<?php
/**
 * Clase para manejar la interfaz de administración del plugin.
 */
class PMPro_Woo_Sync_Admin {

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
     * Añade las páginas del menú de administración del plugin.
     */
    public function add_admin_menu_pages() {
        add_menu_page(
            __( 'PMPRO-Woo Sync', 'pmpro-woo-sync' ),      // Título de la página.
            __( 'PMPRO-Woo Sync', 'pmpro-woo-sync' ),      // Texto del menú.
            'manage_options',                              // Capacidad requerida para acceder.
            'pmpro-woo-sync',                              // Slug del menú.
            [ $this, 'display_settings_page' ],            // Callback para renderizar la página.
            'dashicons-randomize',                         // Icono de Dashicons.
            60                                             // Posición en el menú.
        );

        add_submenu_page(
            'pmpro-woo-sync',                              // Slug del menú padre.
            __( 'Ajustes', 'pmpro-woo-sync' ),             // Título de la página.
            __( 'Ajustes', 'pmpro-woo-sync' ),             // Texto del submenú.
            'manage_options',                              // Capacidad requerida.
            'pmpro-woo-sync',                              // Slug (el mismo que el padre para ser la principal).
            [ $this, 'display_settings_page' ]             // Callback para renderizar.
        );

        add_submenu_page(
            'pmpro-woo-sync',                              // Slug del menú padre.
            __( 'Logs del Plugin', 'pmpro-woo-sync' ),     // Título de la página.
            __( 'Logs', 'pmpro-woo-sync' ),                // Texto del submenú.
            'manage_options',                              // Capacidad requerida.
            'pmpro-woo-sync-logs',                         // Slug único para la página de logs.
            [ $this, 'display_logs_page' ]                 // Callback para renderizar.
        );

        // TODO: Añadir más subpáginas si es necesario (ej. Herramientas, Estado, Mapeo).
    }

    /**
     * Callback para renderizar la página de ajustes.
     */
    public function display_settings_page() {
        // Asegurarse de que el usuario tenga permisos.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Mostrar mensajes de error/confirmación de WordPress.
        settings_errors( 'pmpro_woo_sync_option_group' );

        // Incluir la plantilla de la página de ajustes.
        require_once PMPRO_WOO_SYNC_PATH . 'admin/partials/admin-display-settings.php';
    }

    /**
     * Callback para renderizar la página de logs.
     */
    public function display_logs_page() {
        // Asegurarse de que el usuario tenga permisos.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Obtener los logs.
        $logs_per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $offset = ( $current_page - 1 ) * $logs_per_page;
        $filter_level = isset( $_GET['log_level_filter'] ) ? sanitize_text_field( $_GET['log_level_filter'] ) : '';

        $logs = $this->logger->get_logs( $logs_per_page, $offset, $filter_level );
        $total_logs = $this->logger->get_total_logs( $filter_level );
        $total_pages = ceil( $total_logs / $logs_per_page );

        // Incluir la plantilla de la página de logs.
        require_once PMPRO_WOO_SYNC_PATH . 'admin/partials/admin-display-logs.php';
    }

    /**
     * Encola los estilos CSS para el área de administración.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'pmpro-woo-sync-admin',
            PMPRO_WOO_SYNC_URL . 'assets/css/admin.css',
            [],
            PMPRO_WOO_SYNC_VERSION,
            'all'
        );
    }

    /**
     * Encola los scripts JS para el área de administración.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'pmpro-woo-sync-admin',
            PMPRO_WOO_SYNC_URL . 'assets/js/admin.js',
            ['jquery'],
            PMPRO_WOO_SYNC_VERSION,
            true // En el footer.
        );
    }
}
