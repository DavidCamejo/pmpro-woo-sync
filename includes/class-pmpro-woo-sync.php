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
    }

    /**
     * Registra los hooks relacionados con el área de administración.
     *
     * @since 1.0.0
     * @access private
     */
    private function define_admin_hooks() {
        // Cargar scripts y estilos para el admin.
        add_action( 'admin_enqueue_scripts', [ $this->admin, 'enqueue_styles' ] );
        add_action( 'admin_enqueue_scripts', [ $this->admin, 'enqueue_scripts' ] );

        // Añadir páginas de menú en el admin.
        add_action( 'admin_menu', [ $this->admin, 'add_admin_menu_pages' ] );

        // Registrar ajustes del plugin.
        add_action( 'admin_init', [ $this->settings, 'register_settings' ] );
    }

    /**
     * Registra los hooks relacionados con el área pública (frontend) y la lógica principal.
     *
     * @since 1.0.0
     * @access private
     */
    private function define_public_hooks() {
        // Cargar el dominio de texto para la internacionalización.
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );

        // Aquí se agregarían los hooks de integración con PMPRO y WooCommerce.
        // Por ejemplo:
        // add_action( 'pmpro_after_checkout', [ $this->integrations, 'handle_pmpro_checkout' ], 10, 2 );
        // add_action( 'woocommerce_subscription_status_changed', [ $this->integrations, 'handle_subscription_status_change' ], 10, 3 );
        // add_action( 'woocommerce_order_status_changed', [ $this->integrations, 'handle_order_status_change' ], 10, 4 );

        // Ejemplo: Sincronizar un usuario cuando se actualiza.
        // add_action( 'profile_update', [ $this->integrations, 'sync_user_on_profile_update' ], 10, 2 );
    }

    /**
     * Inicia la ejecución del plugin.
     *
     * @since 1.0.0
     */
    public function run() {
        // En este método se pueden ejecutar tareas que deban correr al iniciar el plugin,
        // pero la mayor parte de la lógica se activa a través de los hooks definidos.
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

        // SQL para crear la tabla de logs.
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Establecer opciones por defecto si no existen.
        $default_settings = array(
            'enable_sync' => 'yes',
            'debug_mode'  => 'no',
            // Añadir más opciones por defecto aquí.
        );
        add_option( 'pmpro_woo_sync_settings', $default_settings );
    }

    /**
     * Método de desactivación del plugin.
     * Se ejecuta cuando el plugin es desactivado.
     * No debe eliminar datos persistentes, eso es trabajo de uninstall.php.
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        // Por ahora, no se requiere ninguna acción especial al desactivar.
        // Si tuviera, por ejemplo, cron jobs personalizados, se desprogramarían aquí.
    }
}
