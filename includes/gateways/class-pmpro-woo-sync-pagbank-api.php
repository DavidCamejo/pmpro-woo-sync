<?php
/**
 * Clase para la integración con la API de PagBank.
 * Maneja todas las operaciones relacionadas con suscripciones y pagos en PagBank.
 */
class PMPro_Woo_Sync_PagBank_API {

    /**
     * URL base de la API.
     * @var string
     */
    private $api_url;

    /**
     * API Key de PagBank.
     * @var string
     */
    private $api_key;

    /**
     * Instancia de configuraciones.
     * @var PMPro_Woo_Sync_Settings
     */
    protected $settings;

    /**
     * Instancia del logger.
     * @var PMPro_Woo_Sync_Logger
     */
    protected $logger;

    /**
     * Timeout para requests HTTP.
     * @var int
     */
    private $timeout;
    
    /**
     * Número máximo de reintentos.
     * @var int
     */
    private $max_retries;

    /**
     * Constructor.
     *
     * @param PMPro_Woo_Sync_Settings $settings Instancia de la clase de ajustes.
     * @param PMPro_Woo_Sync_Logger   $logger   Instancia de la clase de logs.
     */
    public function __construct( PMPro_Woo_Sync_Settings $settings, PMPro_Woo_Sync_Logger $logger ) {
        $this->settings = $settings;
        $this->logger   = $logger;

        // Cargar configuraciones de PagBank
        $this->load_pagbank_settings();
        
        // Configuraciones adicionales
        $this->timeout = $this->settings->get_setting( 'api_timeout', 30 );
        $this->max_retries = $this->settings->get_setting( 'retry_attempts', 3 );
        
        // Validar configuración crítica
        $this->validate_configuration();
    }

    /**
     * Carga las configuraciones específicas de PagBank.
     */
    private function load_pagbank_settings() {
        $pagbank_settings = $this->settings->get_setting( 'pagbank_api_settings', array() );
        
        $this->api_key = $pagbank_settings['api_key'] ?? '';
        $mode = $pagbank_settings['mode'] ?? 'live';

        // URLs de API según el ambiente
        $api_urls = array(
            'sandbox' => 'https://sandbox.api.pagbank.com.br/v1/',
            'live'    => 'https://api.pagbank.com.br/v1/',
        );

        $this->api_url = $api_urls[ $mode ] ?? $api_urls['live'];
        
        $this->logger->debug(
            "Configuración PagBank cargada",
            array( 
                'mode' => $mode,
                'api_url' => $this->api_url,
                'has_api_key' => ! empty( $this->api_key )
            ),
            __METHOD__
        );
    }

    /**
     * Valida la configuración crítica.
     */
    private function validate_configuration() {
        if ( empty( $this->api_key ) ) {
            $this->logger->error( 
                'PagBank API Key no configurada. Las operaciones con PagBank fallarán.',
                array(),
                __METHOD__
            );
        } elseif ( strlen( $this->api_key ) < 20 ) {
            $this->logger->warning(
                'PagBank API Key parece tener formato inválido',
                array( 'api_key_length' => strlen( $this->api_key ) ),
                __METHOD__
            );
        }
    }

    /**
     * Realiza una solicitud HTTP a la API de PagBank con reintentos automáticos.
     *
     * @param string $endpoint El endpoint de la API.
     * @param string $method   Método HTTP (GET, POST, PUT, DELETE).
     * @param array  $data     Datos a enviar en el cuerpo.
     * @param int    $retry_count Contador interno de reintentos.
     * @return array|WP_Error La respuesta decodificada o error.
     */
    protected function make_request( $endpoint, $method = 'GET', $data = array(), $retry_count = 0 ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error(
                'pagbank_no_api_key',
                __( 'API Key de PagBank no configurada.', 'pmpro-woo-sync' )
            );
        }

