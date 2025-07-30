<?php
/**
 * Clase principal del plugin PMPRO-WooCommerce Sync.
 * Orquesta la carga de otras clases y maneja los hooks principales.
 */
class PMPro_Woo_Sync {

    /**
     * Instancia de la clase PMPro_Woo_Sync_Admin.
     *
     * @var PMPro_Woo_Sync_Admin
     */
    protected $admin;

    /**
     * Instancia de la clase PMPro_Woo_Sync_Settings.
     *
     * @var PMPro_Woo_Sync_Settings
     */
    protected $settings;

    /**
     * Instancia de la clase PMPro_Woo_Sync_Logger.
     *
     * @var PMPro_Woo_Sync_Logger
     */
    protected $logger;

    /**
     * Instancia de la clase PMPro_Woo_Sync_Integrations.
     *
     * @var PMPro_Woo_Sync_Integrations
     */
    protected $integrations;

    /**
     * Constructor de la clase.
     * Carga las dependencias y define los hooks.
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Carga las dependencias del plugin.
     * Instancia las clases necesarias.
     *
     * @since 1.0.0
     * @access private
     */
    private function load_dependencies() {
        // Inicializa el sistema de logs.
        $this->logger = new PMPro_Woo_Sync_Logger();

        // Inicializa la gestión de ajustes.
        $this->settings = new PMPro_Woo_Sync_Settings( $this->logger );

        // Inicializa la interfaz de administración.
        $this->admin = new PMPro_Woo_Sync_Admin( $this->settings, $this->logger );

        // Inicializa la lógica de integración.
        $this->integrations = new PMPro_Woo_Sync_Integrations( $this->settings, $this->logger );
        
        // Log de inicialización exitosa
        $this->logger->log( 'info', 'Plugin PMPRO-Woo-Sync inicializado correctamente.' );
    }

    /**
     * Registra los hooks relacionados con el área de administración.
     *
     * @since 1.0.0
     * @access private
     */
    private function define_admin_hooks() {
        // Cargar scripts y estilos para el admin.
        add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_scripts' ) );

        // Añadir páginas de menú en el admin.
        add_action( 'admin_menu', array( $this->admin, 'add_admin_menu_pages' ) );

        // Registrar ajustes del plugin.
        add_action( 'admin_init', array( $this->settings, 'register_settings' ) );
        
        // Hook para mostrar avisos de admin
        add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
    }

    /**
     * Registra los hooks relacionados con el área pública y la lógica principal.
     *
     * @since 1.0.0
     * @access private
     */
    private function define_public_hooks() {
        // Cargar el dominio de texto para la internacionalización.
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Hooks de PMPro → WooCommerce
        add_action( 'pmpro_after_checkout', array( $this->integrations, 'handle_pmpro_checkout' ), 10, 2 );
        add_action( 'pmpro_after_change_membership_level', array( $this->integrations, 'sync_membership_to_woo' ), 10, 3 );
        
        // Hooks de WooCommerce → PMPro
        add_action( 'woocommerce_subscription_status_changed', array( $this->integrations, 'handle_subscription_status_change' ), 10, 3 );
        add_action( 'woocommerce_order_status_changed', array( $this->integrations, 'handle_order_status_change' ), 10, 4 );
        
        // Hook específico para cancelaciones desde PMPro
        add_action( 'pmpro_before_change_membership_level', array( $this->integrations, 'store_previous_level' ), 5, 3 );
        add_action( 'pmpro_after_change_membership_level', array( $this->integrations, 'handle_pmpro_cancellation_from_pmpro' ), 10, 3 );
        
        // Hooks adicionales para sincronización completa
        add_action( 'woocommerce_subscription_payment_complete', array( $this->integrations, 'handle_subscription_payment_complete' ), 10, 1 );
        add_action( 'woocommerce_subscription_payment_failed', array( $this->integrations, 'handle_subscription_payment_failed' ), 10, 1 );
    }

