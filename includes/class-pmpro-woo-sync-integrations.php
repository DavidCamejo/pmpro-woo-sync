<?php
/**
 * Clase para manejar las integraciones y lógica de sincronización entre PMPRO y WooCommerce.
 */
class PMPro_Woo_Sync_Integrations {

    /**
     * Instancia de PMPro_Woo_Sync_Settings.
     *
     * @var PMPro_Woo_Sync_Settings
     */
    protected $settings;

    /**
     * Instancia de PMPro_Woo_Sync_Logger.
     *
     * @var PMPro_Woo_Sync_Logger
     */
    protected $logger;

    /**
     * Instancia de PMPro_Woo_Sync_Gateway_Manager.
     *
     * @var PMPro_Woo_Sync_Gateway_Manager
     */
    protected $gateway_manager;

    /**
     * Cache temporal para niveles anteriores de usuarios.
     * @var array
     */
    private $previous_levels_cache = array();

    /**
     * Constructor.
     *
     * @param PMPro_Woo_Sync_Settings $settings Instancia de la clase de ajustes.
     * @param PMPro_Woo_Sync_Logger   $logger   Instancia de la clase de logs.
     */
    public function __construct( PMPro_Woo_Sync_Settings $settings, PMPro_Woo_Sync_Logger $logger ) {
        $this->settings = $settings;
        $this->logger   = $logger;
        $this->gateway_manager = new PMPro_Woo_Sync_Gateway_Manager( $settings, $logger );
    }

    /**
     * Almacena el nivel anterior del usuario antes de que cambie.
     * Se ejecuta en el hook pmpro_before_change_membership_level.
     *
     * @param int $level_id      Nuevo nivel que se asignará.
     * @param int $user_id       ID del usuario.
     * @param int $cancel_level  Nivel que se está cancelando.
     */
    public function store_previous_level( $level_id, $user_id, $cancel_level ) {
        $current_level = pmpro_getMembershipLevelForUser( $user_id );
        
        if ( $current_level ) {
            $this->previous_levels_cache[ $user_id ] = $current_level;
            $this->logger->debug( 
                "Nivel anterior almacenado para usuario {$user_id}: {$current_level->name}",
                array( 'user_id' => $user_id, 'level_id' => $current_level->id ),
                __METHOD__
            );
        }
    }

    /**
     * Maneja el evento de finalización de compra de Paid Memberships Pro.
     *
     * @param int   $user_id ID del usuario.
     * @param array $morder  Objeto de orden de membresía de PMPRO.
     */
    public function handle_pmpro_checkout( $user_id, $morder ) {
        if ( 'yes' !== $this->settings->get_setting( 'enable_sync', 'yes' ) ) {
            $this->logger->info( 'Sincronización deshabilitada. PMPRO checkout omitido.', array( 'user_id' => $user_id ) );
            return;
        }

        $this->logger->info( 
            'Procesando checkout de PMPRO',
            array( 
                'user_id' => $user_id, 
                'membership_level_id' => $morder->membership_id,
                'gateway' => $morder->gateway ?? 'unknown'
            ),
            __METHOD__
        );

        // Buscar producto WooCommerce relacionado con el nivel de membresía
        $related_product = $this->find_related_woocommerce_product( $morder->membership_id );
        
        if ( $related_product ) {
            $this->create_or_update_woocommerce_subscription( $user_id, $related_product, $morder );
        } else {
            $this->logger->warning(
                "No se encontró producto WooCommerce relacionado con el nivel PMPro {$morder->membership_id}",
                array( 'membership_level_id' => $morder->membership_id ),
                __METHOD__
            );
        }
    }

    /**
     * Sincroniza cambios de nivel de membresía de PMPro a WooCommerce.
     *
     * @param int $level_id      Nuevo nivel de membresía.
     * @param int $user_id       ID del usuario.
     * @param int $cancel_level  Nivel cancelado.
     */
    public function sync_membership_to_woo( $level_id, $user_id, $cancel_level ) {
        if ( 'yes' !== $this->settings->get_setting( 'enable_sync', 'yes' ) ) {
            return;
        }

        $this->logger->info(
            "Sincronizando cambio de membresía PMPro",
            array( 
                'user_id' => $user_id, 
                'new_level_id' => $level_id,
                'cancel_level' => $cancel_level
            ),
            __METHOD__
        );

        // Si el nuevo nivel es 0, es una cancelación
        if ( 0 === intval( $level_id ) ) {
            $this->handle_membership_cancellation( $user_id, $cancel_level );
        } else {
            $this->handle_membership_upgrade_or_new( $user_id, $level_id );
        }
    }

