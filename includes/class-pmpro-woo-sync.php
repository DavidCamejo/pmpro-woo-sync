<?php
/**
 * Clase principal del plugin PMPro-Woo-Sync
 *
 * @package PMPro_Woo_Sync
 * @since 2.0.0
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PMPro_Woo_Sync {

    /**
     * Instancia única del plugin
     *
     * @var PMPro_Woo_Sync
     */
    private static $instance = null;

    /**
     * Versión del plugin
     *
     * @var string
     */
    public $version = '2.0.0';

    /**
     * Constructor
     */
    public function __construct() {
        $this->define_constants();
        $this->init_hooks();
    }

    /**
     * Obtener instancia única del plugin
     *
     * @return PMPro_Woo_Sync
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Definir constantes del plugin
     */
    private function define_constants() {
        if ( ! defined( 'PMPRO_WOO_SYNC_PLUGIN_VERSION' ) ) {
            define( 'PMPRO_WOO_SYNC_PLUGIN_VERSION', $this->version );
        }
    }

    /**
     * Inicializar hooks principales
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'init' ), 0 );
        add_action( 'plugins_loaded', array( $this, 'init_integrations' ), 20 );
    }

    /**
     * Inicialización principal del plugin
     */
    public function init() {
        // Cargar traducciones
        $this->load_textdomain();
        
        // Verificar dependencias
        if ( ! $this->check_dependencies() ) {
            return;
        }

        // Inicializar configuraciones
        if ( class_exists( 'PMPro_Woo_Sync_Settings' ) ) {
            new PMPro_Woo_Sync_Settings();
        }

        // Inicializar admin si estamos en el backend
        if ( is_admin() && class_exists( 'PMPro_Woo_Sync_Admin' ) ) {
            new PMPro_Woo_Sync_Admin();
        }
    }

    /**
     * Ejecutar el plugin
     */
    public function run() {
        // El plugin se ejecuta automáticamente a través de los hooks
        do_action( 'pmpro_woo_sync_loaded' );
    }

    /**
     * Inicializar integraciones con WooCommerce
     */
    public function init_integrations() {
        // Verificar que WooCommerce esté activo
        if ( ! $this->is_woocommerce_active() ) {
            return;
        }

        // Inicializar la clase de integraciones
        if ( class_exists( 'PMPro_Woo_Sync_Integrations' ) ) {
            $integrations = new PMPro_Woo_Sync_Integrations();
            $integrations->register_hooks();
        }
    }

    /**
     * Cargar traducciones
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'pmpro-woo-sync',
            false,
            dirname( PMPRO_WOO_SYNC_BASENAME ) . '/languages/'
        );
    }

    /**
     * Verificar dependencias críticas
     *
     * @return bool
     */
    private function check_dependencies() {
        $dependencies_met = true;

        // Verificar Paid Memberships Pro
        if ( ! function_exists( 'pmpro_getLevel' ) ) {
            add_action( 'admin_notices', array( $this, 'pmpro_missing_notice' ) );
            $dependencies_met = false;
        }

        // Verificar WooCommerce
        if ( ! $this->is_woocommerce_active() ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
            $dependencies_met = false;
        }

        return $dependencies_met;
    }

    /**
     * Verificar si WooCommerce está activo
     *
     * @return bool
     */
    private function is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Aviso de PMPro faltante
     */
    public function pmpro_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        printf(
            __( '<strong>PMPro-Woo-Sync:</strong> Requiere que %s esté instalado y activo.', 'pmpro-woo-sync' ),
            '<a href="https://wordpress.org/plugins/paid-memberships-pro/" target="_blank">Paid Memberships Pro</a>'
        );
        echo '</p></div>';
    }

    /**
     * Aviso de WooCommerce faltante
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        printf(
            __( '<strong>PMPro-Woo-Sync:</strong> Requiere que %s esté instalado y activo.', 'pmpro-woo-sync' ),
            '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>'
        );
        echo '</p></div>';
    }

    /**
     * Hook de activación del plugin
     */
    public static function activate() {
        // Crear tablas si es necesario
        self::create_tables();
        
        // Configuraciones por defecto
        self::set_default_options();
        
        // Limpiar cache
        flush_rewrite_rules();
        
        // Log de activación
        if ( class_exists( 'PMPro_Woo_Sync_Logger' ) ) {
            PMPro_Woo_Sync_Logger::log( 'Plugin activado correctamente', 'info' );
        }
    }

    /**
     * Hook de desactivación del plugin
     */
    public static function deactivate() {
        // Limpiar cache
        flush_rewrite_rules();
        
        // Log de desactivación
        if ( class_exists( 'PMPro_Woo_Sync_Logger' ) ) {
            PMPro_Woo_Sync_Logger::log( 'Plugin desactivado', 'info' );
        }
    }

    /**
     * Crear tablas necesarias
     */
    private static function create_tables() {
        // Implementar si necesitas tablas personalizadas
        // Por ahora, el plugin usa las tablas estándar de WordPress
    }

    /**
     * Establecer opciones por defecto
     */
    private static function set_default_options() {
        $default_settings = array(
            'enable_sync' => 1,
            'debug_mode' => 0,
            'log_retention_days' => 30,
        );

        add_option( 'pmpro_woo_sync_settings', $default_settings );
        add_option( 'pmpro_woo_sync_version', PMPRO_WOO_SYNC_VERSION );
    }

    /**
     * Obtener configuración del plugin
     *
     * @param string $key Clave de configuración
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    public function get_setting( $key, $default = null ) {
        $settings = get_option( 'pmpro_woo_sync_settings', array() );
        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }

    /**
     * Verificar si la sincronización está habilitada
     *
     * @return bool
     */
    public function is_sync_enabled() {
        return (bool) $this->get_setting( 'enable_sync', true );
    }
}
