<?php
/**
 * Clase para gestionar las operaciones con diferentes gateways de pago.
 * Proporciona una interfaz unificada para interactuar con múltiples gateways.
 */
class PMPro_Woo_Sync_Gateway_Manager {

    /**
     * Instancia de configuraciones.
     *
     * @var PMPro_Woo_Sync_Settings
     */
    protected $settings;

    /**
     * Instancia del logger.
     *
     * @var PMPro_Woo_Sync_Logger
     */
    protected $logger;

    /**
     * Registro de gateways soportados.
     *
     * @var array
     */
    private $supported_gateways = array(
        'pagbank' => array(
            'name' => 'PagBank',
            'class' => 'PMPro_Woo_Sync_PagBank_API',
            'file' => 'includes/gateways/class-pmpro-woo-sync-pagbank-api.php',
        ),
        // TODO: Agregar más gateways aquí
        // 'stripe' => array(
        //     'name' => 'Stripe',
        //     'class' => 'PMPro_Woo_Sync_Stripe_API',
        //     'file' => 'includes/gateways/class-pmpro-woo-sync-stripe-api.php',
        // ),
    );

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
     * @param string          $gateway_id   El ID del gateway de pago.
     * @return true|WP_Error True si la cancelación fue exitosa, WP_Error en caso contrario.
     */
    public function cancel_subscription_at_gateway( WC_Subscription $subscription, $gateway_id ) {
        // Validación de entrada
        if ( ! $subscription || ! $subscription->get_id() ) {
            $error = new WP_Error( 
                'pmpro_woo_sync_invalid_subscription', 
                __( 'Suscripción inválida proporcionada.', 'pmpro-woo-sync' ) 
            );
            $this->logger->log( 'error', 'Gateway Manager: ' . $error->get_error_message() );
            return $error;
        }

        if ( empty( $gateway_id ) ) {
            $error = new WP_Error( 
                'pmpro_woo_sync_invalid_gateway', 
                __( 'ID de gateway inválido.', 'pmpro-woo-sync' ) 
            );
            $this->logger->log( 'error', 'Gateway Manager: ' . $error->get_error_message() );
            return $error;
        }

        // Log del intento
        $this->logger->log( 'info', sprintf( 
            'Intentando cancelar suscripción #%d en gateway %s', 
            $subscription->get_id(), 
            $gateway_id 
        ));

        // Verificar si el gateway está soportado
        if ( ! $this->is_gateway_supported( $gateway_id ) ) {
            $error = new WP_Error(
                'pmpro_woo_sync_unsupported_gateway',
                sprintf( 
                    __( 'El gateway de pago "%s" no está soportado para cancelación remota.', 'pmpro-woo-sync' ), 
                    $gateway_id 
                )
            );
            $this->logger->log( 'warning', 'Gateway Manager: ' . $error->get_error_message() );
            return $error;
        }

        // Cargar y ejecutar el gateway específico
        $gateway_api = $this->load_gateway_api( $gateway_id );
        if ( is_wp_error( $gateway_api ) ) {
            return $gateway_api;
        }

        // Verificar que el método existe
        if ( ! method_exists( $gateway_api, 'cancel_subscription' ) ) {
            $error = new WP_Error(
                'pmpro_woo_sync_method_not_found',
                sprintf( 
                    __( 'El método cancel_subscription no está implementado para el gateway %s.', 'pmpro-woo-sync' ), 
                    $gateway_id 
                )
            );
            $this->logger->log( 'error', 'Gateway Manager: ' . $error->get_error_message() );
            return $error;
        }

        // Ejecutar la cancelación
        try {
            $result = $gateway_api->cancel_subscription( $subscription );
            
            if ( is_wp_error( $result ) ) {
                $this->logger->log( 'error', sprintf(
                    'Falló la cancelación en gateway %s para suscripción #%d: %s',
                    $gateway_id,
                    $subscription->get_id(),
                    $result->get_error_message()
                ));
            } else {
                $this->logger->log( 'success', sprintf(
                    'Cancelación exitosa en gateway %s para suscripción #%d',
                    $gateway_id,
                    $subscription->get_id()
                ));
            }
            
            return $result;
            
        } catch ( Exception $e ) {
            $error = new WP_Error(
                'pmpro_woo_sync_gateway_exception',
                sprintf( 
                    __( 'Excepción en gateway %s: %s', 'pmpro-woo-sync' ), 
                    $gateway_id, 
                    $e->getMessage() 
                )
            );
            $this->logger->log( 'error', 'Gateway Manager: ' . $error->get_error_message() );
            return $error;
        }
    }

    /**
     * Verifica si un gateway está soportado.
     *
     * @param string $gateway_id ID del gateway.
     * @return bool
     */
    public function is_gateway_supported( $gateway_id ) {
        return isset( $this->supported_gateways[ $gateway_id ] );
    }

    /**
     * Obtiene la lista de gateways soportados.
     *
     * @return array
     */
    public function get_supported_gateways() {
        return $this->supported_gateways;
    }

    /**
     * Carga la API del gateway específico.
     *
     * @param string $gateway_id ID del gateway.
     * @return object|WP_Error Instancia de la API o error.
     */
    private function load_gateway_api( $gateway_id ) {
        $gateway_config = $this->supported_gateways[ $gateway_id ];
        $file_path = PMPRO_WOO_SYNC_PATH . $gateway_config['file'];
        
        // Verificar que el archivo existe
        if ( ! file_exists( $file_path ) ) {
            $error = new WP_Error(
                'pmpro_woo_sync_gateway_file_not_found',
                sprintf( 
                    __( 'Archivo de gateway no encontrado: %s', 'pmpro-woo-sync' ), 
                    $file_path 
                )
            );
            $this->logger->log( 'error', 'Gateway Manager: ' . $error->get_error_message() );
            return $error;
        }
        
        // Cargar el archivo
        require_once $file_path;
        
        // Verificar que la clase existe
        $class_name = $gateway_config['class'];
        if ( ! class_exists( $class_name ) ) {
            $error = new WP_Error(
                'pmpro_woo_sync_gateway_class_not_found',
                sprintf( 
                    __( 'Clase de gateway no encontrada: %s', 'pmpro-woo-sync' ), 
                    $class_name 
                )
            );
            $this->logger->log( 'error', 'Gateway Manager: ' . $error->get_error_message() );
            return $error;
        }
        
        // Instanciar la clase
        return new $class_name( $this->settings, $this->logger );
    }

    /**
     * Registra un nuevo gateway dinámicamente.
     *
     * @param string $gateway_id   ID único del gateway.
     * @param array  $gateway_config Configuración del gateway.
     * @return bool
     */
    public function register_gateway( $gateway_id, $gateway_config ) {
        $required_keys = array( 'name', 'class', 'file' );
        
        foreach ( $required_keys as $key ) {
            if ( ! isset( $gateway_config[ $key ] ) ) {
                $this->logger->log( 'error', sprintf(
                    'Configuración de gateway inválida para %s: falta la clave %s',
                    $gateway_id,
                    $key
                ));
                return false;
            }
        }
        
        $this->supported_gateways[ $gateway_id ] = $gateway_config;
        $this->logger->log( 'info', sprintf( 'Gateway %s registrado exitosamente', $gateway_id ) );
        
        return true;
    }

    // TODO: Métodos adicionales para otras operaciones de gateway
    // public function get_subscription_status( WC_Subscription $subscription, $gateway_id ) {}
    // public function process_refund( WC_Order $order, $amount, $gateway_id ) {}
    // public function update_payment_method( WC_Subscription $subscription, $gateway_id ) {}
}