    /**
     * Maneja el cambio de estado de una suscripción de WooCommerce.
     *
     * @param WC_Subscription $subscription La suscripción.
     * @param string          $new_status   Nuevo estado.
     * @param string          $old_status   Estado anterior.
     */
    public function handle_subscription_status_change( $subscription, $new_status, $old_status ) {
        if ( 'yes' !== $this->settings->get_setting( 'enable_sync', 'yes' ) ) {
            return;
        }

        $user_id = $subscription->get_user_id();
        $subscription_id = $subscription->get_id();

        $this->logger->info(
            "Cambio de estado de suscripción WooCommerce: #{$subscription_id} de {$old_status} a {$new_status}",
            array( 
                'user_id' => $user_id, 
                'subscription_id' => $subscription_id,
                'old_status' => $old_status,
                'new_status' => $new_status
            ),
            __METHOD__
        );

        switch ( $new_status ) {
            case 'active':
                $this->sync_active_subscription_to_pmpro( $subscription );
                break;
            case 'cancelled':
            case 'expired':
                $this->sync_cancelled_subscription_to_pmpro( $subscription );
                break;
            case 'on-hold':
                $this->sync_on_hold_subscription_to_pmpro( $subscription );
                break;
        }
    }

    /**
     * Maneja cambios de estado de órdenes de WooCommerce.
     *
     * @param int    $order_id   ID de la orden.
     * @param string $old_status Estado anterior.
     * @param string $new_status Nuevo estado.
     * @param object $order      Objeto de la orden.
     */
    public function handle_order_status_change( $order_id, $old_status, $new_status, $order ) {
        if ( 'yes' !== $this->settings->get_setting( 'enable_sync', 'yes' ) ) {
            return;
        }

        // Solo procesar órdenes que contengan productos de membresía
        if ( ! $this->order_contains_membership_products( $order ) ) {
            return;
        }

        $this->logger->info(
            "Cambio de estado de orden WooCommerce: #{$order_id} de {$old_status} a {$new_status}",
            array( 
                'order_id' => $order_id,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'user_id' => $order->get_user_id()
            ),
            __METHOD__
        );

        switch ( $new_status ) {
            case 'completed':
                $this->process_completed_membership_order( $order );
                break;
            case 'refunded':
            case 'cancelled':
                $this->process_cancelled_membership_order( $order );
                break;
        }
    }

    /**
     * Maneja pagos completados de suscripciones.
     *
     * @param WC_Subscription $subscription La suscripción.
     */
    public function handle_subscription_payment_complete( $subscription ) {
        if ( 'yes' !== $this->settings->get_setting( 'enable_sync', 'yes' ) ) {
            return;
        }

        $this->logger->info(
            "Pago de suscripción completado: #{$subscription->get_id()}",
            array( 
                'subscription_id' => $subscription->get_id(),
                'user_id' => $subscription->get_user_id()
            ),
            __METHOD__
        );

        // Asegurar que el usuario mantenga su nivel de membresía activo
        $this->sync_active_subscription_to_pmpro( $subscription );
    }

    /**
     * Maneja fallos de pago de suscripciones.
     *
     * @param WC_Subscription $subscription La suscripción.
     */
    public function handle_subscription_payment_failed( $subscription ) {
        if ( 'yes' !== $this->settings->get_setting( 'enable_sync', 'yes' ) ) {
            return;
        }

        $this->logger->warning(
            "Fallo de pago en suscripción: #{$subscription->get_id()}",
            array( 
                'subscription_id' => $subscription->get_id(),
                'user_id' => $subscription->get_user_id()
            ),
            __METHOD__
        );

        // Opcional: Cambiar estado de membresía según configuración
        $this->handle_failed_payment_membership( $subscription );
    }