    /**
     * Inicia la ejecución del plugin.
     *
     * @since 1.0.0
     */
    public function run() {
        // Verificar que PMPro y WooCommerce estén activos
        if ( ! $this->check_plugin_dependencies() ) {
            return;
        }
        
        // Log de ejecución iniciada
        $this->logger->log( 'info', 'Plugin PMPRO-Woo-Sync ejecutándose.' );
    }

    /**
     * Verifica que los plugins dependientes estén activos.
     *
     * @return bool
     */
    private function check_plugin_dependencies() {
        $dependencies = array(
            'pmpro' => function_exists( 'pmpro_getLevel' ),
            'woocommerce' => class_exists( 'WooCommerce' ),
        );
        
        foreach ( $dependencies as $plugin => $check ) {
            if ( ! $check ) {
                $this->logger->log( 'error', "Dependencia faltante: {$plugin}" );
                return false;
            }
        }
        
        return true;
    }

    /**
     * Muestra avisos de administración si es necesario.
     */
    public function show_admin_notices() {
        // Verificar si hay errores críticos que mostrar
        if ( ! $this->check_plugin_dependencies() ) {
            echo '<div class="notice notice-error"><p>';
            _e( 'PMPRO-WooCommerce Sync: Faltan dependencias críticas. Verifique que PMPro y WooCommerce estén activos.', 'pmpro-woo-sync' );
            echo '</p></div>';
        }
    }

    /**
     * Carga el dominio de texto del plugin para la internacionalización.
     *
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'pmpro-woo-sync',
            false,
            dirname( PMPRO_WOO_SYNC_BASENAME ) . '/languages/'
        );
    }

    /**
     * Método de activación del plugin.
     * Se ejecuta una sola vez cuando el plugin es activado.
     *
     * @since 1.0.0
     */
    public static function activate() {
        global $wpdb;

        // Crear tabla para logs si no existe.
        $table_name = $wpdb->prefix . 'pmpro_woo_sync_logs';
        $charset_collate = $wpdb->get_charset_collate();

        // SQL para crear la tabla de logs con índices optimizados.
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext NULL,
            PRIMARY KEY (id),
            INDEX idx_timestamp (timestamp),
            INDEX idx_level (level),
            INDEX idx_timestamp_level (timestamp, level)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Establecer opciones por defecto más completas.
        $default_settings = array(
            'enable_sync' => 'yes',
            'debug_mode' => 'no',
            'log_retention_days' => 30,
            'retry_attempts' => 3,
            'retry_delay' => 300, // 5 minutos
            'webhook_enabled' => 'no',
            'batch_size' => 50,
            'api_timeout' => 30,
            'enable_logging' => 'yes',
        );
        
        // Solo agregar si no existe para no sobrescribir configuraciones existentes
        if ( ! get_option( 'pmpro_woo_sync_settings' ) ) {
            add_option( 'pmpro_woo_sync_settings', $default_settings );
        }
        
        // Crear directorio de logs si no existe
        $log_dir = PMPRO_WOO_SYNC_PATH . 'logs/';
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
            // Crear archivo .htaccess para proteger los logs
            file_put_contents( $log_dir . '.htaccess', "Deny from all\n" );
        }
        
        // Programar limpieza de logs
        if ( ! wp_next_scheduled( 'pmpro_woo_sync_cleanup_logs' ) ) {
            wp_schedule_event( time(), 'daily', 'pmpro_woo_sync_cleanup_logs' );
        }
    }

    /**
     * Método de desactivación del plugin.
     * Se ejecuta cuando el plugin es desactivado.
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        // Desprogramar eventos cron
        wp_clear_scheduled_hook( 'pmpro_woo_sync_cleanup_logs' );
        wp_clear_scheduled_hook( 'pmpro_woo_sync_retry_failed_operations' );
        
        // Log de desactivación (si el logger está disponible)
        if ( class_exists( 'PMPro_Woo_Sync_Logger' ) ) {
            $logger = new PMPro_Woo_Sync_Logger();
            $logger->log( 'info', 'Plugin PMPRO-Woo-Sync desactivado.' );
        }
    }
}
