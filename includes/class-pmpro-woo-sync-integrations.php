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

        // Hooks principales de pedidos
        add_action( 'woocommerce_order_status_completed', array( $this, 'process_completed_order' ), 10, 1 );
        add_action( 'woocommerce_order_status_processing', array( $this, 'process_processing_order' ), 10, 1 );
        add_action( 'woocommerce_order_status_cancelled', array( $this, 'process_cancelled_order' ), 10, 1 );
        add_action( 'woocommerce_order_status_failed', array( $this, 'process_failed_order' ), 10, 1 );
        add_action( 'woocommerce_order_status_refunded', array( $this, 'process_refunded_order' ), 10, 1 );

        // Hooks para pagos recurrentes de PagBank
        add_action( 'woocommerce_pagbank_recurring_payment_complete', array( $this, 'handle_recurring_payment_complete' ), 10, 2 );
        add_action( 'woocommerce_pagbank_recurring_payment_failed', array( $this, 'handle_recurring_payment_failed' ), 10, 2 );

        // Hook para cancelaciones desde PMPro
        add_action( 'pmpro_membership_post_membership_expiry', array( $this, 'handle_pmpro_expiry' ), 10, 2 );
        add_action( 'pmpro_after_change_membership_level', array( $this, 'handle_pmpro_level_change' ), 10, 3 );
    }

    /**
     * Procesar pedido completado
     *
     * @param int $order_id
     */
    public function process_completed_order( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $user_id = $order->get_user_id();
        if ( ! $user_id ) {
            $this->log( sprintf( 'Pedido %d completado pero sin usuario asociado', $order_id ), 'warning' );
            return;
        }

        // Buscar productos con niveles de membresía asociados
        $membership_products = $this->get_membership_products_from_order( $order );
        
        if ( empty( $membership_products ) ) {
            $this->log( sprintf( 'Pedido %d completado pero sin productos de membresía', $order_id ), 'debug' );
            return;
        }

        // Activar membresías para cada producto
        foreach ( $membership_products as $product_data ) {
            $this->activate_user_membership( $user_id, $product_data['level_id'], $order, $product_data );
        }

        // Actualizar metadatos de sincronización
        $this->update_sync_metadata( $user_id, $order, 'completed' );

        $this->log( sprintf( 'Pedido %d procesado exitosamente para usuario %d', $order_id, $user_id ), 'success' );
    }

    /**
     * Procesar pedido en procesamiento
     *
     * @param int $order_id
     */
    public function process_processing_order( $order_id ) {
        // Para algunos gateways, el estado 'processing' es suficiente para activar membresías
        $this->process_completed_order( $order_id );
    }

    /**
     * Procesar pedido cancelado
     *
     * @param int $order_id
     */
    public function process_cancelled_order( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $user_id = $order->get_user_id();
        if ( ! $user_id ) {
            return;
        }

        // Cancelar membresía asociada al pedido
        $this->cancel_membership_from_order( $user_id, $order );
        
        $this->log( sprintf( 'Pedido %d cancelado - membresía cancelada para usuario %d', $order_id, $user_id ), 'info' );
    }

    /**
     * Procesar pedido fallido
     *
     * @param int $order_id
     */
    public function process_failed_order( $order_id ) {
        // Solo procesar si está configurado para sincronizar pedidos fallidos
        if ( ! $this->get_setting( 'sync_failed_orders', false ) ) {
            return;
        }

        $this->process_cancelled_order( $order_id );
    }

    /**
     * Procesar pedido reembolsado
     *
     * @param int $order_id
     */
    public function process_refunded_order( $order_id ) {
        $this->process_cancelled_order( $order_id );
    }

    /**
     * Manejar pago recurrente completado de PagBank
     *
     * @param WC_Order $order Pedido de renovación
     * @param array $payment_data Datos del pago
     */
    public function handle_recurring_payment_complete( $order, $payment_data ) {
        $user_id = $order->get_user_id();
        if ( ! $user_id ) {
            return;
        }

        // Buscar la membresía activa del usuario
        $current_level = pmpro_getMembershipLevelForUser( $user_id );
        if ( ! $current_level ) {
            $this->log( sprintf( 'Pago recurrente completado pero usuario %d no tiene membresía activa', $user_id ), 'warning' );
            return;
        }

        // Extender la membresía
        $this->extend_membership( $user_id, $current_level->id, $order );
        
        // Registrar el pago en PMPro si está configurado
        if ( $this->get_setting( 'record_payments_in_pmpro', true ) ) {
            $this->record_payment_in_pmpro( $user_id, $order );
        }

        $this->log( sprintf( 'Pago recurrente completado para usuario %d - membresía extendida', $user_id ), 'success' );
    }

    /**
     * Manejar pago recurrente fallido de PagBank
     *
     * @param WC_Order $order Pedido de renovación fallido
     * @param array $payment_data Datos del pago
     */
    public function handle_recurring_payment_failed( $order, $payment_data ) {
        $user_id = $order->get_user_id();
        if ( ! $user_id ) {
            return;
        }

        $this->log( sprintf( 'Pago recurrente fallido para usuario %d - pedido %d', $user_id, $order->get_id() ), 'warning' );
        
        // Aquí podrías implementar lógica de reintentos o notificaciones
        do_action( 'pmpro_woo_sync_recurring_payment_failed', $user_id, $order, $payment_data );
    }

    /**
     * Manejar expiración desde PMPro
     *
     * @param int $user_id
     * @param int $level_id
     */
    public function handle_pmpro_expiry( $user_id, $level_id ) {
        $this->log( sprintf( 'Membresía expirada desde PMPro: Usuario %d, Nivel %d', $user_id, $level_id ), 'info' );
        
        // Aquí podrías cancelar pagos recurrentes en PagBank si es necesario
        do_action( 'pmpro_woo_sync_membership_expired', $user_id, $level_id );
    }

    /**
     * Manejar cambio de nivel desde PMPro
     *
     * @param int $level_id
     * @param int $user_id
     * @param int $old_level_id
     */
    public function handle_pmpro_level_change( $level_id, $user_id, $old_level_id ) {
        if ( $level_id == 0 && $old_level_id > 0 ) {
            $this->log( sprintf( 'Membresía cancelada desde PMPro: Usuario %d', $user_id ), 'info' );
            
            // Aquí podrías cancelar pagos recurrentes
            do_action( 'pmpro_woo_sync_membership_cancelled', $user_id, $old_level_id );
        }
    }

    /**
     * Activar membresía de usuario
     *
     * @param int $user_id
     * @param int $level_id
     * @param WC_Order $order
     * @param array $product_data
     */
    private function activate_user_membership( $user_id, $level_id, $order, $product_data ) {
        // Calcular fecha de expiración basada en el producto
        $end_date = $this->calculate_membership_end_date( $product_data, $order );
        
        // Cambiar nivel de membresía
        $result = pmpro_changeMembershipLevel( $level_id, $user_id, 'changed', $end_date );
        
        if ( $result ) {
            $this->log( sprintf( 'Membresía activada: Usuario %d, Nivel %d, Expira: %s', $user_id, $level_id, $end_date ), 'success' );
            
            // Registrar el pago inicial en PMPro si está configurado
            if ( $this->get_setting( 'record_payments_in_pmpro', true ) ) {
                $this->record_payment_in_pmpro( $user_id, $order );
            }
        } else {
            $this->log( sprintf( 'Error al activar membresía: Usuario %d, Nivel %d', $user_id, $level_id ), 'error' );
        }
    }

    /**
     * Cancelar membresía desde pedido
     *
     * @param int $user_id
     * @param WC_Order $order
     */
    private function cancel_membership_from_order( $user_id, $order ) {
        $result = pmpro_changeMembershipLevel( 0, $user_id, 'cancelled' );
        
        if ( $result ) {
            $this->log( sprintf( 'Membresía cancelada: Usuario %d', $user_id ), 'success' );
        } else {
            $this->log( sprintf( 'Error al cancelar membresía: Usuario %d', $user_id ), 'error' );
        }
    }

    /**
     * Extender membresía existente
     *
     * @param int $user_id
     * @param int $level_id
     * @param WC_Order $order
     */
    private function extend_membership( $user_id, $level_id, $order ) {
        $current_level = pmpro_getMembershipLevelForUser( $user_id );
        
        if ( $current_level && $current_level->id == $level_id ) {
            // Calcular nueva fecha de expiración
            $current_end = $current_level->enddate ? strtotime( $current_level->enddate ) : time();
            $extension_period = $this->get_membership_extension_period( $level_id );
            $new_end_date = date( 'Y-m-d H:i:s', $current_end + $extension_period );
            
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
        }
    }

    /**
     * Obtener productos de membresía de un pedido
     *
     * @param WC_Order $order
     * @return array
     */
    private function get_membership_products_from_order( $order ) {
        $membership_products = array();
        
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }
            
            $level_id = get_post_meta( $product->get_id(), '_pmpro_membership_level', true );
            if ( $level_id ) {
                $membership_products[] = array(
                    'level_id' => intval( $level_id ),
                    'product_id' => $product->get_id(),
                    'product' => $product,
                    'item' => $item,
                );
            }
        }
        
        return $membership_products;
    }

    /**
     * Calcular fecha de expiración de membresía
     *
     * @param array $product_data
     * @param WC_Order $order
     * @return string|null
     */
    private function calculate_membership_end_date( $product_data, $order ) {
        $product = $product_data['product'];
        
        // Buscar período de membresía en metadatos del producto
        $membership_period = get_post_meta( $product->get_id(), '_pmpro_membership_period', true );
        $membership_period_unit = get_post_meta( $product->get_id(), '_pmpro_membership_period_unit', true );
        
        if ( $membership_period && $membership_period_unit ) {
            $end_date = strtotime( "+{$membership_period} {$membership_period_unit}" );
            return date( 'Y-m-d H:i:s', $end_date );
        }
        
        // Por defecto, 1 año de duración
        return date( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
    }

    /**
     * Obtener período de extensión de membresía
     *
     * @param int $level_id
     * @return int Segundos de extensión
     */
    private function get_membership_extension_period( $level_id ) {
        // Por defecto, extender por 1 mes
        return 30 * 24 * 60 * 60; // 30 días en segundos
    }

    /**
     * Registrar pago en PMPro
     *
     * @param int $user_id
     * @param WC_Order $order
     */
    private function record_payment_in_pmpro( $user_id, $order ) {
        if ( ! function_exists( 'pmpro_add_order' ) ) {
            return;
        }

        $current_level = pmpro_getMembershipLevelForUser( $user_id );
        if ( ! $current_level ) {
            return;
        }

        $pmpro_order = new MemberOrder();
        $pmpro_order->user_id = $user_id;
        $pmpro_order->membership_id = $current_level->id;
        $pmpro_order->payment_transaction_id = $order->get_transaction_id();
        $pmpro_order->subscription_transaction_id = $order->get_id();
        $pmpro_order->gateway = $order->get_payment_method();
        $pmpro_order->total = $order->get_total();
        $pmpro_order->status = 'success';
        $pmpro_order->timestamp = current_time( 'timestamp' );
        
        $pmpro_order->saveOrder();
        
        $this->log( sprintf( 'Pago registrado en PMPro: Usuario %d, Monto: %s', $user_id, $order->get_total() ), 'info' );
    }

    /**
     * Actualizar metadatos de sincronización
     *
     * @param int $user_id
     * @param WC_Order $order
     * @param string $status
     */
    private function update_sync_metadata( $user_id, $order, $status ) {
        update_user_meta( $user_id, '_pmpro_woo_sync_order_id', $order->get_id() );
        update_user_meta( $user_id, '_pmpro_woo_sync_last_sync', current_time( 'mysql' ) );
        update_user_meta( $user_id, '_pmpro_woo_sync_sync_status', $status );
    }

    /**
     * Obtener configuración del plugin
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function get_setting( $key, $default = null ) {
        if ( $this->plugin && method_exists( $this->plugin, 'get_setting' ) ) {
            return $this->plugin->get_setting( $key, $default );
        }
        
        $settings = get_option( 'pmpro_woo_sync_settings', array() );
        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
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