    /**
     * Maneja la cancelación de membresía desde PMPro.
     *
     * @param int $level_id     Nuevo nivel (generalmente 0).
     * @param int $user_id      ID del usuario.
     * @param int $cancel_level Nivel que se canceló.
     */
    public function handle_pmpro_cancellation_from_pmpro( $level_id, $user_id, $cancel_level ) {
        // Obtener nivel anterior del cache
        $old_level = isset( $this->previous_levels_cache[ $user_id ] ) 
                    ? $this->previous_levels_cache[ $user_id ] 
                    : null;

        // Solo procesar si realmente es una cancelación
        if ( ! $old_level || 0 !== intval( $level_id ) ) {
            return;
        }

        $this->logger->info(
            "Procesando cancelación de membresía PMPro",
            array( 
                'user_id' => $user_id,
                'cancelled_level_id' => $old_level->id,
                'cancelled_level_name' => $old_level->name
            ),
            __METHOD__
        );

        // Buscar suscripción de WooCommerce relacionada
        $subscription = $this->get_related_woocommerce_subscription( $user_id, $old_level->id );

        if ( $subscription && $subscription->has_status( array( 'active', 'pending-cancel' ) ) ) {
            $gateway_id = $subscription->get_payment_method();
            
            // Verificar si el gateway requiere notificación
            if ( $this->gateway_requires_notification( $gateway_id ) ) {
                $this->cancel_subscription_at_gateway( $subscription, $gateway_id );
            }
        }

        // Limpiar cache
        unset( $this->previous_levels_cache[ $user_id ] );
    }

