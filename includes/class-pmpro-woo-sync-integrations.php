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
     * Constructor.
     *
     * @param PMPro_Woo_Sync_Settings $settings Instancia de la clase de ajustes.
     * @param PMPro_Woo_Sync_Logger   $logger   Instancia de la clase de logs.
     */
    public function __construct( PMPro_Woo_Sync_Settings $settings, PMPro_Woo_Sync_Logger $logger ) {
        $this->settings = $settings;
        $this->logger   = $logger;
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

    // TODO: Añadir más métodos para manejar otros hooks de PMPRO y WooCommerce según sea necesario.
    // Ej: woocommerce_new_order, pmpro_membership_level_changed, etc.
}
