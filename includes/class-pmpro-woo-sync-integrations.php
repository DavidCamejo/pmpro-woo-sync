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
    protected $gateway_manager; // <--- NEW LINE

    /**
     * Constructor.
     *
     * @param PMPro_Woo_Sync_Settings $settings Instancia de la clase de ajustes.
     * @param PMPro_Woo_Sync_Logger   $logger   Instancia de la clase de logs.
     */
    public function __construct( PMPro_Woo_Sync_Settings $settings, PMPro_Woo_Sync_Logger $logger ) {
        $this->settings = $settings;
        $this->logger   = $logger;

        // Instancia el gestor de gateways.
        $this->gateway_manager = new PMPro_Woo_Sync_Gateway_Manager( $settings, $logger ); // Necesitarás crear esta clase.
    }

    /**
     * Maneja el evento de finalización de compra de Paid Memberships Pro.
     *
     * @param int $user_id      ID del usuario.
     * @param array $morder     Objeto de orden de membresía de PMPRO.
     */
    public function handle_pmpro_checkout( $user_id, $morder ) {
        if ( ! $this->settings->get_setting( 'enable_sync' ) ) {
            $this->logger->info( 'Sincronización deshabilitada. PMPRO checkout omitido.', [ 'user_id' => $user_id ] );
            return;
        }

        // TODO: Lógica para encontrar el producto de WooCommerce asociado y la suscripción.
        // O bien, registrar la membresía PMPRO para futuras acciones en WooCommerce.
        $this->logger->info( 'PMPRO checkout manejado.', [ 'user_id' => $user_id, 'membership_level_id' => $morder->membership_id ] );
    }

    /**
     * Maneja el cambio de estado de una suscripción de WooCommerce.
     *
     * @param WC_Subscription $subscription La suscripción.
     * @param string          $new_status   Nuevo estado.
     * @param string          $old_status   Estado anterior.
     */
    public function handle_subscription_status_change( $subscription, $new_status, $old_status ) {
        if ( ! $this->settings->get_setting( 'enable_sync' ) ) {
            $this->logger->info( 'Sincronización deshabilitada. Cambio de estado de suscripción omitido.', [ 'subscription_id' => $subscription->get_id() ] );
            return;
        }

        $user_id = $subscription->get_user_id();
        $subscription_id = $subscription->get_id();
        $this->logger->info( "Cambio de estado de suscripción WooCommerce: #{$subscription_id} de {$old_status} a {$new_status}.", [ 'user_id' => $user_id, 'subscription_id' => $subscription_id ] );

        // TODO: Lógica para actualizar el nivel de membresía PMPRO del usuario
        // basado en el nuevo estado de la suscripción.
        // Por ejemplo:
        // - Si $new_status es 'active', asegurarse de que el usuario tenga el nivel de PMPRO correspondiente.
        // - Si $new_status es 'cancelled' o 'expired', degradar o quitar el nivel de PMPRO.
    }

    /**
     * Sincroniza el usuario cuando su perfil es actualizado (ej., cambios de rol, etc.).
     *
     * @param int $user_id ID del usuario.
     * @param WP_User $old_user_data Objeto WP_User con los datos anteriores.
     */
    public function sync_user_on_profile_update( $user_id, $old_user_data ) {
        if ( ! $this->settings->get_setting( 'enable_sync' ) ) {
            return;
        }

        $this->logger->debug( "Sincronizando usuario en actualización de perfil: {$user_id}", [ 'user_id' => $user_id ] );
        // TODO: Lógica para verificar y sincronizar el estado de membresía PMPRO
        // con las suscripciones de WooCommerce activas del usuario.
    }

    /******************/
    // TODO: Añadir más métodos para manejar otros hooks de PMPRO y WooCommerce según sea necesario.
    // Ej: woocommerce_new_order, pmpro_membership_level_changed, etc.
    /******************/

    /**
     * Maneja la cancelación de una membresía de PMPro.
     * Si la membresía fue adquirida vía WooCommerce y un gateway externo,
     * intenta cancelar también la suscripción en el gateway.
     *
     * @param int $level_id     ID del nivel de membresía (el nuevo nivel).
     * @param int $user_id      ID del usuario afectado.
     * @param int $cancel_level ID del nivel que se está cancelando (0 si es una cancelación total).
     */
    public function handle_pmpro_cancellation_from_pmpro( $level_id, $user_id, $cancel_level ) {
        // Solo actuar si el nivel anterior ha sido "cancelado" (cambiado a 0 o un nivel inferior de "cancelación")
        // O si el user_id ya no tiene el nivel $level_id.
        // Se asume que $cancel_level !== 0 indica que es una cancelación del nivel actual.
        // Ojo: pmpro_after_change_membership_level se dispara para CUALQUIER cambio de nivel.
        // Necesitamos asegurar que el usuario *realmente* ya no tiene el nivel que tenía, o que su nivel es 0.
        $old_level = pmpro_getMembershipLevelForUser( $user_id, true ); // Obtiene el nivel antes del cambio si es posible.

        // Si el usuario tenía un nivel y ahora no tiene ninguno (o el nuevo nivel es 0/no válido).
        // Y si el nivel que se intenta cancelar es el nivel activo del usuario antes del cambio.
        if ( ! empty( $old_level ) && ( 0 === (int) $level_id || (isset($cancel_level) && 0 === (int) $cancel_level ) ) ) {

            $this->logger->info(
                sprintf( 'PMPro nivel de membresía para el usuario #%d ha cambiado. Detectando posible cancelación. Nivel anterior: %s, Nuevo nivel: %s',
                    $user_id,
                    $old_level->name,
                    ( (int) $level_id === 0 ) ? 'Ninguno' : pmpro_getLevel( $level_id )->name
                ),
                [ 'user_id' => $user_id, 'old_level_id' => $old_level->id, 'new_level_id' => $level_id, 'cancel_level_param' => $cancel_level ]
            );

            // Buscar la suscripción de WooCommerce activa relacionada con el usuario y su nivel anterior.
            // Esto es crucial y puede requerir lógica para encontrar la suscripción *correcta* si un usuario tiene varias.
            // La función pmprowoo_get_related_subscription() de tu propuesta debe implementarse.
            $subscription = $this->get_related_woocommerce_subscription( $user_id, $old_level->id );

            if ( $subscription && $subscription->has_status( [ 'active', 'pending-cancel' ] ) ) { // Incluir 'pending-cancel' si WooCommerce ya está en proceso.
                $gateway_id = $subscription->get_payment_method();

                // Solo proceder si el gateway es uno que necesita ser notificado.
                // Los gateways "manual" o "cheque" no requieren notificación externa.
                $gateways_to_notify = apply_filters( 'pmpro_woo_sync_gateways_to_notify_on_pmpro_cancel', [ 'pagbank', 'stripe', 'paypal' ] ); // Añade aquí los IDs de los gateways.

                if ( in_array( $gateway_id, $gateways_to_notify ) ) {
                    $this->logger->info(
                        sprintf( 'Detectada cancelación de PMPro para usuario #%d. Intentando cancelar suscripción de WooCommerce #%d en el gateway %s.',
                            $user_id,
                            $subscription->get_id(),
                            $gateway_id
                        ),
                        [ 'user_id' => $user_id, 'subscription_id' => $subscription->get_id(), 'gateway' => $gateway_id ]
                    );

                    // Aquí, el Gateway Manager toma el control.
                    $cancellation_result = $this->gateway_manager->cancel_subscription_at_gateway( $subscription, $gateway_id );

                    if ( is_wp_error( $cancellation_result ) ) {
                        $this->logger->error(
                            sprintf( 'Error al cancelar suscripción #%d en el gateway %s: %s',
                                $subscription->get_id(),
                                $gateway_id,
                                $cancellation_result->get_error_message()
                            ),
                            [ 'user_id' => $user_id, 'subscription_id' => $subscription->get_id(), 'gateway' => $gateway_id, 'error' => $cancellation_result->get_error_data() ]
                        );
                        // TODO: Considerar notificar al administrador.
                    } else {
                        $this->logger->info(
                            sprintf( 'Suscripción #%d cancelada exitosamente en el gateway %s.',
                                $subscription->get_id(),
                                $gateway_id
                            ),
                            [ 'user_id' => $user_id, 'subscription_id' => $subscription->get_id(), 'gateway' => $gateway_id ]
                        );
                        // TODO: Opcional: Actualizar el estado de la suscripción de WooCommerce a 'cancelled' aquí si el gateway no lo hace vía webhook.
                        // Usualmente el gateway envía un webhook que manejaría esto automáticamente.
                    }
                } else {
                    $this->logger->info(
                        sprintf( 'Membresía PMPro de usuario #%d cancelada, pero el gateway %s no requiere notificación externa para suscripción #%d.',
                            $user_id,
                            $gateway_id,
                            $subscription->get_id()
                        ),
                        [ 'user_id' => $user_id, 'subscription_id' => $subscription->get_id(), 'gateway' => $gateway_id ]
                    );
                }
            } else {
                $this->logger->info(
                    sprintf( 'No se encontró una suscripción de WooCommerce activa y vinculada para el usuario #%d (nivel PMPro #%d) para cancelar en el gateway.',
                        $user_id,
                        $old_level->id
                    ),
                    [ 'user_id' => $user_id, 'old_level_id' => $old_level->id ]
                );
            }
        }
    }

    /**
     * Busca y retorna la suscripción de WooCommerce relacionada con un usuario y un nivel de membresía PMPro.
     * Esta es una función crítica que debe implementarse cuidadosamente.
     * Un usuario puede tener varias suscripciones; necesitas lógica para determinar cuál cancelar.
     *
     * @param int $user_id         ID del usuario.
     * @param int $pmpro_level_id  ID del nivel de membresía de PMPro que se está buscando.
     * @return WC_Subscription|false La suscripción de WooCommerce o false si no se encuentra.
     */
    protected function get_related_woocommerce_subscription( $user_id, $pmpro_level_id = 0 ) {
        // TODO: Implementar lógica para encontrar la suscripción correcta.
        // Esto podría implicar:
        // 1. Buscar todas las suscripciones activas del usuario.
        // 2. Comprobar los metadatos de esas suscripciones para un "meta_key" que las vincule al ID del nivel de PMPro.
        //    (e.g., '_pmpro_level_id_linked_to_subscription') que deberías guardar cuando se crea la suscripción.
        // 3. O, si es una integración 1:1, simplemente devolver la suscripción activa más reciente.

        // Ejemplo básico (necesita ser robusto para producción):
        if ( ! class_exists( 'WC_Subscriptions' ) ) {
            return false;
        }

        $subscriptions = wcs_get_users_subscriptions( $user_id );

        foreach ( $subscriptions as $subscription ) {
            // Un ejemplo de cómo podrías vincular una suscripción a un nivel PMPro.
            // Deberías asegurarte de que este meta se guarda cuando se crea la suscripción inicialmente.
            $linked_pmpro_level_id = $subscription->get_meta( '_pmpro_linked_level_id' );

            if ( $linked_pmpro_level_id == $pmpro_level_id && $subscription->has_status( [ 'active', 'on-hold', 'pending-cancel' ] ) ) {
                return $subscription;
            }
        }
        return false;
    }
}