    /**
     * Busca el producto WooCommerce relacionado con un nivel de membresía PMPro.
     *
     * @param int $pmpro_level_id ID del nivel de membresía.
     * @return WC_Product|false
     */
    private function find_related_woocommerce_product( $pmpro_level_id ) {
        // Buscar productos con meta que los vincule al nivel de PMPro
        $args = array(
            'post_type'      => 'product',
            'meta_query'     => array(
                array(
                    'key'     => '_pmpro_membership_level',
                    'value'   => $pmpro_level_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
        );

        $products = get_posts( $args );
        
        if ( ! empty( $products ) ) {
            return wc_get_product( $products[0]->ID );
        }

        return false;
    }

    /**
     * Busca la suscripción de WooCommerce relacionada con un usuario y nivel de membresía.
     *
     * @param int $user_id         ID del usuario.
     * @param int $pmpro_level_id  ID del nivel de membresía de PMPro.
     * @return WC_Subscription|false
     */
    protected function get_related_woocommerce_subscription( $user_id, $pmpro_level_id = 0 ) {
        if ( ! function_exists( 'wcs_get_users_subscriptions' ) ) {
            return false;
        }

        $subscriptions = wcs_get_users_subscriptions( $user_id );

        foreach ( $subscriptions as $subscription ) {
            $linked_pmpro_level_id = $subscription->get_meta( '_pmpro_linked_level_id' );

            if ( $linked_pmpro_level_id == $pmpro_level_id && 
                 $subscription->has_status( array( 'active', 'on-hold', 'pending-cancel' ) ) ) {
                return $subscription;
            }
        }

        return false;
    }

    /**
     * Verifica si un gateway requiere notificación para cancelaciones.
     *
     * @param string $gateway_id ID del gateway.
     * @return bool
     */
    private function gateway_requires_notification( $gateway_id ) {
        $gateways_to_notify = apply_filters( 
            'pmpro_woo_sync_gateways_to_notify_on_cancel', 
            array( 'pagbank', 'stripe', 'paypal' ) 
        );

        return in_array( $gateway_id, $gateways_to_notify, true );
    }

    /**
     * Cancela una suscripción en el gateway externo.
     *
     * @param WC_Subscription $subscription La suscripción.
     * @param string          $gateway_id   ID del gateway.
     */
    private function cancel_subscription_at_gateway( $subscription, $gateway_id ) {
        $result = $this->gateway_manager->cancel_subscription_at_gateway( $subscription, $gateway_id );

        if ( is_wp_error( $result ) ) {
            $this->logger->error(
                "Error al cancelar suscripción #{$subscription->get_id()} en gateway {$gateway_id}: " . $result->get_error_message(),
                array( 
                    'subscription_id' => $subscription->get_id(),
                    'gateway' => $gateway_id,
                    'error' => $result->get_error_data()
                ),
                __METHOD__
            );
        } else {
            $this->logger->success(
                "Suscripción #{$subscription->get_id()} cancelada exitosamente en gateway {$gateway_id}",
                array( 
                    'subscription_id' => $subscription->get_id(),
                    'gateway' => $gateway_id
                ),
                __METHOD__
            );
        }
    }

    /**
     * Sincroniza una suscripción activa de WooCommerce con PMPro.
     *
     * @param WC_Subscription $subscription La suscripción.
     */
    private function sync_active_subscription_to_pmpro( $subscription ) {
        $user_id = $subscription->get_user_id();
        $pmpro_level_id = $subscription->get_meta( '_pmpro_linked_level_id' );

        if ( empty( $pmpro_level_id ) ) {
            $this->logger->warning(
                "Suscripción #{$subscription->get_id()} no tiene nivel PMPro vinculado",
                array( 'subscription_id' => $subscription->get_id() ),
                __METHOD__
            );
            return;
        }

        // Verificar si el usuario ya tiene el nivel correcto
        $current_level = pmpro_getMembershipLevelForUser( $user_id );
        
        if ( $current_level && $current_level->id == $pmpro_level_id ) {
            $this->logger->debug( "Usuario {$user_id} ya tiene el nivel correcto", array( 'level_id' => $pmpro_level_id ) );
            return;
        }

        // Asignar nivel de membresía
        $result = pmpro_changeMembershipLevel( $pmpro_level_id, $user_id );
        
        if ( $result ) {
            $this->logger->success(
                "Nivel de membresía sincronizado: Usuario {$user_id} asignado al nivel {$pmpro_level_id}",
                array( 'user_id' => $user_id, 'level_id' => $pmpro_level_id ),
                __METHOD__
            );
        } else {
            $this->logger->error(
                "Error al sincronizar nivel de membresía para usuario {$user_id}",
                array( 'user_id' => $user_id, 'level_id' => $pmpro_level_id ),
                __METHOD__
            );
        }
    }

    /**
     * Sincroniza una suscripción cancelada con PMPro.
     *
     * @param WC_Subscription $subscription La suscripción.
     */
    private function sync_cancelled_subscription_to_pmpro( $subscription ) {
        $user_id = $subscription->get_user_id();
        
        // Cancelar membresía en PMPro
        $result = pmpro_changeMembershipLevel( 0, $user_id );
        
        if ( $result ) {
            $this->logger->success(
                "Membresía cancelada en PMPro para usuario {$user_id} debido a cancelación de suscripción #{$subscription->get_id()}",
                array( 'user_id' => $user_id, 'subscription_id' => $subscription->get_id() ),
                __METHOD__
            );
        }
    }

    /**
     * Maneja suscripciones en pausa.
     *
     * @param WC_Subscription $subscription La suscripción.
     */
    private function sync_on_hold_subscription_to_pmpro( $subscription ) {
        // Según configuración, podríamos mantener el nivel o degradarlo
        $maintain_level_on_hold = apply_filters( 'pmpro_woo_sync_maintain_level_on_hold', true );
        
        if ( ! $maintain_level_on_hold ) {
            $this->sync_cancelled_subscription_to_pmpro( $subscription );
        }
    }

    /**
     * Verifica si una orden contiene productos de membresía.
     *
     * @param WC_Order $order La orden.
     * @return bool
     */
    private function order_contains_membership_products( $order ) {
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( $product && $product->get_meta( '_pmpro_membership_level' ) ) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Procesa una orden de membresía completada.
     *
     * @param WC_Order $order La orden.
     */
    private function process_completed_membership_order( $order ) {
        $user_id = $order->get_user_id();
        
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            $pmpro_level_id = $product->get_meta( '_pmpro_membership_level' );
            
            if ( $pmpro_level_id ) {
                pmpro_changeMembershipLevel( $pmpro_level_id, $user_id );
                
                $this->logger->success(
                    "Membresía asignada por orden completada: Usuario {$user_id} -> Nivel {$pmpro_level_id}",
                    array( 'user_id' => $user_id, 'level_id' => $pmpro_level_id, 'order_id' => $order->get_id() ),
                    __METHOD__
                );
            }
        }
    }

    /**
     * Procesa una orden de membresía cancelada.
     *
     * @param WC_Order $order La orden.
     */
    private function process_cancelled_membership_order( $order ) {
        $user_id = $order->get_user_id();
        
        // Cancelar membresía solo si esta orden era la que le otorgó la membresía actual
        $current_level = pmpro_getMembershipLevelForUser( $user_id );
        
        if ( $current_level ) {
            // Verificar si esta orden es la fuente de la membresía actual
            $last_order = $this->get_last_membership_order_for_user( $user_id );
            
            if ( $last_order && $last_order->get_id() === $order->get_id() ) {
                pmpro_changeMembershipLevel( 0, $user_id );
                
                $this->logger->success(
                    "Membresía cancelada por orden cancelada/reembolsada: Usuario {$user_id}",
                    array( 'user_id' => $user_id, 'order_id' => $order->get_id() ),
                    __METHOD__
                );
            }
        }
    }

    /**
     * Maneja membresías con fallos de pago.
     *
     * @param WC_Subscription $subscription La suscripción.
     */
    private function handle_failed_payment_membership( $subscription ) {
        $grace_period = apply_filters( 'pmpro_woo_sync_payment_failure_grace_period', false );
        
        if ( ! $grace_period ) {
            // Cancelar inmediatamente
            $this->sync_cancelled_subscription_to_pmpro( $subscription );
        } else {
            // Implementar lógica de período de gracia
            $this->schedule_grace_period_check( $subscription );
        }
    }

    /**
     * Crea o actualiza una suscripción de WooCommerce basada en una membresía PMPro.
     *
     * @param int        $user_id         ID del usuario.
     * @param WC_Product $product         Producto relacionado.
     * @param object     $morder          Orden de membresía PMPro.
     */
    private function create_or_update_woocommerce_subscription( $user_id, $product, $morder ) {
        // Esta función requiere lógica compleja para crear suscripciones de WooCommerce
        // programáticamente. Es un TODO complejo que requiere manejo cuidadoso.
        
        $this->logger->debug(
            "TODO: Implementar creación/actualización de suscripción WooCommerce",
            array( 
                'user_id' => $user_id,
                'product_id' => $product->get_id(),
                'pmpro_order_id' => $morder->id ?? null
            ),
            __METHOD__
        );
    }

    /**
     * Obtiene la última orden de membresía para un usuario.
     *
     * @param int $user_id ID del usuario.
     * @return WC_Order|false
     */
    private function get_last_membership_order_for_user( $user_id ) {
        $orders = wc_get_orders( array(
            'customer' => $user_id,
            'status'   => array( 'completed', 'processing' ),
            'limit'    => 1,
            'orderby'  => 'date',
            'order'    => 'DESC',
        ));

        foreach ( $orders as $order ) {
            if ( $this->order_contains_membership_products( $order ) ) {
                return $order;
            }
        }

        return false;
    }

    /**
     * Programa verificación de período de gracia.
     *
     * @param WC_Subscription $subscription La suscripción.
     */
    private function schedule_grace_period_check( $subscription ) {
        // Implementar lógica de programación de tareas cron para verificar
        // el estado de la suscripción después del período de gracia
        
        $this->logger->debug(
            "TODO: Implementar programación de período de gracia",
            array( 'subscription_id' => $subscription->get_id() ),
            __METHOD__
        );
    }

    /**
     * Maneja la cancelación de membresía.
     *
     * @param int $user_id      ID del usuario.
     * @param int $cancel_level Nivel cancelado.
     */
    private function handle_membership_cancellation( $user_id, $cancel_level ) {
        $this->logger->info(
            "Procesando cancelación de membresía",
            array( 'user_id' => $user_id, 'cancel_level' => $cancel_level ),
            __METHOD__
        );

        // Buscar y cancelar suscripciones relacionadas
        if ( function_exists( 'wcs_get_users_subscriptions' ) ) {
            $subscriptions = wcs_get_users_subscriptions( $user_id );
            
            foreach ( $subscriptions as $subscription ) {
                $linked_level = $subscription->get_meta( '_pmpro_linked_level_id' );
                
                if ( $linked_level == $cancel_level && $subscription->has_status( 'active' ) ) {
                    $subscription->update_status( 'cancelled', 'Cancelado desde PMPro' );
                    
                    $this->logger->success(
                        "Suscripción WooCommerce #{$subscription->get_id()} cancelada por cancelación PMPro",
                        array( 'subscription_id' => $subscription->get_id(), 'user_id' => $user_id ),
                        __METHOD__
                    );
                }
            }
        }
    }

    /**
     * Maneja upgrade o nueva membresía.
     *
     * @param int $user_id  ID del usuario.
     * @param int $level_id Nuevo nivel de membresía.
     */
    private function handle_membership_upgrade_or_new( $user_id, $level_id ) {
        $this->logger->info(
            "Procesando upgrade/nueva membresía",
            array( 'user_id' => $user_id, 'level_id' => $level_id ),
            __METHOD__
        );

        // Buscar producto WooCommerce relacionado y crear/actualizar suscripción si es necesario
        $related_product = $this->find_related_woocommerce_product( $level_id );
        
        if ( $related_product ) {
            $this->logger->debug(
                "Producto WooCommerce encontrado para nivel {$level_id}: {$related_product->get_id()}",
                array( 'level_id' => $level_id, 'product_id' => $related_product->get_id() ),
                __METHOD__
            );
            
            // TODO: Lógica para crear/actualizar suscripción WooCommerce
        }
    }
}
