<?php
/**
 * Clase de integraciones con WooCommerce
 *
 * @package PMPro_Woo_Sync
 * @since 2.0.0
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PMPro_Woo_Sync_Integrations {

    /**
     * Instancia del plugin principal
     *
     * @var PMPro_Woo_Sync
     */
    private $plugin;

    /**
     * Constructor
     */
    public function __construct() {
        $this->plugin = PMPro_Woo_Sync::get_instance();
    }

    /**
     * Registrar hooks de WooCommerce
     */
    public function register_hooks() {
        // Verificar que la sincronización esté habilitada
        if ( ! $this->plugin->is_sync_enabled() ) {
            return;
        }

        // Hooks principales de suscripciones
        add_action( 'woocommerce_subscription_status_updated', array( $this, 'sync_membership_with_subscription' ), 10, 3 );
        
        // Hooks específicos por estado
        add_action( 'woocommerce_subscription_status_active', array( $this, 'activate_membership' ), 10, 1 );
        add_action( 'woocommerce_subscription_status_cancelled', array( $this, 'cancel_membership' ), 10, 1 );
        add_action( 'woocommerce_subscription_status_expired', array( $this, 'cancel_membership' ), 10, 1 );
        add_action( 'woocommerce_subscription_status_on-hold', array( $this, 'pause_membership' ), 10, 1 );
        add_action( 'woocommerce_subscription_status_pending-cancel', array( $this, 'pending_cancel_membership' ), 10, 1 );

        // Hooks de renovación de pagos
        add_action( 'woocommerce_subscription_renewal_payment_complete', array( $this, 'renewal_payment_complete' ), 10, 2 );
        add_action( 'woocommerce_subscription_renewal_payment_failed', array( $this, 'renewal_payment_failed' ), 10, 2 );

        // Hooks de pedidos (para suscripciones iniciales)
        add_action( 'woocommerce_order_status_completed', array( $this, 'process_completed_order' ), 10, 1 );
        add_action( 'woocommerce_order_status_processing', array( $this, 'process_completed_order' ), 10, 1 );

        // Hook para cancelaciones desde PMPro
        add_action( 'pmpro_membership_post_membership_expiry', array( $this, 'handle_pmpro_expiry' ), 10, 2 );
        add_action( 'pmpro_after_change_membership_level', array( $this, 'handle_pmpro_level_change' ), 10, 3 );
    }

    /**
     * Sincronizar membresía según cambio de estado de suscripción
     *
     * @param WC_Subscription $subscription
     * @param string $new_status
     * @param string $old_status
     */
    public function sync_membership_with_subscription( $subscription, $new_status, $old_status ) {
        $user_id = $subscription->get_user_id();
        $level_id = $this->get_linked_membership_level( $subscription );

        if ( ! $user_id || ! $level_id ) {
            $this->log( sprintf( 
                'No se pudo sincronizar suscripción %d: usuario=%d, nivel=%d', 
                $subscription->get_id(), 
                $user_id, 
                $level_id 
            ), 'warning' );
            return;
        }

        $this->log( sprintf(
            'Sincronizando suscripción %d: %s -> %s (Usuario: %d, Nivel: %d)',
            $subscription->get_id(),
            $old_status,
            $new_status,
            $user_id,
            $level_id
        ), 'info' );

        switch ( $new_status ) {
            case 'active':
                $this->activate_user_membership( $user_id, $level_id, $subscription );
                break;
            
            case 'cancelled':
            case 'expired':
                $this->cancel_user_membership( $user_id, $subscription );
                break;
            
            case 'on-hold':
            case 'pending-cancel':
                $this->pause_user_membership( $user_id, $subscription );
                break;
            
            default:
                $this->log( sprintf( 'Estado no manejado: %s para suscripción %d', $new_status, $subscription->get_id() ), 'debug' );
        }

        // Actualizar metadatos de sincronización
        $this->update_sync_metadata( $user_id, $subscription, $new_status );
    }

    /**
     * Activar membresía cuando suscripción se activa
     *
     * @param WC_Subscription $subscription
     */
    public function activate_membership( $subscription ) {
        $user_id = $subscription->get_user_id();
        $level_id = $this->get_linked_membership_level( $subscription );
        
        if ( $user_id && $level_id ) {
            $this->activate_user_membership( $user_id, $level_id, $subscription );
        }
    }

    /**
     * Cancelar membresía cuando suscripción se cancela o expira
     *
     * @param WC_Subscription $subscription
     */
    public function cancel_membership( $subscription ) {
        $user_id = $subscription->get_user_id();
        if ( $user_id ) {
            $this->cancel_user_membership( $user_id, $subscription );
        }
    }

    /**
     * Pausar membresía cuando suscripción está en espera
     *
     * @param WC_Subscription $subscription
     */
    public function pause_membership( $subscription ) {
        $user_id = $subscription->get_user_id();
        if ( $user_id ) {
            $this->pause_user_membership( $user_id, $subscription );
        }
    }

    /**
     * Manejar suscripción pendiente de cancelación
     *
     * @param WC_Subscription $subscription
     */
    public function pending_cancel_membership( $subscription ) {
        $user_id = $subscription->get_user_id();
        if ( $user_id ) {
            $this->log( sprintf( 'Suscripción %d marcada para cancelación (Usuario: %d)', $subscription->get_id(), $user_id ), 'info' );
            // Por ahora, no hacemos nada hasta que se cancele definitivamente
        }
    }

    /**
     * Procesar pago de renovación completado
     *
     * @param WC_Subscription $subscription
     * @param WC_Order $renewal_order
     */
    public function renewal_payment_complete( $subscription, $renewal_order ) {
        $user_id = $subscription->get_user_id();
        $level_id = $this->get_linked_membership_level( $subscription );

        if ( $user_id && $level_id ) {
            // Extender la membresía
            $this->extend_membership( $user_id, $level_id, $subscription );
            
            // Registrar el pago en PMPro si es posible
            $this->record_payment_in_pmpro( $user_id, $renewal_order, $subscription );
            
            $this->log( sprintf( 'Renovación completada para suscripción %d (Usuario: %d)', $subscription->get_id(), $user_id ), 'success' );
        }
    }

    /**
     * Procesar pago de renovación fallido
     *
     * @param WC_Subscription $subscription
     * @param WC_Order $renewal_order
     */
    public function renewal_payment_failed( $subscription, $renewal_order ) {
        $user_id = $subscription->get_user_id();
        
        if ( $user_id ) {
            $this->log( sprintf( 'Pago de renovación fallido para suscripción %d (Usuario: %d)', $subscription->get_id(), $user_id ), 'warning' );
            
            // Aquí podrías implementar lógica de reintentos o notificaciones
            do_action( 'pmpro_woo_sync_renewal_failed', $user_id, $subscription, $renewal_order );
        }
    }

    /**
     * Procesar pedido completado (para suscripciones iniciales)
     *
     * @param int $order_id
     */
    public function process_completed_order( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Verificar si el pedido contiene suscripciones
        if ( function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order ) ) {
            $subscriptions = wcs_get_subscriptions_for_order( $order );
            
            foreach ( $subscriptions as $subscription ) {
                if ( $subscription->get_status() === 'active' ) {
                    $this->activate_membership( $subscription );
                }
            }
        }
    }

    /**
     * Manejar expiración desde PMPro
     *
     * @param int $user_id
     * @param int $level_id
     */
    public function handle_pmpro_expiry( $user_id, $level_id ) {
        // Buscar suscripciones relacionadas y cancelarlas si es necesario
        $subscriptions = $this->get_user_subscriptions_for_level( $user_id, $level_id );
        
        foreach ( $subscriptions as $subscription ) {
            if ( in_array( $subscription->get_status(), array( 'active', 'on-hold' ) ) ) {
                $subscription->update_status( 'cancelled', __( 'Cancelada desde PMPro', 'pmpro-woo-sync' ) );
                $this->log( sprintf( 'Suscripción %d cancelada desde PMPro (Usuario: %d)', $subscription->get_id(), $user_id ), 'info' );
            }
        }
    }

    /**
     * Manejar cambio de nivel desde PMPro
     *
     * @param int $level_id
     * @param int $user_id
     * @param int $old_level_id
     */
    public function handle_pmpro_level_change( $level_id, $user_id, $old_level_id ) {
        // Si se cancela la membresía (level_id = 0), cancelar suscripciones
        if ( $level_id == 0 && $old_level_id > 0 ) {
            $subscriptions = $this->get_user_subscriptions_for_level( $user_id, $old_level_id );
            
            foreach ( $subscriptions as $subscription ) {
                if ( in_array( $subscription->get_status(), array( 'active', 'on-hold' ) ) ) {
                    $subscription->update_status( 'cancelled', __( 'Membresía cancelada desde PMPro', 'pmpro-woo-sync' ) );
                    $this->log( sprintf( 'Suscripción %d cancelada por cambio de nivel PMPro (Usuario: %d)', $subscription->get_id(), $user_id ), 'info' );
                }
            }
        }
    }

    /**
     * Activar membresía de usuario
     *
     * @param int $user_id
     * @param int $level_id
     * @param WC_Subscription $subscription
     */
    private function activate_user_membership( $user_id, $level_id, $subscription ) {
        // Calcular fecha de expiración basada en la suscripción
        $end_date = $this->calculate_membership_end_date( $subscription );
        
        // Cambiar nivel de membresía
        $result = pmpro_changeMembershipLevel( $level_id, $user_id, 'changed', $end_date );
        
        if ( $result ) {
            $this->log( sprintf( 'Membresía activada: Usuario %d, Nivel %d, Expira: %s', $user_id, $level_id, $end_date ), 'success' );
        } else {
            $this->log( sprintf( 'Error al activar membresía: Usuario %d, Nivel %d', $user_id, $level_id ), 'error' );
        }
    }

    /**
     * Cancelar membresía de usuario
     *
     * @param int $user_id
     * @param WC_Subscription $subscription
     */
    private function cancel_user_membership( $user_id, $subscription ) {
        $result = pmpro_changeMembershipLevel( 0, $user_id, 'cancelled' );
        
        if ( $result ) {
            $this->log( sprintf( 'Membresía cancelada: Usuario %d', $user_id ), 'success' );
        } else {
            $this->log( sprintf( 'Error al cancelar membresía: Usuario %d', $user_id ), 'error' );
        }
    }

    /**
     * Pausar membresía de usuario
     *
     * @param int $user_id
     * @param WC_Subscription $subscription
     */
    private function pause_user_membership( $user_id, $subscription ) {
        // PMPro no tiene estado "pausado", así que cancelamos temporalmente
        // Podrías implementar lógica personalizada aquí
        $this->cancel_user_membership( $user_id, $subscription );
        
        // Marcar como pausada en metadatos para posible reactivación
        update_user_meta( $user_id, '_pmpro_woo_sync_paused', time() );
        
        $this->log( sprintf( 'Membresía pausada: Usuario %d', $user_id ), 'info' );
    }

    /**
     * Extender membresía existente
     *
     * @param int $user_id
     * @param int $level_id
     * @param WC_Subscription $subscription
     */
    private function extend_membership( $user_id, $level_id, $subscription ) {
        $current_level = pmpro_getMembershipLevelForUser( $user_id );
        
        if ( $current_level && $current_level->id == $level_id ) {
            // Calcular nueva fecha de expiración
            $new_end_date = $this->calculate_membership_end_date( $subscription );
            
            // Actualizar fecha de expiración
            global $wpdb;
            $wpdb->update(
                $wpdb->pmpro_memberships_users,
                array( 'enddate' => $new_end_date ),
                array( 'user_id' => $user_id, 'membership_id' => $level_id, 'status' => 'active' ),
                array( '%s' ),
                array( '%d', '%d', '%s' )
            );
            
            $this->log( sprintf( 'Membresía extendida: Usuario %d, Nueva expiración: %s', $user_id, $new_end_date ), 'success' );
        } else {
            // Activar nueva membresía
            $this->activate_user_membership( $user_id, $level_id, $subscription );
        }
    }

    /**
     * Registrar pago en PMPro
     *
     * @param int $user_id
     * @param WC_Order $order
     * @param WC_Subscription $subscription
     */
    private function record_payment_in_pmpro( $user_id, $order, $subscription ) {
        if ( ! function_exists( 'pmpro_add_order' ) ) {
            return;
        }

        $pmpro_order = new MemberOrder();
        $pmpro_order->user_id = $user_id;
        $pmpro_order->membership_id = $this->get_linked_membership_level( $subscription );
        $pmpro_order->payment_transaction_id = $order->get_transaction_id();
        $pmpro_order->subscription_transaction_id = $subscription->get_id();
        $pmpro_order->gateway = $order->get_payment_method();
        $pmpro_order->total = $order->get_total();
        $pmpro_order->status = 'success';
        $pmpro_order->timestamp = current_time( 'timestamp' );
        
        $pmpro_order->saveOrder();
        
        $this->log( sprintf( 'Pago registrado en PMPro: Usuario %d, Monto: %s', $user_id, $order->get_total() ), 'info' );
    }

    /**
     * Obtener nivel de membresía vinculado a la suscripción
     *
     * @param WC_Subscription $subscription
     * @return int|false
     */
    private function get_linked_membership_level( $subscription ) {
        // Buscar en metadatos de la suscripción
        $level_id = $subscription->get_meta( '_pmpro_linked_level_id' );
        
        if ( $level_id ) {
            return intval( $level_id );
        }

        // Buscar en los productos de la suscripción
        foreach ( $subscription->get_items() as $item ) {
            $product = $item->get_product();
            if ( $product ) {
                $level_id = get_post_meta( $product->get_id(), '_pmpro_membership_level', true );
                if ( $level_id ) {
                    // Guardar para futuras referencias
                    $subscription->update_meta_data( '_pmpro_linked_level_id', $level_id );
                    $subscription->save();
                    return intval( $level_id );
                }
            }
        }

        return false;
    }

    /**
     * Obtener suscripciones de usuario para un nivel específico
     *
     * @param int $user_id
     * @param int $level_id
     * @return WC_Subscription[]
     */
    private function get_user_subscriptions_for_level( $user_id, $level_id ) {
        if ( ! function_exists( 'wcs_get_users_subscriptions' ) ) {
            return array();
        }

        $subscriptions = wcs_get_users_subscriptions( $user_id );
        $filtered = array();

        foreach ( $subscriptions as $subscription ) {
            if ( $this->get_linked_membership_level( $subscription ) == $level_id ) {
                $filtered[] = $subscription;
            }
        }

        return $filtered;
    }

    /**
     * Calcular fecha de expiración de membresía basada en suscripción
     *
     * @param WC_Subscription $subscription
     * @return string|null
     */
    private function calculate_membership_end_date( $subscription ) {
        $next_payment = $subscription->get_date( 'next_payment' );
        
        if ( $next_payment ) {
            return date( 'Y-m-d H:i:s', strtotime( $next_payment ) );
        }

        // Si no hay próximo pago, calcular basado en el período
        $billing_period = $subscription->get_billing_period();
        $billing_interval = $subscription->get_billing_interval();
        
        $end_date = strtotime( "+{$billing_interval} {$billing_period}" );
        return date( 'Y-m-d H:i:s', $end_date );
    }

    /**
     * Actualizar metadatos de sincronización
     *
     * @param int $user_id
     * @param WC_Subscription $subscription
     * @param string $status
     */
    private function update_sync_metadata( $user_id, $subscription, $status ) {
        update_user_meta( $user_id, '_pmpro_woo_sync_subscription_id', $subscription->get_id() );
        update_user_meta( $user_id, '_pmpro_woo_sync_last_sync', current_time( 'mysql' ) );
        update_user_meta( $user_id, '_pmpro_woo_sync_sync_status', $status );
    }

    /**
     * Registrar mensaje en log
     *
     * @param string $message
     * @param string $level
     */
    private function log( $message, $level = 'info' ) {
        if ( class_exists( 'PMPro_Woo_Sync_Logger' ) ) {
            PMPro_Woo_Sync_Logger::log( $message, $level );
        }
        
        // También log en WooCommerce si está disponible
        if ( function_exists( 'wc_get_logger' ) ) {
            $logger = wc_get_logger();
            $logger->log( $level, $message, array( 'source' => 'pmpro-woo-sync' ) );
        }
    }
}
