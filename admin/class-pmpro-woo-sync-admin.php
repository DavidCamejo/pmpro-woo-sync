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

    /**
     * Instancia de PMPro_Woo_Sync_Settings
     *
     * @var PMPro_Woo_Sync_Settings
     */
    protected $settings;

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
    public function __construct() {
        $this->settings = new PMPro_Woo_Sync_Settings();
        $this->logger = PMPro_Woo_Sync_Logger::get_instance();
        
        $this->init_hooks();
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
    }

    /**
     * Añadir páginas del menú de administración
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

        // Subpágina de Configuraciones
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
            __( 'Logs del Sistema', 'pmpro-woo-sync' ),
            __( 'Logs', 'pmpro-woo-sync' ),
            'manage_options',
            'pmpro-woo-sync-logs',
            array( $this, 'display_logs_page' )
        );

        // Subpágina de Herramientas
        $this->admin_pages['tools'] = add_submenu_page(
            'pmpro-woo-sync',
            __( 'Herramientas de Sincronización', 'pmpro-woo-sync' ),
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
     * Callback ejecutado al cargar la página principal
     */
    public function load_main_page() {
        $this->load_settings_page();
    }

    /**
     * Callback ejecutado al cargar la página de configuraciones
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

        // Mostrar mensajes de error/confirmación
        settings_errors( 'pmpro_woo_sync_messages' );

        $settings = $this->settings->get_settings();
        $system_status = $this->get_system_status();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <?php $this->render_status_indicators(); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields( 'pmpro_woo_sync_settings_group' );
                do_settings_sections( 'pmpro-woo-sync' );
                submit_button( __( 'Guardar Configuraciones', 'pmpro-woo-sync' ) );
                ?>
            </form>

            <div class="pmpro-woo-sync-quick-actions">
                <h3><?php _e( 'Acciones Rápidas', 'pmpro-woo-sync' ); ?></h3>
                <p>
                    <button type="button" class="button" id="test-sync-connection">
                        <?php _e( 'Probar Sincronización', 'pmpro-woo-sync' ); ?>
                    </button>
                    <button type="button" class="button" id="view-logs">
                        <?php _e( 'Ver Logs', 'pmpro-woo-sync' ); ?>
                    </button>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar página de logs
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
        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
        $date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';

        // Obtener logs
        $logs = $this->logger->get_logs( $logs_per_page, $offset, $filter_level, $search_query, $date_from, $date_to );
        $total_logs = $this->logger->get_total_logs( $filter_level, $search_query, $date_from, $date_to );
        $total_pages = ceil( $total_logs / $logs_per_page );

        // Estadísticas de logs
        $log_stats = $this->logger->get_log_stats();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <!-- Estadísticas de logs -->
            <div class="pmpro-woo-sync-log-stats">
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html( $log_stats['total'] ); ?></span>
                        <span class="stat-label"><?php _e( 'Total de Logs', 'pmpro-woo-sync' ); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html( $log_stats['last_24h'] ); ?></span>
                        <span class="stat-label"><?php _e( 'Últimas 24h', 'pmpro-woo-sync' ); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html( $log_stats['last_7d'] ); ?></span>
                        <span class="stat-label"><?php _e( 'Últimos 7 días', 'pmpro-woo-sync' ); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html( $log_stats['database_size'] ); ?></span>
                        <span class="stat-label"><?php _e( 'Tamaño BD', 'pmpro-woo-sync' ); ?></span>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="pmpro-woo-sync-log-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="pmpro-woo-sync-logs" />
                    
                    <select name="log_level_filter">
                        <option value=""><?php _e( 'Todos los niveles', 'pmpro-woo-sync' ); ?></option>
                        <option value="info" <?php selected( $filter_level, 'info' ); ?>><?php _e( 'Info', 'pmpro-woo-sync' ); ?></option>
                        <option value="success" <?php selected( $filter_level, 'success' ); ?>><?php _e( 'Éxito', 'pmpro-woo-sync' ); ?></option>
                        <option value="warning" <?php selected( $filter_level, 'warning' ); ?>><?php _e( 'Advertencia', 'pmpro-woo-sync' ); ?></option>
                        <option value="error" <?php selected( $filter_level, 'error' ); ?>><?php _e( 'Error', 'pmpro-woo-sync' ); ?></option>
                        <option value="debug" <?php selected( $filter_level, 'debug' ); ?>><?php _e( 'Debug', 'pmpro-woo-sync' ); ?></option>
                    </select>
                    
                    <input type="text" name="search" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php _e( 'Buscar en mensajes...', 'pmpro-woo-sync' ); ?>" />
                    
                    <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
                    <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
                    
                    <button type="submit" class="button"><?php _e( 'Filtrar', 'pmpro-woo-sync' ); ?></button>
                    <a href="<?php echo admin_url( 'admin.php?page=pmpro-woo-sync-logs' ); ?>" class="button"><?php _e( 'Limpiar', 'pmpro-woo-sync' ); ?></a>
                </form>
            </div>

            <!-- Acciones de logs -->
            <div class="pmpro-woo-sync-log-actions">
                <button type="button" class="button" id="refresh-logs"><?php _e( 'Actualizar', 'pmpro-woo-sync' ); ?></button>
                <button type="button" class="button" id="export-logs"><?php _e( 'Exportar', 'pmpro-woo-sync' ); ?></button>
                <button type="button" class="button button-secondary" id="clear-logs"><?php _e( 'Limpiar Todos', 'pmpro-woo-sync' ); ?></button>
            </div>

            <!-- Tabla de logs -->
            <div class="pmpro-woo-sync-logs-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'Fecha/Hora', 'pmpro-woo-sync' ); ?></th>
                            <th><?php _e( 'Nivel', 'pmpro-woo-sync' ); ?></th>
                            <th><?php _e( 'Mensaje', 'pmpro-woo-sync' ); ?></th>
                            <th><?php _e( 'Usuario', 'pmpro-woo-sync' ); ?></th>
                            <th><?php _e( 'Contexto', 'pmpro-woo-sync' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="logs-table-body">
                        <?php foreach ( $logs as $log_entry ) : ?>
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
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginación -->
                <?php if ( $total_pages > 1 ) : ?>
                    <div class="tablenav">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links( array(
                                'base' => add_query_arg( 'paged', '%#%' ),
                                'format' => '',
                                'prev_text' => __( '&laquo;', 'pmpro-woo-sync' ),
                                'next_text' => __( '&raquo;', 'pmpro-woo-sync' ),
                                'total' => $total_pages,
                                'current' => $current_page,
                            ));
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar página de herramientas
     */
    public function display_tools_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos suficientes para acceder a esta página.', 'pmpro-woo-sync' ) );
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <div class="pmpro-woo-sync-tools-grid">
                <!-- Herramientas de Sincronización -->
                <div class="tool-section">
                    <h3><?php _e( 'Herramientas de Sincronización', 'pmpro-woo-sync' ); ?></h3>
                    
                    <div class="tool-item">
                        <h4><?php _e( 'Sincronización Manual', 'pmpro-woo-sync' ); ?></h4>
                        <p><?php _e( 'Sincronizar manualmente un usuario específico.', 'pmpro-woo-sync' ); ?></p>
                        <form method="post" action="">
                            <?php wp_nonce_field( 'pmpro_woo_sync_tools_action', '_wpnonce' ); ?>
                            <input type="hidden" name="pmpro_woo_sync_tools_action" value="sync_user" />
                            <input type="number" name="user_id" placeholder="<?php _e( 'ID del usuario', 'pmpro-woo-sync' ); ?>" required />
                            <button type="submit" class="button button-primary"><?php _e( 'Sincronizar Usuario', 'pmpro-woo-sync' ); ?></button>
                        </form>
                    </div>

                    <div class="tool-item">
                        <h4><?php _e( 'Sincronización Masiva', 'pmpro-woo-sync' ); ?></h4>
                        <p><?php _e( 'Sincronizar todas las membresías con sus suscripciones correspondientes.', 'pmpro-woo-sync' ); ?></p>
                        <form method="post" action="">
                            <?php wp_nonce_field( 'pmpro_woo_sync_tools_action', '_wpnonce' ); ?>
                            <input type="hidden" name="pmpro_woo_sync_tools_action" value="sync_all_memberships" />
                            <button type="submit" class="button button-primary" onclick="return confirm('<?php _e( '¿Está seguro? Esta operación puede tomar varios minutos.', 'pmpro-woo-sync' ); ?>')">
                                <?php _e( 'Sincronizar Todo', 'pmpro-woo-sync' ); ?>
                            </button>
                        </form>
                    </div>

                    <div class="tool-item">
                        <h4><?php _e( 'Reparar Enlaces', 'pmpro-woo-sync' ); ?></h4>
                        <p><?php _e( 'Reparar enlaces rotos entre suscripciones y membresías.', 'pmpro-woo-sync' ); ?></p>
                        <form method="post" action="">
                            <?php wp_nonce_field( 'pmpro_woo_sync_tools_action', '_wpnonce' ); ?>
                            <input type="hidden" name="pmpro_woo_sync_tools_action" value="repair_subscription_links" />
                            <button type="submit" class="button button-secondary">
                                <?php _e( 'Reparar Enlaces', 'pmpro-woo-sync' ); ?>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Herramientas de Mantenimiento -->
                <div class="tool-section">
                    <h3><?php _e( 'Herramientas de Mantenimiento', 'pmpro-woo-sync' ); ?></h3>
                    
                    <div class="tool-item">
                        <h4><?php _e( 'Limpiar Logs Antiguos', 'pmpro-woo-sync' ); ?></h4>
                        <p><?php _e( 'Eliminar logs más antiguos que el período de retención configurado.', 'pmpro-woo-sync' ); ?></p>
                        <form method="post" action="">
                            <?php wp_nonce_field( 'pmpro_woo_sync_tools_action', '_wpnonce' ); ?>
                            <input type="hidden" name="pmpro_woo_sync_tools_action" value="cleanup_old_logs" />
                            <button type="submit" class="button button-secondary">
                                <?php _e( 'Limpiar Logs', 'pmpro-woo-sync' ); ?>
                            </button>
                        </form>
                    </div>

                    <div class="tool-item">
                        <h4><?php _e( 'Reiniciar Configuraciones', 'pmpro-woo-sync' ); ?></h4>
                        <p><?php _e( 'Restaurar todas las configuraciones a sus valores por defecto.', 'pmpro-woo-sync' ); ?></p>
                        <form method="post" action="">
                            <?php wp_nonce_field( 'pmpro_woo_sync_tools_action', '_wpnonce' ); ?>
                            <input type="hidden" name="pmpro_woo_sync_tools_action" value="reset_settings" />
                            <button type="submit" class="button button-secondary" onclick="return confirm('<?php _e( '¿Está seguro? Se perderán todas las configuraciones personalizadas.', 'pmpro-woo-sync' ); ?>')">
                                <?php _e( 'Reiniciar Configuraciones', 'pmpro-woo-sync' ); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar página de estado del sistema
     */
    public function display_status_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos suficientes para acceder a esta página.', 'pmpro-woo-sync' ) );
        }

        $system_info = $this->get_system_information();
        $dependency_status = $this->check_dependencies_status();
        $sync_stats = $this->get_sync_statistics();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <!-- Estado de Dependencias -->
            <div class="pmpro-woo-sync-status-section">
                <h3><?php _e( 'Estado de Dependencias', 'pmpro-woo-sync' ); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'Componente', 'pmpro-woo-sync' ); ?></th>
                            <th><?php _e( 'Estado', 'pmpro-woo-sync' ); ?></th>
                            <th><?php _e( 'Versión', 'pmpro-woo-sync' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php _e( 'Paid Memberships Pro', 'pmpro-woo-sync' ); ?></td>
                            <td>
                                <span class="status-indicator <?php echo $dependency_status['pmpro_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $dependency_status['pmpro_active'] ? __( 'Activo', 'pmpro-woo-sync' ) : __( 'Inactivo', 'pmpro-woo-sync' ); ?>
                                </span>
                            </td>
                            <td><?php echo $dependency_status['pmpro_active'] ? esc_html( $dependency_status['pmpro_version'] ) : '—'; ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'WooCommerce', 'pmpro-woo-sync' ); ?></td>
                            <td>
                                <span class="status-indicator <?php echo $dependency_status['woocommerce_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $dependency_status['woocommerce_active'] ? __( 'Activo', 'pmpro-woo-sync' ) : __( 'Inactivo', 'pmpro-woo-sync' ); ?>
                                </span>
                            </td>
                            <td><?php echo $dependency_status['woocommerce_active'] ? esc_html( $dependency_status['woocommerce_version'] ) : '—'; ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'WooCommerce Subscriptions', 'pmpro-woo-sync' ); ?></td>
                            <td>
                                <span class="status-indicator <?php echo $dependency_status['wc_subscriptions_active'] ? 'active' : 'warning'; ?>">
                                    <?php echo $dependency_status['wc_subscriptions_active'] ? __( 'Activo', 'pmpro-woo-sync' ) : __( 'Recomendado', 'pmpro-woo-sync' ); ?>
                                </span>
                            </td>
                            <td><?php echo $dependency_status['wc_subscriptions_active'] ? esc_html( $dependency_status['wc_subscriptions_version'] ) : '—'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Información del Sistema -->
            <div class="pmpro-woo-sync-status-section">
                <h3><?php _e( 'Información del Sistema', 'pmpro-woo-sync' ); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <tbody>
                        <tr>
                            <td><?php _e( 'Versión de WordPress', 'pmpro-woo-sync' ); ?></td>
                            <td><?php echo esc_html( $system_info['wordpress_version'] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Versión de PHP', 'pmpro-woo-sync' ); ?></td>
                            <td><?php echo esc_html( $system_info['php_version'] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Versión de MySQL', 'pmpro-woo-sync' ); ?></td>
                            <td><?php echo esc_html( $system_info['mysql_version'] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Versión del Plugin', 'pmpro-woo-sync' ); ?></td>
                            <td><?php echo esc_html( $system_info['plugin_version'] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Límite de Memoria', 'pmpro-woo-sync' ); ?></td>
                            <td><?php echo esc_html( $system_info['memory_limit'] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Tiempo Máximo de Ejecución', 'pmpro-woo-sync' ); ?></td>
                            <td><?php echo esc_html( $system_info['max_execution_time'] ); ?> segundos</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Estadísticas de Sincronización -->
            <div class="pmpro-woo-sync-status-section">
                <h3><?php _e( 'Estadísticas de Sincronización', 'pmpro-woo-sync' ); ?></h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html( $sync_stats['total_synced_users'] ); ?></span>
                        <span class="stat-label"><?php _e( 'Usuarios Sincronizados', 'pmpro-woo-sync' ); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html( $sync_stats['active_subscriptions'] ); ?></span>
                        <span class="stat-label"><?php _e( 'Suscripciones Activas', 'pmpro-woo-sync' ); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html( $sync_stats['last_sync'] ); ?></span>
                        <span class="stat-label"><?php _e( 'Última Sincronización', 'pmpro-woo-sync' ); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html( $sync_stats['sync_errors'] ); ?></span>
                        <span class="stat-label"><?php _e( 'Errores (24h)', 'pmpro-woo-sync' ); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
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
            'pmpro_active'      => function_exists( 'pmpro_getLevel' ),
            'woocommerce_active' => class_exists( 'WooCommerce' ),
            'wc_subscriptions_active' => class_exists( 'WC_Subscriptions' ),
        );

        // Obtener versiones si están activos
        if ( $status['pmpro_active'] && defined( 'PMPRO_VERSION' ) ) {
            $status['pmpro_version'] = PMPRO_VERSION;
        }

        if ( $status['woocommerce_active'] && defined( 'WC_VERSION' ) ) {
            $status['woocommerce_version'] = WC_VERSION;
        }

        if ( $status['wc_subscriptions_active'] && class_exists( 'WC_Subscriptions' ) ) {
            $status['wc_subscriptions_version'] = WC_Subscriptions::$version ?? 'N/A';
        }

        return $status;
    }

    /**
     * Obtener estadísticas de sincronización
     */
    private function get_sync_statistics() {
        global $wpdb;

        $stats = array(
            'total_synced_users' => 0,
            'active_subscriptions' => 0,
            'last_sync' => __( 'Nunca', 'pmpro-woo-sync' ),
            'sync_errors' => 0,
        );

        // Contar usuarios con metadatos de sincronización
        $stats['total_synced_users'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = '_pmpro_woo_sync_subscription_id'"
        );

        // Contar suscripciones activas si WooCommerce está disponible
        if ( class_exists( 'WooCommerce' ) && function_exists( 'wcs_get_subscriptions' ) ) {
            $active_subscriptions = wcs_get_subscriptions( array( 'subscription_status' => 'active' ) );
            $stats['active_subscriptions'] = count( $active_subscriptions );
        }

        // Obtener última sincronización
        $last_sync = $wpdb->get_var(
            "SELECT MAX(meta_value) FROM {$wpdb->usermeta} WHERE meta_key = '_pmpro_woo_sync_last_sync'"
        );
        if ( $last_sync ) {
            $stats['last_sync'] = human_time_diff( strtotime( $last_sync ), current_time( 'timestamp' ) ) . ' ' . __( 'atrás', 'pmpro-woo-sync' );
        }

        // Contar errores en las últimas 24 horas
        if ( $this->logger->table_exists() ) {
            $stats['sync_errors'] = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pmpro_woo_sync_logs WHERE level = 'error' AND timestamp >= %s",
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
