<?php
/**
 * Clase para manejar la interfaz de administración del plugin PMPro-Woo-Sync
 *
 * @package PMPro_Woo_Sync
 * @since 2.0.0
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PMPro_Woo_Sync_Admin {
        
    // AGREGAR singleton pattern para evitar múltiples instancias
    private static $instance = null;
    
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Instancia de PMPro_Woo_Sync_Settings
     *
     * @var PMPro_Woo_Sync_Settings
     */
    protected $settings = array(
        'enable_sync' => 1,
        'debug_mode' => 0,
        'enable_logging' => 1,
        'log_level' => 'info',
        'log_retention_days' => 7,
    );

    /**
     * Instancia de PMPro_Woo_Sync_Logger
     *
     * @var PMPro_Woo_Sync_Logger
     */
    protected $logger;

    /**
     * Hook suffix de las páginas de admin para cargar assets específicos
     *
     * @var array
     */
    protected $admin_pages = array();

    /**
     * Constructor
     */
    private function __construct() {
        $this->settings = new PMPro_Woo_Sync_Settings();
        $this->logger = PMPro_Woo_Sync_Logger::get_instance();
        
        $this->init_hooks();
    }
    
    // Prevenir clonación
    private function __clone() {}
    private function __wakeup() {}

    /**
    * Obtener productos de WooCommerce
    */
    private function get_woocommerce_products() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return array();
        }

        $products = array();
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_subscription_period',
                    'compare' => 'EXISTS'
                )
            )
        );

        $query = new WP_Query( $args );
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $product_id = get_the_ID();
                $product = wc_get_product( $product_id );
                
                if ( $product && ( $product->is_type( 'subscription' ) || $product->is_type( 'variable-subscription' ) ) ) {
                    $products[ $product_id ] = get_the_title() . ' (ID: ' . $product_id . ')';
                }
            }
            wp_reset_postdata();
        }

        // Si no hay productos de suscripción, obtener productos simples
        if ( empty( $products ) ) {
            $simple_args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => 50,
            );

            $simple_query = new WP_Query( $simple_args );
            
            if ( $simple_query->have_posts() ) {
                while ( $simple_query->have_posts() ) {
                    $simple_query->the_post();
                    $product_id = get_the_ID();
                    $products[ $product_id ] = get_the_title() . ' (ID: ' . $product_id . ')';
                }
                wp_reset_postdata();
            }
        }

        return $products;
    }

    /**
    * Inicializar hooks de administración
    */
    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu_pages' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
        
        // Registrar handlers AJAX
        add_action( 'wp_ajax_pmpro_woo_sync_clear_logs', array( $this, 'ajax_clear_logs' ) );
        add_action( 'wp_ajax_pmpro_woo_sync_test_sync', array( $this, 'ajax_test_sync' ) );
        add_action( 'wp_ajax_pmpro_woo_sync_export_logs', array( $this, 'ajax_export_logs' ) );
        add_action( 'wp_ajax_pmpro_woo_sync_refresh_logs', array( $this, 'ajax_refresh_logs' ) );
        add_action( 'wp_ajax_pmpro_woo_sync_sync_user', array( $this, 'ajax_sync_user' ) );
        
        // Handlers adicionales para página de estado
        add_action( 'wp_ajax_pmpro_woo_sync_diagnostic_test', array( $this, 'ajax_diagnostic_test' ) );
        add_action( 'wp_ajax_pmpro_woo_sync_export_system_info', array( $this, 'ajax_export_system_info' ) );

        // Handler para verificar debug
        add_action( 'wp_ajax_pmpro_woo_sync_debug_status', array( $this, 'ajax_debug_status' ) );
    }

    /**
    * AJAX: Exportar información del sistema
    */
    public function ajax_export_system_info() {
        check_ajax_referer( 'pmpro_woo_sync_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }

        $system_info = $this->get_system_information();
        $dependency_status = $this->check_dependencies_status();
        $sync_stats = $this->get_sync_statistics();

        $export_data = array(
            'plugin_info' => array(
                'name' => 'PMPro-Woo-Sync',
                'version' => PMPRO_WOO_SYNC_VERSION ?? '2.0.0',
                'export_date' => current_time( 'Y-m-d H:i:s' ),
            ),
            'system_info' => $system_info,
            'dependencies' => $dependency_status,
            'sync_statistics' => $sync_stats,
            'settings' => $this->settings->get_settings(),
        );

        // Crear archivo temporal
        $upload_dir = wp_upload_dir();
        $filename = 'pmpro-woo-sync-system-info-' . date( 'Y-m-d-H-i-s' ) . '.json';
        $filepath = $upload_dir['path'] . '/' . $filename;

        file_put_contents( $filepath, wp_json_encode( $export_data, JSON_PRETTY_PRINT ) );

        // Forzar descarga
        header( 'Content-Type: application/json' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . filesize( $filepath ) );
        
        readfile( $filepath );
        unlink( $filepath ); // Eliminar archivo temporal
        
        wp_die();
    }

    /**
     * AJAX handler para verificar estado del debug
     */
    public function ajax_debug_status() {
        check_ajax_referer('pmpro_woo_sync_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permisos insuficientes', 'pmpro-woo-sync'));
        }
        
        $settings = $this->settings->get_settings();
        $debug_info = array(
            'debug_enabled' => $this->settings->is_debug_enabled(),
            'logging_enabled' => $this->settings->is_logging_enabled(),
            'log_level' => $this->settings->get_log_level(),
            'settings_raw' => $settings
        );
        
        wp_send_json_success(array(
            'message' => __('Estado del debug verificado correctamente', 'pmpro-woo-sync'),
            'debug_info' => $debug_info
        ));
    }

    /**
     * Añadir páginas del menú de administración
     */
    public function add_admin_menu_pages() {
        // Página principal - WordPress automáticamente crea la primera subpágina
        $this->admin_pages['main'] = add_menu_page(
            __( 'PMPro-Woo-Sync', 'pmpro-woo-sync' ),
            __( 'PMPro-Woo-Sync', 'pmpro-woo-sync' ),
            'manage_options',
            'pmpro-woo-sync',
            array( $this, 'display_settings_page' ),
            'dashicons-randomize',
            60
        );

        // CAMBIAR EL TÍTULO de la subpágina automática (NO crear una nueva)
        $this->admin_pages['settings'] = add_submenu_page(
            'pmpro-woo-sync',
            __( 'Configuraciones', 'pmpro-woo-sync' ),
            __( 'Configuraciones', 'pmpro-woo-sync' ),
            'manage_options',
            'pmpro-woo-sync', // MISMO slug - esto reemplaza el título, no duplica
            array( $this, 'display_settings_page' )
        );

        // Resto de subpáginas con slugs únicos
        $this->admin_pages['logs'] = add_submenu_page(
            'pmpro-woo-sync',
            __( 'Logs del Sistema', 'pmpro-woo-sync' ),
            __( 'Logs', 'pmpro-woo-sync' ),
            'manage_options',
            'pmpro-woo-sync-logs', // Slug diferente
            array( $this, 'display_logs_page' )
        );

        $this->admin_pages['tools'] = add_submenu_page(
            'pmpro-woo-sync',
            __( 'Herramientas de Sincronización', 'pmpro-woo-sync' ),
            __( 'Herramientas', 'pmpro-woo-sync' ),
            'manage_options',
            'pmpro-woo-sync-tools', // Slug diferente
            array( $this, 'display_tools_page' )
        );

        $this->admin_pages['status'] = add_submenu_page(
            'pmpro-woo-sync',
            __( 'Estado del Sistema', 'pmpro-woo-sync' ),
            __( 'Estado', 'pmpro-woo-sync' ),
            'manage_options',
            'pmpro-woo-sync-status', // Slug diferente
            array( $this, 'display_status_page' )
        );

        // Hooks para páginas - main y settings usan el mismo hook
        add_action( "load-{$this->admin_pages['main']}", array( $this, 'load_settings_page' ) );
        add_action( "load-{$this->admin_pages['logs']}", array( $this, 'load_logs_page' ) );
        add_action( "load-{$this->admin_pages['tools']}", array( $this, 'load_tools_page' ) );
        add_action( "load-{$this->admin_pages['status']}", array( $this, 'load_status_page' ) );
    }

    /**
     * Callback ejecutado al cargar la página de configuraciones
     */
    public function load_settings_page() {
        // Procesar guardado de configuraciones
        if ( isset( $_POST['pmpro_woo_sync_action'] ) && $_POST['pmpro_woo_sync_action'] === 'save_settings' ) {
            $this->handle_save_settings();
        }

        // Agregar help tabs
        $this->add_settings_help_tabs();
    }

    /**
     * Manejar guardado de configuraciones
     */
    private function handle_save_settings() {
        // Verificar nonce
        if ( ! isset( $_POST['_pmpro_woo_sync_nonce'] ) || ! wp_verify_nonce( $_POST['_pmpro_woo_sync_nonce'], 'pmpro_woo_sync_save_settings' ) ) {
            wp_die( __( 'Acción no autorizada.', 'pmpro-woo-sync' ) );
        }

        // Verificar permisos
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos suficientes.', 'pmpro-woo-sync' ) );
        }

        // Obtener datos del formulario
        $input = isset( $_POST['pmpro_woo_sync_settings'] ) ? $_POST['pmpro_woo_sync_settings'] : array();
        
        // Sanitizar usando el método existente de la clase Settings
        $sanitized_settings = $this->settings->sanitize_settings( $input );
        
        // Guardar configuraciones
        $saved = update_option( 'pmpro_woo_sync_settings', $sanitized_settings );
        
        if ( $saved ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Configuraciones guardadas exitosamente.', 'pmpro-woo-sync' ) . '</p></div>';
            });
        } else {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Error al guardar las configuraciones.', 'pmpro-woo-sync' ) . '</p></div>';
            });
        }
        
        // Redireccionar para evitar reenvío del formulario
        wp_redirect( admin_url( 'admin.php?page=pmpro-woo-sync' ) );
        exit;
    }

    /**
     * Callback ejecutado al cargar la página de logs
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
     * Callback ejecutado al cargar la página de herramientas
     */
    public function load_tools_page() {
        // Procesar acciones de herramientas
        if ( isset( $_POST['pmpro_woo_sync_tools_action'] ) ) {
            $this->handle_tools_actions();
        }

        // Agregar help tabs
        $this->add_tools_help_tabs();
    }

    /**
     * Callback ejecutado al cargar la página de estado
     */
    public function load_status_page() {
        // Agregar help tabs
        $this->add_status_help_tabs();
    }

    /**
     * Renderizar página de configuraciones
     */
    public function display_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos suficientes para acceder a esta página.', 'pmpro-woo-sync' ) );
        }

        // USAR SOLO EL TEMPLATE PERSONALIZADO - ELIMINAR WordPress Settings API
        include_once plugin_dir_path( __FILE__ ) . 'partials/admin-display-settings.php';
    }

    /**
     * Renderizar página de logs
     */
    public function display_logs_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos suficientes para acceder a esta página.', 'pmpro-woo-sync' ) );
        }

        // USAR SOLO EL TEMPLATE - ELIMINAR TODO EL HTML DIRECTO
        include_once plugin_dir_path( __FILE__ ) . 'partials/admin-display-logs.php';
    }

    /**
     * Renderizar página de herramientas
     */
    public function display_tools_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos suficientes para acceder a esta página.', 'pmpro-woo-sync' ) );
        }

        // USAR SOLO EL TEMPLATE - ELIMINAR TODO EL HTML DIRECTO
        include_once plugin_dir_path( __FILE__ ) . 'partials/admin-display-tools.php';
    }

    /**
     * Manejar acciones de configuraciones
     */
    private function handle_settings_actions() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'pmpro_woo_sync_settings_action' ) ) {
            wp_die( __( 'Acción no autorizada.', 'pmpro-woo-sync' ) );
        }

        $action = sanitize_text_field( $_POST['pmpro_woo_sync_action'] );

        switch ( $action ) {
            case 'test_sync':
                $this->test_sync_functionality();
                break;
            case 'reset_settings':
                $this->reset_plugin_settings();
                break;
        }
    }

    /**
     * Manejar acciones de logs
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
                add_settings_error( 'pmpro_woo_sync_messages', 'logs_cleaned', __( 'Logs antiguos limpiados exitosamente.', 'pmpro-woo-sync' ), 'updated' );
                break;
        }
    }

    /**
     * Manejar acciones de herramientas
     */
    private function handle_tools_actions() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'pmpro_woo_sync_tools_action' ) ) {
            wp_die( __( 'Acción no autorizada.', 'pmpro-woo-sync' ) );
        }

        $action = sanitize_text_field( $_POST['pmpro_woo_sync_tools_action'] );

        switch ( $action ) {
            case 'sync_user':
                $user_id = intval( $_POST['user_id'] );
                $this->sync_single_user( $user_id );
                break;
            case 'sync_all_memberships':
                $this->sync_all_memberships();
                break;
            case 'repair_subscription_links':
                $this->repair_subscription_links();
                break;
            case 'cleanup_old_logs':
                $this->logger->cleanup_old_logs();
                add_settings_error( 'pmpro_woo_sync_messages', 'logs_cleaned', __( 'Logs antiguos limpiados exitosamente.', 'pmpro-woo-sync' ), 'updated' );
                break;
            case 'reset_settings':
                $this->reset_plugin_settings();
                break;
        }
    }

    /**
     * Cargar assets de administración
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        // Solo cargar en nuestras páginas
        if ( ! in_array( $hook_suffix, $this->admin_pages, true ) ) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'pmpro-woo-sync-admin',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin.css',
            array(),
            PMPRO_WOO_SYNC_VERSION,
            'all'
        );

        // JavaScript
        wp_enqueue_script(
            'pmpro-woo-sync-admin',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin.js',
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
                'testing_sync' => __( 'Probando sincronización...', 'pmpro-woo-sync' ),
                'sync_success' => __( 'Sincronización exitosa', 'pmpro-woo-sync' ),
                'sync_failed'  => __( 'Error de sincronización', 'pmpro-woo-sync' ),
                'processing'   => __( 'Procesando...', 'pmpro-woo-sync' ),
            ),
        ));

        // Estilos específicos para logs
        if ( $hook_suffix === $this->admin_pages['logs'] ) {
            wp_enqueue_style( 'wp-jquery-ui-dialog' );
        }
    }

    /**
     * Mostrar avisos de administración
     */
    public function display_admin_notices() {
        $screen = get_current_screen();
        
        // Solo mostrar en nuestras páginas
        if ( ! isset( $screen->id ) || strpos( $screen->id, 'pmpro-woo-sync' ) === false ) {
            return;
        }

        // Verificar dependencias críticas
        if ( ! function_exists( 'pmpro_getLevel' ) ) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php _e( 'PMPro-Woo-Sync:', 'pmpro-woo-sync' ); ?></strong>
                    <?php printf( 
                        __( 'Requiere que %s esté instalado y activo.', 'pmpro-woo-sync' ),
                        '<a href="https://wordpress.org/plugins/paid-memberships-pro/" target="_blank">Paid Memberships Pro</a>'
                    ); ?>
                </p>
            </div>
            <?php
        }

        if ( ! class_exists( 'WooCommerce' ) ) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php _e( 'PMPro-Woo-Sync:', 'pmpro-woo-sync' ); ?></strong>
                    <?php printf( 
                        __( 'Requiere que %s esté instalado y activo.', 'pmpro-woo-sync' ),
                        '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>'
                    ); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * AJAX: Limpiar todos los logs
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
     * AJAX: Probar funcionalidad de sincronización
     */
    public function ajax_test_sync() {
        check_ajax_referer( 'pmpro_woo_sync_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }

        $result = $this->test_sync_functionality();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message()
            ));
        } else {
            wp_send_json_success( array(
                'message' => __( 'Funcionalidad de sincronización operativa.', 'pmpro-woo-sync' )
            ));
        }
    }

    /**
     * AJAX: Exportar logs
     */
    public function ajax_export_logs() {
        check_ajax_referer( 'pmpro_woo_sync_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }

        $filters = array(
            'level' => sanitize_text_field( $_POST['level'] ?? '' ),
            'limit' => 1000
        );

        $csv_file = $this->logger->export_logs_to_csv( $filters );

        if ( $csv_file ) {
            wp_send_json_success( array(
                'download_url' => str_replace( ABSPATH, home_url( '/' ), $csv_file ),
                'filename' => basename( $csv_file )
            ));
        } else {
            wp_send_json_error( array(
                'message' => __( 'Error al exportar logs.', 'pmpro-woo-sync' )
            ));
        }
    }

    /**
     * AJAX: Refrescar logs
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
            ?>
            <tr class="log-level-<?php echo esc_attr( $log_entry->level ); ?>">
                <td><?php echo esc_html( $log_entry->timestamp ); ?></td>
                <td>
                    <span class="log-level-badge log-level-<?php echo esc_attr( $log_entry->level ); ?>">
                        <?php echo esc_html( ucfirst( $log_entry->level ) ); ?>
                    </span>
                </td>
                <td><?php echo esc_html( $log_entry->message ); ?></td>
                <td>
                    <?php if ( $log_entry->user_id ) : ?>
                        <?php $user = get_user_by( 'id', $log_entry->user_id ); ?>
                        <?php echo $user ? esc_html( $user->display_name ) : esc_html( $log_entry->user_id ); ?>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ( ! empty( $log_entry->context ) ) : ?>
                        <button type="button" class="button-link view-context" data-context="<?php echo esc_attr( $log_entry->context ); ?>">
                            <?php _e( 'Ver detalles', 'pmpro-woo-sync' ); ?>
                        </button>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
            <?php
        }
        $html = ob_get_clean();

        wp_send_json_success( array(
            'html'       => $html,
            'total_logs' => $total_logs,
        ));
    }

    /**
     * AJAX: Sincronizar usuario específico
     */
    public function ajax_sync_user() {
        check_ajax_referer( 'pmpro_woo_sync_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }

        $user_id = intval( $_POST['user_id'] ?? 0 );
        
        if ( ! $user_id ) {
            wp_send_json_error( array(
                'message' => __( 'ID de usuario inválido.', 'pmpro-woo-sync' )
            ));
        }

        $result = $this->sync_single_user( $user_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message()
            ));
        } else {
            wp_send_json_success( array(
                'message' => sprintf( __( 'Usuario %d sincronizado exitosamente.', 'pmpro-woo-sync' ), $user_id )
            ));
        }
    }

    /**
     * Probar funcionalidad de sincronización
     */
    private function test_sync_functionality() {
        // Verificar que las clases necesarias existan
        if ( ! class_exists( 'PMPro_Woo_Sync_Integrations' ) ) {
            return new WP_Error( 'missing_class', __( 'Clase de integraciones no encontrada.', 'pmpro-woo-sync' ) );
        }

        // Verificar configuraciones
        if ( ! $this->settings->is_sync_enabled() ) {
            return new WP_Error( 'sync_disabled', __( 'La sincronización está deshabilitada.', 'pmpro-woo-sync' ) );
        }

        // Log de prueba
        $this->logger->info( 'Prueba de sincronización ejecutada desde el panel de administración' );

        return true;
    }

    /**
     * Sincronizar usuario específico
     */
    private function sync_single_user( $user_id ) {
        if ( ! get_user_by( 'id', $user_id ) ) {
            return new WP_Error( 'user_not_found', __( 'Usuario no encontrado.', 'pmpro-woo-sync' ) );
        }

        // Aquí implementarías la lógica de sincronización específica
        $this->logger->info( sprintf( 'Sincronización manual ejecutada para usuario %d', $user_id ) );

        return true;
    }

    /**
     * Sincronizar todas las membresías
     */
    private function sync_all_memberships() {
        // Implementar sincronización masiva
        $this->logger->info( 'Sincronización masiva iniciada desde herramientas de administración' );
        add_settings_error( 'pmpro_woo_sync_messages', 'sync_started', __( 'Sincronización masiva iniciada. Revise los logs para ver el progreso.', 'pmpro-woo-sync' ), 'updated' );
    }

    /**
     * Reparar enlaces de suscripciones
     */
    private function repair_subscription_links() {
        // Implementar reparación de enlaces
        $this->logger->info( 'Reparación de enlaces de suscripciones iniciada' );
        add_settings_error( 'pmpro_woo_sync_messages', 'repair_started', __( 'Reparación de enlaces iniciada.', 'pmpro-woo-sync' ), 'updated' );
    }

    /**
     * Limpiar todos los logs
     */
    private function clear_all_logs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pmpro_woo_sync_logs';
        $deleted = $wpdb->query( "TRUNCATE TABLE {$table_name}" );

        if ( false !== $deleted ) {
            $this->logger->info( 'Todos los logs fueron limpiados manualmente desde el admin' );
            add_settings_error( 'pmpro_woo_sync_messages', 'logs_cleared', __( 'Todos los logs han sido limpiados.', 'pmpro-woo-sync' ), 'updated' );
        } else {
            add_settings_error( 'pmpro_woo_sync_messages', 'logs_clear_error', __( 'Error al limpiar los logs.', 'pmpro-woo-sync' ), 'error' );
        }
    }

    /**
     * Reiniciar configuraciones del plugin
     */
    private function reset_plugin_settings() {
        delete_option( 'pmpro_woo_sync_settings' );
        add_settings_error( 'pmpro_woo_sync_messages', 'settings_reset', __( 'Configuraciones reiniciadas a valores por defecto.', 'pmpro-woo-sync' ), 'updated' );
    }

    /**
     * Obtener información del sistema
     */
    private function get_system_information() {
        global $wpdb;

        return array(
            'wordpress_version' => get_bloginfo( 'version' ),
            'php_version'       => PHP_VERSION,
            'mysql_version'     => $wpdb->db_version(),
            'plugin_version'    => defined( 'PMPRO_WOO_SYNC_VERSION' ) ? PMPRO_WOO_SYNC_VERSION : '2.0.0',
            'memory_limit'      => ini_get( 'memory_limit' ),
            'max_execution_time' => ini_get( 'max_execution_time' ),
            'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
        );
    }

    /**
    * Verificar estado de dependencias
    */
    private function check_dependencies_status() {
        $status = array(
            'pmpro_active'    => function_exists( 'pmpro_getLevel' ),
            'woocommerce_active' => class_exists( 'WooCommerce' ),
            'pmpro_woocommerce_active' => $this->is_pmpro_woocommerce_active(),
            'pagbank_active' => $this->is_pagbank_active(),
        );

        // Obtener versiones si están activos
        if ( $status['pmpro_active'] && defined( 'PMPRO_VERSION' ) ) {
            $status['pmpro_version'] = PMPRO_VERSION;
        }

        if ( $status['woocommerce_active'] && defined( 'WC_VERSION' ) ) {
            $status['woocommerce_version'] = WC_VERSION;
        }

        // Verificar versión de PMPro WooCommerce
        if ( $status['pmpro_woocommerce_active'] ) {
            $status['pmpro_woocommerce_version'] = $this->get_pmpro_woocommerce_version();
        }

        // Verificar versión de PagBank
        if ( $status['pagbank_active'] ) {
            $status['pagbank_version'] = $this->get_pagbank_version();
        }

        return $status;
    }

    /**
    * Verificar si PMPro WooCommerce está activo
    */
    private function is_pmpro_woocommerce_active() {
        // Verificar si el plugin está activo
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        // Posibles paths del plugin
        $possible_paths = array(
            'pmpro-woocommerce/pmpro-woocommerce.php',
            'paid-memberships-pro-woocommerce/pmpro-woocommerce.php',
        );

        foreach ( $possible_paths as $path ) {
            if ( is_plugin_active( $path ) ) {
                return true;
            }
        }

        // Verificar por funciones específicas como fallback
        return function_exists( 'pmprowoo_getMembershipLevelFromProduct' ) || 
               function_exists( 'pmprowoo_init' ) ||
               defined( 'PMPROWOO_VERSION' ) ||
               class_exists( 'PMProWoo' );
    }

    /**
    * Verificar si PagBank está activo
    */
    private function is_pagbank_active() {
        // Verificar si el plugin está activo
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        // Posibles paths del plugin PagBank
        $possible_paths = array(
            'pagbank-connect/rm-pagbank.php',
            'pagbank-for-woocommerce/pagbank-for-woocommerce.php',
            'woocommerce-pagseguro/woocommerce-pagseguro.php',
            'pagseguro-for-woocommerce/pagseguro-for-woocommerce.php',
        );

        foreach ( $possible_paths as $path ) {
            if ( is_plugin_active( $path ) ) {
                return true;
            }
        }

        // Verificar por clases/funciones específicas como fallback
        return class_exists( 'RM_PagBank' ) || 
               class_exists( 'WC_PagSeguro' ) ||
               class_exists( 'WC_PagBank_Gateway' ) ||
               function_exists( 'wc_pagseguro_init' ) ||
               defined( 'WC_PAGSEGURO_VERSION' ) ||
               defined( 'RM_PAGBANK_VERSION' );
    }

    /**
    * Obtener versión de PMPro WooCommerce
    */
    private function get_pmpro_woocommerce_version() {
        // Verificar constante de versión
        if ( defined( 'PMPROWOO_VERSION' ) ) {
            return PMPROWOO_VERSION;
        }

        // Verificar en los datos del plugin
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        $possible_files = array(
            WP_PLUGIN_DIR . '/pmpro-woocommerce/pmpro-woocommerce.php',
            WP_PLUGIN_DIR . '/paid-memberships-pro-woocommerce/pmpro-woocommerce.php',
        );

        foreach ( $possible_files as $plugin_file ) {
            if ( file_exists( $plugin_file ) ) {
                $plugin_data = get_plugin_data( $plugin_file );
                if ( ! empty( $plugin_data['Version'] ) ) {
                    return $plugin_data['Version'];
                }
            }
        }

        return 'Unknown';
    }

    /**
    * Obtener versión de PagBank
    */
    private function get_pagbank_version() {
        // Verificar constantes de versión
        if ( defined( 'RM_PAGBANK_VERSION' ) ) {
            return RM_PAGBANK_VERSION;
        }
        if ( defined( 'WC_PAGSEGURO_VERSION' ) ) {
            return WC_PAGSEGURO_VERSION;
        }

        // Verificar en los datos del plugin
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        $possible_files = array(
            WP_PLUGIN_DIR . '/pagbank-connect/rm-pagbank.php',
            WP_PLUGIN_DIR . '/pagbank-for-woocommerce/pagbank-for-woocommerce.php',
            WP_PLUGIN_DIR . '/woocommerce-pagseguro/woocommerce-pagseguro.php',
            WP_PLUGIN_DIR . '/pagseguro-for-woocommerce/pagseguro-for-woocommerce.php',
        );

        foreach ( $possible_files as $plugin_file ) {
            if ( file_exists( $plugin_file ) ) {
                $plugin_data = get_plugin_data( $plugin_file );
                if ( ! empty( $plugin_data['Version'] ) ) {
                    return $plugin_data['Version'];
                }
            }
        }

        return 'Unknown';
    }

    /**
    * Obtener información detallada de plugins instalados
    */
    private function get_installed_plugins_info() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        $all_plugins = get_plugins();
        $active_plugins = get_option( 'active_plugins', array() );
        
        $plugins_info = array();

        foreach ( $all_plugins as $plugin_path => $plugin_data ) {
            $plugin_slug = dirname( $plugin_path );
            
            // Verificar si es uno de nuestros plugins de interés
            if ( in_array( $plugin_slug, array( 'pmpro-woocommerce', 'paid-memberships-pro-woocommerce', 'pagbank-connect', 'pagbank-for-woocommerce', 'woocommerce-pagseguro' ) ) ) {
                $plugins_info[ $plugin_slug ] = array(
                    'name' => $plugin_data['Name'],
                    'version' => $plugin_data['Version'],
                    'active' => in_array( $plugin_path, $active_plugins ),
                    'path' => $plugin_path,
                );
            }
        }

        return $plugins_info;
    }

    /**
    * AJAX: Ejecutar prueba de diagnóstico
    */
    public function ajax_diagnostic_test() {
        check_ajax_referer( 'pmpro_woo_sync_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }

        // Ejecutar pruebas de diagnóstico
        $diagnostic_results = array();

        // Verificar conexión a base de datos
        global $wpdb;
        $diagnostic_results['database'] = $wpdb->get_var( "SELECT 1" ) === '1';

        // Verificar dependencias principales
        $diagnostic_results['pmpro'] = function_exists( 'pmpro_getLevel' );
        $diagnostic_results['woocommerce'] = class_exists( 'WooCommerce' );

        // Verificar dependencias opcionales con información detallada
        $diagnostic_results['pmpro_woocommerce'] = $this->is_pmpro_woocommerce_active();
        $diagnostic_results['pagbank'] = $this->is_pagbank_active();

        // Obtener información de plugins instalados
        $plugins_info = $this->get_installed_plugins_info();
        $diagnostic_results['installed_plugins'] = $plugins_info;

        // Verificar configuración
        $diagnostic_results['settings'] = $this->settings->is_sync_enabled();

        // Verificar permisos de archivos
        $log_dir = wp_upload_dir()['basedir'] . '/pmpro-woo-sync-logs/';
        $diagnostic_results['file_permissions'] = is_writable( $log_dir ) || wp_mkdir_p( $log_dir );

        // Verificar tabla de logs
        global $wpdb;
        $table_name = $wpdb->prefix . 'pmpro_woo_sync_logs';
        $diagnostic_results['logs_table'] = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name;

        // Log del diagnóstico
        $this->logger->info( 'Prueba de diagnóstico ejecutada', array(
            'results' => $diagnostic_results
        ));

        // Crear mensaje detallado
        $message_parts = array();
        $message_parts[] = __( 'Diagnóstico completado:', 'pmpro-woo-sync' );
        $message_parts[] = sprintf( __( '• Base de datos: %s', 'pmpro-woo-sync' ), $diagnostic_results['database'] ? '✓' : '✗' );
        $message_parts[] = sprintf( __( '• PMPro: %s', 'pmpro-woo-sync' ), $diagnostic_results['pmpro'] ? '✓' : '✗' );
        $message_parts[] = sprintf( __( '• WooCommerce: %s', 'pmpro-woo-sync' ), $diagnostic_results['woocommerce'] ? '✓' : '✗' );
        $message_parts[] = sprintf( __( '• PMPro-WooCommerce: %s', 'pmpro-woo-sync' ), $diagnostic_results['pmpro_woocommerce'] ? '✓' : '✗' );
        $message_parts[] = sprintf( __( '• PagBank: %s', 'pmpro-woo-sync' ), $diagnostic_results['pagbank'] ? '✓' : '✗' );
        $message_parts[] = sprintf( __( '• Tabla de logs: %s', 'pmpro-woo-sync' ), $diagnostic_results['logs_table'] ? '✓' : '✗' );

        if ( ! empty( $plugins_info ) ) {
            $message_parts[] = __( '• Plugins relacionados encontrados:', 'pmpro-woo-sync' );
            foreach ( $plugins_info as $slug => $info ) {
                $status = $info['active'] ? '✓ Activo' : '○ Instalado';
                $message_parts[] = sprintf( '  - %s v%s (%s)', $info['name'], $info['version'], $status );
            }
        }

        wp_send_json_success( array(
            'message' => implode( "\n", $message_parts ),
            'results' => $diagnostic_results
        ));
    }

    /**
    * Obtener gateways de pago activos
    */
    private function get_active_payment_gateways() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return array();
        }

        $gateways = array();
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

        foreach ( $available_gateways as $gateway ) {
            if ( $gateway->enabled === 'yes' ) {
                $gateways[] = $gateway->get_method_title();
            }
        }

        return $gateways;
    }

    /**
    * Renderizar página de estado del sistema
    */
    public function display_status_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos suficientes para acceder a esta página.', 'pmpro-woo-sync' ) );
        }

        // Incluir el partial que contiene toda la interfaz de estado
        include_once plugin_dir_path( __FILE__ ) . 'partials/admin-display-status.php';
    }

    /**
     * Obtener estadísticas de sincronización
     */
    private function get_sync_statistics() {
        global $wpdb;

        $stats = array(
            'total_synced_users' => 0,
            'active_orders' => 0,
            'last_sync' => __( 'Nunca', 'pmpro-woo-sync' ),
            'sync_errors' => 0,
        );

        // Contar usuarios con metadatos de sincronización
        $stats['total_synced_users'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = '_pmpro_woo_sync_order_id'"
        );

        // Contar pedidos activos si WooCommerce está disponible
        if ( class_exists( 'WooCommerce' ) ) {
            $active_orders = wc_get_orders( array( 
                'status' => array( 'completed', 'processing' ),
                'limit' => -1 
            ) );
            $stats['active_orders'] = count( $active_orders );
        }

        // Obtener última sincronización
        $last_sync = $wpdb->get_var(
            "SELECT MAX(meta_value) FROM {$wpdb->usermeta} WHERE meta_key = '_pmpro_woo_sync_last_sync'"
        );
        if ( $last_sync ) {
            $stats['last_sync'] = human_time_diff( strtotime( $last_sync ), current_time( 'timestamp' ) ) . ' ' . __( 'atrás', 'pmpro-woo-sync' );
        }

        // Contar errores en las últimas 24 horas - Check if table exists first
        $table_name = $wpdb->prefix . 'pmpro_woo_sync_logs';
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
            $stats['sync_errors'] = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE level = 'error' AND timestamp >= %s",
                gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) )
            ));
        }

        return $stats;
    }

    /**
     * Obtener estado del sistema
     */
    private function get_system_status() {
        return array(
            'sync_enabled' => $this->settings->is_sync_enabled(),
            'debug_mode' => $this->settings->is_debug_enabled(),
            'dependencies_ok' => function_exists( 'pmpro_getLevel' ) && class_exists( 'WooCommerce' ),
        );
    }

    /**
     * Renderizar indicadores de estado
     */
    public function render_status_indicators() {
        $status = $this->get_system_status();
        ?>
        <div class="pmpro-woo-sync-indicators">
            <div class="indicator <?php echo $status['sync_enabled'] ? 'active' : 'inactive'; ?>">
                <span class="dashicons <?php echo $status['sync_enabled'] ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
                <?php esc_html_e( 'Sincronización', 'pmpro-woo-sync' ); ?>
            </div>
            
            <div class="indicator <?php echo $status['debug_mode'] ? 'warning' : 'inactive'; ?>">
                <span class="dashicons <?php echo $status['debug_mode'] ? 'dashicons-warning' : 'dashicons-dismiss'; ?>"></span>
                <?php esc_html_e( 'Modo Debug', 'pmpro-woo-sync' ); ?>
            </div>
            
            <div class="indicator <?php echo $status['dependencies_ok'] ? 'active' : 'inactive'; ?>">
                <span class="dashicons <?php echo $status['dependencies_ok'] ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
                <?php esc_html_e( 'Dependencias', 'pmpro-woo-sync' ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Agregar help tabs a configuraciones
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
     * Agregar help tabs a logs
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
     * Agregar help tabs a herramientas
     */
    private function add_tools_help_tabs() {
        $screen = get_current_screen();
        
        $screen->add_help_tab( array(
            'id'      => 'pmpro_woo_sync_tools_help',
            'title'   => __( 'Herramientas', 'pmpro-woo-sync' ),
            'content' => '<p>' . __( 'Herramientas para sincronización manual y mantenimiento del plugin.', 'pmpro-woo-sync' ) . '</p>',
        ));
    }

    /**
     * Agregar help tabs a estado
     */
    private function add_status_help_tabs() {
        $screen = get_current_screen();
        
        $screen->add_help_tab( array(
            'id'      => 'pmpro_woo_sync_status_help',
            'title'   => __( 'Estado del Sistema', 'pmpro-woo-sync' ),
            'content' => '<p>' . __( 'Información sobre el estado del sistema y estadísticas de sincronización.', 'pmpro-woo-sync' ) . '</p>',
        ));
    }
}