        // Preparar argumentos del request
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'User-Agent'    => 'PMPRO-Woo-Sync/' . PMPRO_WOO_SYNC_VERSION,
            ),
            'method'  => strtoupper( $method ),
            'timeout' => $this->timeout,
        );

        if ( ! empty( $data ) ) {
            $args['body'] = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        }

        $url = $this->api_url . ltrim( $endpoint, '/' );
        
        $this->logger->debug(
            "Realizando request a PagBank API",
            array( 
                'url' => $url,
                'method' => $method,
                'retry_attempt' => $retry_count,
                'has_data' => ! empty( $data )
            ),
            __METHOD__
        );

        $response = wp_remote_request( $url, $args );

        // Manejo de errores de conexión
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            
            $this->logger->error(
                "Error de conexión a PagBank API: {$error_message}",
                array( 
                    'endpoint' => $endpoint,
                    'method' => $method,
                    'retry_attempt' => $retry_count,
                    'error_code' => $response->get_error_code()
                ),
                __METHOD__
            );

            // Reintentar si es apropiado
            if ( $this->should_retry( $response, $retry_count ) ) {
                return $this->retry_request( $endpoint, $method, $data, $retry_count );
            }

            return $response;
        }

        // Procesar respuesta HTTP
        return $this->process_response( $response, $endpoint, $method, $data, $retry_count );
    }

    /**
     * Procesa la respuesta HTTP de la API.
     *
     * @param array  $response     Respuesta de wp_remote_request.
     * @param string $endpoint     Endpoint llamado.
     * @param string $method       Método HTTP usado.
     * @param array  $data         Datos enviados.
     * @param int    $retry_count  Número de reintentos.
     * @return array|WP_Error
     */
    private function process_response( $response, $endpoint, $method, $data, $retry_count ) {
        $body = wp_remote_retrieve_body( $response );
        $http_code = wp_remote_retrieve_response_code( $response );
        $decoded_body = json_decode( $body, true );

        // Log de respuesta exitosa
        if ( $http_code >= 200 && $http_code < 300 ) {
            $this->logger->debug(
                "Respuesta exitosa de PagBank API",
                array( 
                    'endpoint' => $endpoint,
                    'http_code' => $http_code,
                    'response_size' => strlen( $body )
                ),
                __METHOD__
            );
            
            return $decoded_body;
        }

        // Manejo de errores HTTP
        $error_message = $this->extract_error_message( $decoded_body, $http_code );
        
        $this->logger->error(
            "Error HTTP de PagBank API: {$error_message}",
            array( 
                'endpoint' => $endpoint,
                'method' => $method,
                'http_code' => $http_code,
                'response_body' => $decoded_body,
                'retry_attempt' => $retry_count
            ),
            __METHOD__
        );

        // Decidir si reintentar
        if ( $this->should_retry_http_error( $http_code, $retry_count ) ) {
            return $this->retry_request( $endpoint, $method, $data, $retry_count );
        }

        return new WP_Error(
            'pagbank_api_error',
            $error_message,
            array( 
                'http_code' => $http_code,
                'response' => $decoded_body
            )
        );
    }

    /**
     * Extrae mensaje de error de la respuesta de la API.
     *
     * @param array $decoded_body Cuerpo decodificado de la respuesta.
     * @param int   $http_code    Código HTTP.
     * @return string
     */
    private function extract_error_message( $decoded_body, $http_code ) {
        // PagBank puede devolver errores en diferentes formatos
        if ( isset( $decoded_body['message'] ) ) {
            return $decoded_body['message'];
        }
        
        if ( isset( $decoded_body['error']['message'] ) ) {
            return $decoded_body['error']['message'];
        }
        
        if ( isset( $decoded_body['errors'] ) && is_array( $decoded_body['errors'] ) ) {
            $messages = array_column( $decoded_body['errors'], 'message' );
            return implode( '; ', $messages );
        }

        // Mensaje por defecto basado en código HTTP
        $default_messages = array(
            400 => __( 'Solicitud inválida', 'pmpro-woo-sync' ),
            401 => __( 'No autorizado - verifique su API Key', 'pmpro-woo-sync' ),
            403 => __( 'Prohibido - sin permisos suficientes', 'pmpro-woo-sync' ),
            404 => __( 'Recurso no encontrado', 'pmpro-woo-sync' ),
            429 => __( 'Límite de rate excedido', 'pmpro-woo-sync' ),
            500 => __( 'Error interno del servidor de PagBank', 'pmpro-woo-sync' ),
        );

        return $default_messages[ $http_code ] ?? __( 'Error desconocido en la API de PagBank', 'pmpro-woo-sync' );
    }

    /**
     * Determina si se debe reintentar una solicitud.
     *
     * @param WP_Error|array $response     Respuesta del request.
     * @param int            $retry_count  Número actual de reintentos.
     * @return bool
     */
    private function should_retry( $response, $retry_count ) {
        if ( $retry_count >= $this->max_retries ) {
            return false;
        }

        // Reintentar en errores de conexión temporal
        if ( is_wp_error( $response ) ) {
            $error_code = $response->get_error_code();
            $retryable_errors = array( 'http_request_failed', 'http_request_timeout' );
            
            return in_array( $error_code, $retryable_errors, true );
        }

        return false;
    }

    /**
     * Determina si se debe reintentar en base al código HTTP.
     *
     * @param int $http_code   Código HTTP de respuesta.
     * @param int $retry_count Número actual de reintentos.
     * @return bool
     */
    private function should_retry_http_error( $http_code, $retry_count ) {
        if ( $retry_count >= $this->max_retries ) {
            return false;
        }

        // Reintentar en errores temporales del servidor
        $retryable_codes = array( 429, 500, 502, 503, 504 );
        
        return in_array( $http_code, $retryable_codes, true );
    }

    /**
     * Ejecuta un reintento con delay progresivo.
     *
     * @param string $endpoint     Endpoint a llamar.
     * @param string $method       Método HTTP.
     * @param array  $data         Datos a enviar.
     * @param int    $retry_count  Número actual de reintentos.
     * @return array|WP_Error
     */
    private function retry_request( $endpoint, $method, $data, $retry_count ) {
        $retry_count++;
        
        // Delay progresivo: 1s, 2s, 4s, etc.
        $delay = pow( 2, $retry_count - 1 );
        
        $this->logger->info(
            "Reintentando request a PagBank API en {$delay} segundos",
            array( 
                'endpoint' => $endpoint,
                'retry_attempt' => $retry_count,
                'delay_seconds' => $delay
            ),
            __METHOD__
        );

        sleep( $delay );
        
        return $this->make_request( $endpoint, $method, $data, $retry_count );
    }

    /**
     * Cancela una suscripción específica en PagBank.
     *
     * @param WC_Subscription $subscription La suscripción de WooCommerce.
     * @return true|WP_Error True si la cancelación fue exitosa, WP_Error en caso contrario.
     */
    public function cancel_subscription( WC_Subscription $subscription ) {
        // Validar entrada
        if ( ! $subscription || ! $subscription->get_id() ) {
            return new WP_Error(
                'pagbank_invalid_subscription',
                __( 'Suscripción inválida proporcionada', 'pmpro-woo-sync' )
            );
        }

        $pagbank_subscription_id = $subscription->get_meta( '_pagbank_subscription_id' );

        if ( empty( $pagbank_subscription_id ) ) {
            $this->logger->warning(
                "No se encontró ID de suscripción PagBank para WC_Subscription #{$subscription->get_id()}",
                array( 'wc_subscription_id' => $subscription->get_id() ),
                __METHOD__
            );
            
            return new WP_Error(
                'pagbank_no_subscription_id',
                __( 'No se encontró el ID de suscripción de PagBank', 'pmpro-woo-sync' )
            );
        }

        $this->logger->info(
            "Iniciando cancelación de suscripción PagBank: {$pagbank_subscription_id}",
            array( 
                'wc_subscription_id' => $subscription->get_id(),
                'pagbank_subscription_id' => $pagbank_subscription_id,
                'user_id' => $subscription->get_user_id()
            ),
            __METHOD__
        );

        // Ejecutar cancelación
        $response = $this->make_request(
            "subscriptions/{$pagbank_subscription_id}/cancel",
            'POST',
            array( 'reason' => 'cancelled_by_merchant' ) // Razón opcional
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Validar respuesta de cancelación
        return $this->validate_cancellation_response( $response, $subscription, $pagbank_subscription_id );
    }

    /**
     * Valida la respuesta de cancelación de PagBank.
     *
     * @param array           $response                Respuesta de la API.
     * @param WC_Subscription $subscription           Suscripción de WooCommerce.
     * @param string          $pagbank_subscription_id ID de suscripción en PagBank.
     * @return true|WP_Error
     */
    private function validate_cancellation_response( $response, $subscription, $pagbank_subscription_id ) {
        // Estados que indican cancelación exitosa
        $success_statuses = array( 'CANCELED', 'CANCELLED', 'INACTIVE' );
        
        $status = $response['status'] ?? '';
        
        if ( in_array( strtoupper( $status ), $success_statuses, true ) ) {
            $this->logger->success(
                "Suscripción PagBank cancelada exitosamente: {$pagbank_subscription_id}",
                array( 
                    'wc_subscription_id' => $subscription->get_id(),
                    'pagbank_subscription_id' => $pagbank_subscription_id,
                    'pagbank_status' => $status,
                    'response' => $response
                ),
                __METHOD__
            );

            // Actualizar metadata de la suscripción
            $subscription->update_meta_data( '_pagbank_cancellation_date', gmdate( 'Y-m-d H:i:s' ) );
            $subscription->update_meta_data( '_pagbank_last_status', $status );
            $subscription->save();

            return true;
        }

        // Cancelación no exitosa
        $error_message = sprintf(
            __( 'Respuesta inesperada al cancelar suscripción PagBank. Estado: %s', 'pmpro-woo-sync' ),
            $status
        );

        $this->logger->error(
            "Fallo en cancelación de suscripción PagBank: {$pagbank_subscription_id}",
            array( 
                'wc_subscription_id' => $subscription->get_id(),
                'pagbank_subscription_id' => $pagbank_subscription_id,
                'expected_status' => $success_statuses,
                'received_status' => $status,
                'full_response' => $response
            ),
            __METHOD__
        );

        return new WP_Error(
            'pagbank_cancellation_failed',
            $error_message,
            $response
        );
    }

    /**
     * Obtiene el estado actual de una suscripción de PagBank.
     *
     * @param string $pagbank_subscription_id El ID de la suscripción en PagBank.
     * @return array|WP_Error Información de la suscripción o error.
     */
    public function get_subscription_status( $pagbank_subscription_id ) {
        if ( empty( $pagbank_subscription_id ) ) {
            return new WP_Error(
                'pagbank_invalid_subscription_id',
                __( 'ID de suscripción PagBank inválido', 'pmpro-woo-sync' )
            );
        }

        $this->logger->debug(
            "Consultando estado de suscripción PagBank: {$pagbank_subscription_id}",
            array( 'pagbank_subscription_id' => $pagbank_subscription_id ),
            __METHOD__
        );

        return $this->make_request( "subscriptions/{$pagbank_subscription_id}" );
    }

    /**
     * Pausa una suscripción en PagBank.
     *
     * @param WC_Subscription $subscription    La suscripción de WooCommerce.
     * @param string          $pause_duration  Duración de la pausa (opcional).
     * @return true|WP_Error
     */
    public function pause_subscription( WC_Subscription $subscription, $pause_duration = null ) {
        $pagbank_subscription_id = $subscription->get_meta( '_pagbank_subscription_id' );

        if ( empty( $pagbank_subscription_id ) ) {
            return new WP_Error(
                'pagbank_no_subscription_id',
                __( 'No se encontró el ID de suscripción de PagBank', 'pmpro-woo-sync' )
            );
        }

        $data = array( 'action' => 'pause' );
        
        if ( $pause_duration ) {
            $data['pause_duration'] = $pause_duration;
        }

        $response = $this->make_request(
            "subscriptions/{$pagbank_subscription_id}/pause",
            'POST',
            $data
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $this->logger->success(
            "Suscripción PagBank pausada: {$pagbank_subscription_id}",
            array( 
                'wc_subscription_id' => $subscription->get_id(),
                'pagbank_subscription_id' => $pagbank_subscription_id
            ),
            __METHOD__
        );

        return true;
    }

    /**
     * Reactiva una suscripción pausada en PagBank.
     *
     * @param WC_Subscription $subscription La suscripción de WooCommerce.
     * @return true|WP_Error
     */
    public function resume_subscription( WC_Subscription $subscription ) {
        $pagbank_subscription_id = $subscription->get_meta( '_pagbank_subscription_id' );

        if ( empty( $pagbank_subscription_id ) ) {
            return new WP_Error(
                'pagbank_no_subscription_id',
                __( 'No se encontró el ID de suscripción de PagBank', 'pmpro-woo-sync' )
            );
        }

        $response = $this->make_request(
            "subscriptions/{$pagbank_subscription_id}/resume",
            'POST'
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $this->logger->success(
            "Suscripción PagBank reactivada: {$pagbank_subscription_id}",
            array( 
                'wc_subscription_id' => $subscription->get_id(),
                'pagbank_subscription_id' => $pagbank_subscription_id
            ),
            __METHOD__
        );

        return true;
    }

    /**
     * Verifica la conectividad con la API de PagBank.
     *
     * @return true|WP_Error
     */
    public function test_connection() {
        $this->logger->info( 'Probando conectividad con API de PagBank', array(), __METHOD__ );

        // Hacer una llamada simple para verificar conectividad
        $response = $this->make_request( 'status' ); // Endpoint genérico de estado

        if ( is_wp_error( $response ) ) {
            $this->logger->error(
                'Fallo en test de conectividad con PagBank: ' . $response->get_error_message(),
                array( 'error' => $response->get_error_data() ),
                __METHOD__
            );
            
            return $response;
        }

        $this->logger->success( 'Conectividad con API de PagBank verificada exitosamente', array(), __METHOD__ );
        
        return true;
    }

    /**
     * Obtiene información de la cuenta/merchant en PagBank.
     *
     * @return array|WP_Error
     */
    public function get_merchant_info() {
        return $this->make_request( 'merchant' );
    }

    /**
     * Webhook para manejar notificaciones de PagBank.
     *
     * @param array $webhook_data Datos del webhook.
     * @return bool
     */
    public function handle_webhook( $webhook_data ) {
        $this->logger->info(
            'Procesando webhook de PagBank',
            array( 'webhook_data' => $webhook_data ),
            __METHOD__
        );

        // Validar webhook
        if ( ! $this->validate_webhook( $webhook_data ) ) {
            $this->logger->error( 'Webhook de PagBank inválido', array(), __METHOD__ );
            return false;
        }

        // Procesar según tipo de evento
        $event_type = $webhook_data['event_type'] ?? '';
        
        switch ( $event_type ) {
            case 'subscription.cancelled':
                return $this->handle_subscription_cancelled_webhook( $webhook_data );
                
            case 'subscription.payment_succeeded':
                return $this->handle_subscription_payment_webhook( $webhook_data, 'succeeded' );
                
            case 'subscription.payment_failed':
                return $this->handle_subscription_payment_webhook( $webhook_data, 'failed' );
                
            default:
                $this->logger->info(
                    "Tipo de webhook no manejado: {$event_type}",
                    array( 'event_type' => $event_type ),
                    __METHOD__
                );
                return true;
        }
    }

    /**
     * Valida la autenticidad de un webhook.
     *
     * @param array $webhook_data Datos del webhook.
     * @return bool
     */
    private function validate_webhook( $webhook_data ) {
        // Implementar validación de firma del webhook según documentación de PagBank
        // Por ahora, validación básica
        
        return isset( $webhook_data['event_type'] ) && isset( $webhook_data['data'] );
    }

    /**
     * Maneja webhook de suscripción cancelada.
     *
     * @param array $webhook_data Datos del webhook.
     * @return bool
     */
    private function handle_subscription_cancelled_webhook( $webhook_data ) {
        $pagbank_subscription_id = $webhook_data['data']['subscription_id'] ?? '';
        
        if ( empty( $pagbank_subscription_id ) ) {
            return false;
        }

        // Buscar suscripción WooCommerce relacionada
        $subscription = $this->find_wc_subscription_by_pagbank_id( $pagbank_subscription_id );
        
        if ( $subscription ) {
            $subscription->update_status( 'cancelled', 'Cancelado por webhook de PagBank' );
            
            $this->logger->success(
                "Suscripción WC #{$subscription->get_id()} cancelada por webhook PagBank",
                array( 
                    'wc_subscription_id' => $subscription->get_id(),
                    'pagbank_subscription_id' => $pagbank_subscription_id
                ),
                __METHOD__
            );
        }

        return true;
    }

    /**
     * Maneja webhooks de pagos de suscripción.
     *
     * @param array  $webhook_data Datos del webhook.
     * @param string $status       Estado del pago (succeeded/failed).
     * @return bool
     */
    private function handle_subscription_payment_webhook( $webhook_data, $status ) {
        $pagbank_subscription_id = $webhook_data['data']['subscription_id'] ?? '';
        
        if ( empty( $pagbank_subscription_id ) ) {
            return false;
        }

        $subscription = $this->find_wc_subscription_by_pagbank_id( $pagbank_subscription_id );
        
        if ( $subscription ) {
            if ( 'succeeded' === $status ) {
                // Registrar pago exitoso
                $this->logger->success(
                    "Pago de suscripción exitoso por webhook PagBank",
                    array( 
                        'wc_subscription_id' => $subscription->get_id(),
                        'pagbank_subscription_id' => $pagbank_subscription_id
                    ),
                    __METHOD__
                );
            } else {
                // Registrar pago fallido
                $this->logger->warning(
                    "Pago de suscripción fallido por webhook PagBank",
                    array( 
                        'wc_subscription_id' => $subscription->get_id(),
                        'pagbank_subscription_id' => $pagbank_subscription_id
                    ),
                    __METHOD__
                );
            }
        }

        return true;
    }

    /**
     * Busca una suscripción WooCommerce por ID de PagBank.
     *
     * @param string $pagbank_subscription_id ID de suscripción en PagBank.
     * @return WC_Subscription|false
     */
    private function find_wc_subscription_by_pagbank_id( $pagbank_subscription_id ) {
        if ( ! function_exists( 'wcs_get_subscriptions' ) ) {
            return false;
        }

        $subscriptions = wcs_get_subscriptions( array(
            'meta_query' => array(
                array(
                    'key'   => '_pagbank_subscription_id',
                    'value' => $pagbank_subscription_id,
                )
            ),
            'limit' => 1,
        ));

        return ! empty( $subscriptions ) ? reset( $subscriptions ) : false;
    }
}
