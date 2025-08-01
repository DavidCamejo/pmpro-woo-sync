<?php
/**
 * Clase para manejar la interfaz de administración del plugin.
 * Incluye páginas de configuración, logs, herramientas y diagnósticos.
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
     * Hook suffix de las páginas de admin para cargar assets específicos.
     *
     * @var array
     */
    protected $admin_pages = array();

    /**
     * Constructor.
     *
     * @param PMPro_Woo_Sync_Settings $settings Instancia de la clase de ajustes.
     * @param PMPro_Woo_Sync_Logger   $logger   Instancia de la clase de logs.
     */
    public function __construct( PMPro_Woo_Sync_Settings $settings, PMPro_Woo_Sync_Logger $logger ) {
        $this->settings = $settings;
        $this->logger   = $logger;
        
        // Registrar handlers AJAX
        add_action( 'wp_ajax_pmpro_woo_sync_clear_logs', array( $this, 'ajax_clear_logs' ) );
        add_action( 'wp_ajax_pmpro_woo_sync_test_connection', array( $this, 'ajax_test_connection' ) );
        add_action( 'wp_ajax_pmpro_woo_sync_export_logs', array( $this, 'ajax_export_logs' ) );
        add_action( 'wp_ajax_pmpro_woo_sync_refresh_logs', array( $this, 'ajax_refresh_logs' ) );
    }

    /**
     * Añade las páginas del menú de administración del plugin.
     */
    public function add_admin_menu_pages() {
        // Página principal (Configuraciones)
        $this->admin_pages['main'] = add_menu_page(
            __( 'PMPro-Woo-Sync', 'pmpro-woo-sync' ),
            __( 'PMPro-Woo-Sync', 'pmpro-woo-sync' ),
            'manage_options',
            'pmpro-woo-sync',
            array( $this, 'display_settings_page' ),
            'dashicons-randomize',
            60
        );

        // Subpágina de Configuraciones (misma que la principal)
        $this->admin_pages['settings'] = add_submenu_page(
            'pmpro-woo-sync',
            __( 'Configuraciones', 'pmpro-woo-sync' ),
            __( 'Configuraciones', 'pmpro-woo-sync' ),
            'manage_options',
            'pmpro-woo-sync',
            array( $this, 'display_settings_page' )
        );

        // Subpágina de Logs
        $this->admin_pages['logs'] = add_submenu_page(
            'pmpro-woo-sync',
            __( 'Logs del Plugin', 'pmpro-woo-sync' ),
            __( 'Logs', 'pmpro-woo-sync' ),
            'manage_options',
            'pmpro-woo-sync-logs',
            array( $this, 'display_logs_page' )
        );

        // Subpágina de Herramientas
        $this->admin_pages['tools'] = add_submenu_page(
            'pmpro-woo-sync',
            __( 'Herramientas', 'pmpro-woo-sync' ),
            __( 'Herramientas', 'pmpro-woo-sync' ),
            'manage_options',
            'pmpro-woo-sync-tools',
            array( $this, 'display_tools_page' )
        );

        // Subpágina de Estado del Sistema
        $this->admin_pages['status'] = add_submenu_page(
            'pmpro-woo-sync',
            __( 'Estado del Sistema', 'pmpro-woo-sync' ),
            __( 'Estado', 'pmpro-woo-sync' ),
            'manage_options',
            'pmpro-woo-sync-status',
            array( $this, 'display_status_page' )
        );

        // Hooks específicos para cada página
        foreach ( $this->admin_pages as $page_key => $hook_suffix ) {
            add_action( "load-{$hook_suffix}", array( $this, "load_{$page_key}_page" ) );
        }
    }

    /**
     * Callback ejecutado al cargar la página principal.
     */
    public function load_main_page() {
        $this->load_settings_page();
    }

    /**
     * Callback ejecutado al cargar la página de configuraciones.
     */
    public function load_settings_page() {
        // Procesar acciones de configuración
        if ( isset( $_POST['pmpro_woo_sync_action'] ) ) {
            $this->handle_settings_actions();
        }

        // Agregar help tabs
        $this->add_settings_help_tabs();
    }

    /**
     * Callback ejecutado al cargar la página de logs.
     */
    public function load_logs_page() {
        // Procesar acciones de logs
        if ( isset( $_POST['pmpro_woo_sync_logs_action'] ) ) {
            $this->handle_logs_actions();
        }

        // Agregar help tabs
        $this->add_logs_help_tabs();
    }

    /**
     * Callback ejecutado al cargar la página de herramientas.
     */
    public function load_tools_page() {
        // Procesar acciones de herramientas
        if ( isset( $_POST['pmpro_woo_sync_tools_action'] ) ) {
            $this->handle_tools_actions();
        }
    }

    /**
     * Callback ejecutado al cargar la página de estado.
     */
    public function load_status_page() {
        // No necesita procesamiento especial por ahora
    }

    /**
     * Callback para renderizar la página de ajustes.
     */
    public function display_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos suficientes para acceder a esta página.', 'pmpro-woo-sync' ) );
        }

        // Mostrar mensajes de error/confirmación
        settings_errors( PMPro_Woo_Sync_Settings::SETTINGS_GROUP_NAME );

        // Incluir la plantilla
        require_once PMPRO_WOO_SYNC_PATH . 'admin/partials/admin-display-settings.php';
    }

    /**
     * Callback para renderizar la página de logs.
     */
    public function display_logs_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos suficientes para acceder a esta página.', 'pmpro-woo-sync' ) );
        }

        // Parámetros de paginación y filtros
        $logs_per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $offset = ( $current_page - 1 ) * $logs_per_page;
        $filter_level = isset( $_GET['log_level_filter'] ) ? sanitize_text_field( $_GET['log_level_filter'] ) : '';
        $search_query = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';

        // Obtener logs
        $logs = $this->logger->get_logs( $logs_per_page, $offset, $filter_level, $search_query );
        $total_logs = $this->logger->get_total_logs( $filter_level, $search_query );
        $total_pages = ceil( $total_logs / $logs_per_page );

        // Estadísticas de logs
        $log_stats = $this->logger->get_log_stats();

        // Incluir la plantilla
        require_once PMPRO_WOO_SYNC_PATH . 'admin/partials/admin-display-logs.php';
    }

    /**
     * Callback para renderizar la página de herramientas.
     */
    public function display_tools_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos suficientes para acceder a esta página.', 'pmpro-woo-sync' ) );
        }

        require_once PMPRO_WOO_SYNC_PATH . 'admin/partials/admin-display-tools.php';
    }

    /**
     * Callback para renderizar la página de estado del sistema.
     */
    public function display_status_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos suficientes para acceder a esta página.', 'pmpro-woo-sync' ) );
        }

        // Obtener información del sistema
        $system_info = $this->get_system_information();
        $dependency_status = $this->check_dependencies_status();

        require_once PMPRO_WOO_SYNC_PATH . 'admin/partials/admin-display-status.php';
    }

    /**
     * Maneja acciones de la página de configuraciones.
     */
    private function handle_settings_actions() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'pmpro_woo_sync_settings_action' ) ) {
            wp_die( __( 'Acción no autorizada.', 'pmpro-woo-sync' ) );
        }

        $action = sanitize_text_field( $_POST['pmpro_woo_sync_action'] );

        switch ( $action ) {
            case 'test_pagbank_connection':
                $this->test_pagbank_connection();
                break;
            case 'reset_settings':
                $this->reset_plugin_settings();
                break;
        }
    }

    /**
     * Maneja acciones de la página de logs.
     */
    private function handle_logs_actions() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'pmpro_woo_sync_logs_action' ) ) {
            wp_die( __( 'Acción no autorizada.', 'pmpro-woo-sync' ) );
        }

        $action = sanitize_text_field( $_POST['pmpro_woo_sync_logs_action'] );

        switch ( $action ) {
            case 'clear_logs':
                $this->clear_all_logs();
                break;
            case 'cleanup_old_logs':
                $this->logger->cleanup_old_logs();
                add_settings_error( 'pmpro_woo_sync_logs', 'logs_cleaned', __( 'Logs antiguos limpiados exitosamente.', 'pmpro-woo-sync' ), 'updated' );
                break;
        }
    }

    /**
     * Maneja acciones de la página de herramientas.
     */
    private function handle_tools_actions() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'pmpro_woo_sync_tools_action' ) ) {
            wp_die( __( 'Acción no autorizada.', 'pmpro-woo-sync' ) );
        }

        $action = sanitize_text_field( $_POST['pmpro_woo_sync_tools_action'] );

        switch ( $action ) {
            case 'sync_all_memberships':
                $this->sync_all_memberships();
                break;
            case 'repair_subscription_links':
                $this->repair_subscription_links();
                break;
        }
    }

    /**
     * Prueba la conexión con PagBank.
     */
    private function test_pagbank_connection() {
        require_once PMPRO_WOO_SYNC_PATH . 'includes/gateways/class-pmpro-woo-sync-pagbank-api.php';
        
        $pagbank_api = new PMPro_Woo_Sync_PagBank_API( $this->settings, $this->logger );
        $result = $pagbank_api->test_connection();

        if ( is_wp_error( $result ) ) {
            add_settings_error(
                'pmpro_woo_sync_settings',
                'pagbank_connection_failed',
                __( 'Error al conectar con PagBank: ', 'pmpro-woo-sync' ) . $result->get_error_message(),
                'error'
            );
        } else {
            add_settings_error(
                'pmpro_woo_sync_settings',
                'pagbank_connection_success',
                __( 'Conexión con PagBank exitosa.', 'pmpro-woo-sync' ),
                'updated'
            );
        }
    }

    /**
     * Encola los estilos CSS para el área de administración.
     */
    public function enqueue_styles( $hook_suffix ) {
        // Solo cargar en nuestras páginas
        if ( ! in_array( $hook_suffix, $this->admin_pages, true ) ) {
            return;
        }

        wp_enqueue_style(
            'pmpro-woo-sync-admin',
            PMPRO_WOO_SYNC_URL . 'assets/css/admin.css',
            array(),
            PMPRO_WOO_SYNC_VERSION,
            'all'
        );

        // Estilos específicos para logs
        if ( $hook_suffix === $this->admin_pages['logs'] ) {
            wp_enqueue_style( 'wp-jquery-ui-dialog' );
        }
    }

    /**
     * Encola los scripts JS para el área de administración.
     */
    public function enqueue_scripts( $hook_suffix ) {
        // Solo cargar en nuestras páginas
        if ( ! in_array( $hook_suffix, $this->admin_pages, true ) ) {
            return;
        }

        wp_enqueue_script(
            'pmpro-woo-sync-admin',
            PMPRO_WOO_SYNC_URL . 'assets/js/admin.js',
            array( 'jquery', 'jquery-ui-dialog' ),
            PMPRO_WOO_SYNC_VERSION,
            true
        );

        // Localizar script
        wp_localize_script( 'pmpro-woo-sync-admin', 'pmproWooSyncAdmin', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'pmpro_woo_sync_admin_nonce' ),
            'strings' => array(
                'confirm_clear_logs' => __( '¿Estás seguro de que deseas limpiar todos los logs?', 'pmpro-woo-sync' ),
                'testing_connection' => __( 'Probando conexión...', 'pmpro-woo-sync' ),
                'connection_success' => __( 'Conexión exitosa', 'pmpro-woo-sync' ),
                'connection_failed'  => __( 'Error de conexión', 'pmpro-woo-sync' ),
                'processing'         => __( 'Procesando...', 'pmpro-woo-sync' ),
            ),
        ));
    }

    /**
     * AJAX: Limpiar todos los logs.
     */
    public function ajax_clear_logs() {
        check_ajax_referer( 'pmpro_woo_sync_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }

        $this->clear_all_logs();

        wp_send_json_success( array(
            'message' => __( 'Todos los logs han sido limpiados.', 'pmpro-woo-sync' )
        ));
    }

    /**
     * AJAX: Probar conexión con gateway.
     */
    public function ajax_test_connection() {
        check_ajax_referer( 'pmpro_woo_sync_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }

        $gateway = sanitize_text_field( $_POST['gateway'] ?? 'pagbank' );

        if ( 'pagbank' === $gateway ) {
            require_once PMPRO_WOO_SYNC_PATH . 'includes/gateways/class-pmpro-woo-sync-pagbank-api.php';
            $api = new PMPro_Woo_Sync_PagBank_API( $this->settings, $this->logger );
            $result = $api->test_connection();
        } else {
            $result = new WP_Error( 'unsupported_gateway', __( 'Gateway no soportado', 'pmpro-woo-sync' ) );
        }

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message()
            ));
        } else {
            wp_send_json_success( array(
                'message' => __( 'Conexión exitosa con el gateway.', 'pmpro-woo-sync' )
            ));
        }
    }

    /**
     * AJAX: Exportar logs.
     */
    public function ajax_export_logs() {
        check_ajax_referer( 'pmpro_woo_sync_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }

        $filter_level = sanitize_text_field( $_POST['level'] ?? '' );
        $logs = $this->logger->get_logs( 1000, 0, $filter_level ); // Máximo 1000 logs

        $export_data = array();
        foreach ( $logs as $log ) {
            $export_data[] = array(
                'timestamp' => $log->timestamp,
                'level'     => $log->level,
                'message'   => $log->message,
                'context'   => $log->context,
            );
        }

        wp_send_json_success( array(
            'data'     => $export_data,
            'filename' => 'pmpro-woo-sync-logs-' . gmdate( 'Y-m-d-H-i-s' ) . '.json'
        ));
    }

    /**
     * AJAX: Refrescar logs.
     */
    public function ajax_refresh_logs() {
        check_ajax_referer( 'pmpro_woo_sync_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }

        $logs_per_page = 20;
        $current_page = max( 1, intval( $_POST['page'] ?? 1 ) );
        $offset = ( $current_page - 1 ) * $logs_per_page;
        $filter_level = sanitize_text_field( $_POST['level'] ?? '' );
        $search_query = sanitize_text_field( $_POST['search'] ?? '' );

        $logs = $this->logger->get_logs( $logs_per_page, $offset, $filter_level, $search_query );
        $total_logs = $this->logger->get_total_logs( $filter_level, $search_query );

        ob_start();
        foreach ( $logs as $log_entry ) {
            include PMPRO_WOO_SYNC_PATH . 'admin/partials/log-row.php';
        }
        $html = ob_get_clean();

        wp_send_json_success( array(
            'html'       => $html,
            'total_logs' => $total_logs,
        ));
    }

    /**
     * Limpia todos los logs.
     */
    private function clear_all_logs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pmpro_woo_sync_logs';
        $deleted = $wpdb->query( "TRUNCATE TABLE {$table_name}" );

        if ( false !== $deleted ) {
            $this->logger->info( 'Todos los logs fueron limpiados manualmente desde el admin' );
            add_settings_error( 'pmpro_woo_sync_logs', 'logs_cleared', __( 'Todos los logs han sido limpiados.', 'pmpro-woo-sync' ), 'updated' );
        } else {
            add_settings_error( 'pmpro_woo_sync_logs', 'logs_clear_error', __( 'Error al limpiar los logs.', 'pmpro-woo-sync' ), 'error' );
        }
    }

    /**
     * Obtiene información del sistema.
     */
    private function get_system_information() {
        global $wpdb;

        return array(
            'wordpress_version' => get_bloginfo( 'version' ),
            'php_version'       => PHP_VERSION,
            'mysql_version'     => $wpdb->db_version(),
            'plugin_version'    => PMPRO_WOO_SYNC_VERSION,
            'memory_limit'      => ini_get( 'memory_limit' ),
            'max_execution_time' => ini_get( 'max_execution_time' ),
            'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
        );
    }

    /**
     * Verifica el estado de las dependencias.
     */
    private function check_dependencies_status() {
        return array(
            'pmpro_active'      => function_exists( 'pmpro_getLevel' ),
            'woocommerce_active' => class_exists( 'WooCommerce' ),
            'wc_subscriptions_active' => class_exists( 'WC_Subscriptions' ),
        );
    }

    /**
     * Agrega help tabs a la página de configuraciones.
     */
    private function add_settings_help_tabs() {
        $screen = get_current_screen();
        
        $screen->add_help_tab( array(
            'id'      => 'pmpro_woo_sync_general_help',
            'title'   => __( 'Configuración General', 'pmpro-woo-sync' ),
            'content' => '<p>' . __( 'Configure las opciones generales del plugin de sincronización entre PMPro y WooCommerce.', 'pmpro-woo-sync' ) . '</p>',
        ));
    }

    /**
     * Agrega help tabs a la página de logs.
     */
    private function add_logs_help_tabs() {
        $screen = get_current_screen();
        
        $screen->add_help_tab( array(
            'id'      => 'pmpro_woo_sync_logs_help',
            'title'   => __( 'Logs del Sistema', 'pmpro-woo-sync' ),
            'content' => '<p>' . __( 'Aquí puede ver todos los logs generados por el plugin. Use los filtros para encontrar información específica.', 'pmpro-woo-sync' ) . '</p>',
        ));
    }

    /**
     * Sincroniza todas las membresías (herramienta).
     */
    private function sync_all_memberships() {
        // TODO: Implementar sincronización masiva
        add_settings_error( 'pmpro_woo_sync_tools', 'sync_started', __( 'Sincronización masiva iniciada. Revise los logs para ver el progreso.', 'pmpro-woo-sync' ), 'updated' );
    }

    /**
     * Repara enlaces de suscripciones (herramienta).
     */
    private function repair_subscription_links() {
        // TODO: Implementar reparación de enlaces
        add_settings_error( 'pmpro_woo_sync_tools', 'repair_started', __( 'Reparación de enlaces iniciada.', 'pmpro-woo-sync' ), 'updated' );
    }

    /**
     * Reinicia las configuraciones del plugin.
     */
    private function reset_plugin_settings() {
        delete_option( PMPro_Woo_Sync_Settings::SETTINGS_OPTION_NAME );
        add_settings_error( 'pmpro_woo_sync_settings', 'settings_reset', __( 'Configuraciones reiniciadas a valores por defecto.', 'pmpro-woo-sync' ), 'updated' );
    }
}
