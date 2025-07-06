<?php
/**
 * Clase para gestionar las operaciones con diferentes gateways de pago.
 */
class PMPro_Woo_Sync_Gateway_Manager {

    protected $settings;
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
     * Intenta cancelar una suscripción en el gateway de pago externo.
     *
     * @param WC_Subscription $subscription La suscripción de WooCommerce a cancelar.
     * @param string          $gateway_id   El ID del gateway de pago (ej. 'pagbank').
     * @return true|WP_Error True si la cancelación fue exitosa, WP_Error en caso contrario.
     */
    public function cancel_subscription_at_gateway( WC_Subscription $subscription, $gateway_id ) {
        switch ( $gateway_id ) {
            case 'pagbank':
                // Carga la clase de la API de PagBank.
                // Asegúrate de que esta clase sea accesible, idealmente en un subdirectorio como includes/gateways/pagbank/.
                require_once PMPRO_WOO_SYNC_PATH . 'includes/gateways/class-pmpro-woo-sync-pagbank-api.php';
                $pagbank_api = new PMPro_Woo_Sync_PagBank_API( $this->settings, $this->logger );
                return $pagbank_api->cancel_subscription( $subscription );
            // TODO: Añadir casos para otros gateways (Stripe, PayPal, etc.)
            // case 'stripe':
            //     // Lógica para Stripe
            //     break;
            default:
                return new WP_Error(
                    'pmpro_woo_sync_unsupported_gateway',
                    sprintf( 'El gateway de pago "%s" no está soportado para la cancelación remota.', $gateway_id )
                );
        }
    }

    // TODO: Puedes añadir más métodos aquí para otras interacciones con gateways
    // (ej. obtener estado de suscripción, reembolsar, etc.)
}
