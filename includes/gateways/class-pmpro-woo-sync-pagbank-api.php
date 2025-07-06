<?php
/**
 * Clase para la integración con la API de PagBank.
 */
class PMPro_Woo_Sync_PagBank_API {

    private $api_url;
    private $api_key;
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

        $pagbank_settings = $this->settings->get_setting( 'pagbank_api_settings', [] );
        $this->api_key = isset( $pagbank_settings['api_key'] ) ? $pagbank_settings['api_key'] : '';
        $mode = isset( $pagbank_settings['mode'] ) ? $pagbank_settings['mode'] : 'live'; // 'live' o 'sandbox'

        // Define la URL de la API según el modo.
        $this->api_url = ( 'sandbox' === $mode ) ? 'https://sandbox.api.pagbank.com.br/v1/' : 'https://api.pagbank.com.br/v1/';

        // Asegúrate de que la API Key esté disponible.
        if ( empty( $this->api_key ) ) {
            $this->logger->error( 'PagBank API Key no configurada. Las operaciones con PagBank fallarán.', [], __METHOD__ );
        }
    }

    /**
     * Realiza una solicitud HTTP a la API de PagBank.
     *
     * @param string $endpoint El endpoint de la API (ej. 'subscriptions/ID/cancel').
     * @param string $method   Método HTTP (GET, POST, PUT, DELETE).
     * @param array  $data     Datos a enviar en el cuerpo de la solicitud para POST/PUT.
     * @return array|WP_Error La respuesta decodificada de la API o un objeto WP_Error.
     */
    protected function make_request( $endpoint, $method = 'GET', $data = [] ) {
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'method'  => $method,
            'timeout' => 30, // Tiempo de espera en segundos.
        ];

        if ( ! empty( $data ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        $url = $this->api_url . ltrim( $endpoint, '/' ); // Asegura que no haya doble barra.
        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            $this->logger->error(
                sprintf( 'Error de conexión a la API de PagBank para el endpoint "%s": %s', $endpoint, $response->get_error_message() ),
                [ 'endpoint' => $endpoint, 'method' => $method, 'data' => $data, 'error' => $response->get_error_data() ],
                __METHOD__
            );
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $http_code = wp_remote_retrieve_response_code( $response );
        $decoded_body = json_decode( $body, true );

        if ( $http_code >= 400 ) {
            $error_message = isset( $decoded_body['message'] ) ? $decoded_body['message'] : 'Error desconocido en la API de PagBank.';
            $this->logger->error(
                sprintf( 'Respuesta de error de la API de PagBank para el endpoint "%s" (HTTP %d): %s', $endpoint, $http_code, $error_message ),
                [ 'endpoint' => $endpoint, 'method' => $method, 'data' => $data, 'http_code' => $http_code, 'response_body' => $decoded_body ],
                __METHOD__
            );
            return new WP_Error( 'pagbank_api_error', $error_message, [ 'http_code' => $http_code, 'response' => $decoded_body ] );
        }

        return $decoded_body;
    }

    /**
     * Cancela una suscripción específica en PagBank.
     *
     * @param WC_Subscription $subscription La suscripción de WooCommerce.
     * @return true|WP_Error True si la cancelación fue exitosa, WP_Error en caso contrario.
     */
    public function cancel_subscription( WC_Subscription $subscription ) {
        $pagbank_subscription_id = $subscription->get_meta( '_pagbank_subscription_id' );

        if ( empty( $pagbank_subscription_id ) ) {
            $this->logger->warning(
                sprintf( 'No se encontró un ID de suscripción de PagBank para la suscripción de WooCommerce #%d. No se puede cancelar en PagBank.', $subscription->get_id() ),
                [ 'subscription_id' => $subscription->get_id() ],
                __METHOD__
            );
            return new WP_Error( 'pagbank_no_subscription_id', 'No se encontró el ID de suscripción de PagBank.' );
        }

        $this->logger->debug(
            sprintf( 'Intentando cancelar suscripción de PagBank "%s" para WC_Subscription #%d.', $pagbank_subscription_id, $subscription->get_id() ),
            [ 'wc_subscription_id' => $subscription->get_id(), 'pagbank_id' => $pagbank_subscription_id ],
            __METHOD__
        );

        $response = $this->make_request(
            "subscriptions/{$pagbank_subscription_id}/cancel",
            'POST' // Según la API de PagBank, para cancelar es POST a /cancel
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // La API de PagBank generalmente retorna el estado de la suscripción después de la cancelación.
        // Asumimos que un estado 'CANCELED' (o similar) en la respuesta significa éxito.
        if ( isset( $response['status'] ) && 'CANCELED' === strtoupper( $response['status'] ) ) { // Verifica el campo de estado que PagBank devuelve.
            $this->logger->info(
                sprintf( 'Suscripción de PagBank "%s" (WC_Subscription #%d) cancelada exitosamente.', $pagbank_subscription_id, $subscription->get_id() ),
                [ 'wc_subscription_id' => $subscription->get_id(), 'pagbank_id' => $pagbank_subscription_id, 'response' => $response ],
                __METHOD__
            );
            // Opcional: Actualizar meta si es necesario.
            // $subscription->update_meta_data( '_pagbank_status', 'cancelled' );
            // $subscription->save();
            return true;
        } else {
            $error_message = isset( $response['message'] ) ? $response['message'] : 'Respuesta inesperada al cancelar en PagBank.';
            $this->logger->error(
                sprintf( 'Fallo al cancelar suscripción de PagBank "%s" (WC_Subscription #%d): %s', $pagbank_subscription_id, $subscription->get_id(), $error_message ),
                [ 'wc_subscription_id' => $subscription->get_id(), 'pagbank_id' => $pagbank_subscription_id, 'response' => $response ],
                __METHOD__
            );
            return new WP_Error( 'pagbank_cancellation_failed', $error_message, $response );
        }
    }

    /**
     * Obtiene el estado de una suscripción de PagBank.
     * Útil para verificar el estado si el webhook no llega o para reintentos.
     *
     * @param string $pagbank_subscription_id El ID de la suscripción en PagBank.
     * @return array|WP_Error La información de la suscripción o un objeto WP_Error.
     */
    public function get_subscription_status( $pagbank_subscription_id ) {
        return $this->make_request( "subscriptions/{$pagbank_subscription_id}" );
    }

    // TODO: Puedes añadir métodos para la activación, suspensión, etc., si los necesitas en el futuro.
}
