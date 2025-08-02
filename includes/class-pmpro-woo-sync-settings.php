<?php
/**
 * Clase de configuraciones del plugin
 *
 * @package PMPro_Woo_Sync
 * @since 2.0.0
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PMPro_Woo_Sync_Settings {

    /**
     * Nombre de la opción en la base de datos
     *
     * @var string
     */
    protected $option_name = 'pmpro_woo_sync_settings';

    /**
     * Configuraciones por defecto
     *
     * @var array
     */
    protected $default_settings = array(
        'enable_sync' => 1,
        'debug_mode' => 0,
        'log_retention_days' => 30,
        'sync_on_hold_subscriptions' => 1,
        'auto_link_products' => 1,
        'record_payments_in_pmpro' => 1,
    );

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
    }

    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        register_setting(
            'pmpro_woo_sync_settings_group',
            $this->option_name,
            array( $this, 'sanitize_settings' )
        );

        // Sección principal
        add_settings_section(
            'pmpro_woo_sync_main_section',
            __( 'Configuración General', 'pmpro-woo-sync' ),
            array( $this, 'main_section_callback' ),
            'pmpro-woo-sync'
        );

        // Campo: Habilitar sincronización
        add_settings_field(
            'enable_sync',
            __( 'Habilitar Sincronización', 'pmpro-woo-sync' ),
            array( $this, 'render_enable_sync_field' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_main_section'
        );

        // Campo: Modo debug
        add_settings_field(
            'debug_mode',
            __( 'Modo Debug', 'pmpro-woo-sync' ),
            array( $this, 'render_debug_mode_field' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_main_section'
        );

        // Campo: Retención de logs
        add_settings_field(
            'log_retention_days',
            __( 'Retención de Logs (días)', 'pmpro-woo-sync' ),
            array( $this, 'render_log_retention_field' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_main_section'
        );

        // Sección de sincronización avanzada
        add_settings_section(
            'pmpro_woo_sync_advanced_section',
            __( 'Configuración Avanzada', 'pmpro-woo-sync' ),
            array( $this, 'advanced_section_callback' ),
            'pmpro-woo-sync'
        );

        // Campo: Sincronizar suscripciones en espera
        add_settings_field(
            'sync_on_hold_subscriptions',
            __( 'Sincronizar Suscripciones en Espera', 'pmpro-woo-sync' ),
            array( $this, 'render_sync_on_hold_field' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_advanced_section'
        );

        // Campo: Auto-vincular productos
        add_settings_field(
            'auto_link_products',
            __( 'Auto-vincular Productos', 'pmpro-woo-sync' ),
            array( $this, 'render_auto_link_field' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_advanced_section'
        );

        // Campo: Registrar pagos en PMPro
        add_settings_field(
            'record_payments_in_pmpro',
            __( 'Registrar Pagos en PMPro', 'pmpro-woo-sync' ),
            array( $this, 'render_record_payments_field' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_advanced_section'
        );
    }

    /**
     * Agregar página de configuraciones al menú
     */
    public function add_settings_page() {
        add_options_page(
            __( 'PMPro-Woo-Sync', 'pmpro-woo-sync' ),
            __( 'PMPro-Woo-Sync', 'pmpro-woo-sync' ),
            'manage_options',
            'pmpro-woo-sync-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Callback de la sección principal
     */
    public function main_section_callback() {
        echo '<p>' . __( 'Configuraciones principales para la sincronización entre WooCommerce y Paid Memberships Pro.', 'pmpro-woo-sync' ) . '</p>';
    }

    /**
     * Callback de la sección avanzada
     */
    public function advanced_section_callback() {
        echo '<p>' . __( 'Configuraciones avanzadas para usuarios experimentados.', 'pmpro-woo-sync' ) . '</p>';
    }

    /**
     * Renderizar campo de habilitar sincronización
     */
    public function render_enable_sync_field() {
        $options = $this->get_settings();
        ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr( $this->option_name ); ?>[enable_sync]" 
                   value="1" 
                   <?php checked( 1, $options['enable_sync'] ); ?> />
            <?php _e( 'Sincronizar automáticamente membresías con suscripciones WooCommerce', 'pmpro-woo-sync' ); ?>
        </label>
        <p class="description">
            <?php _e( 'Cuando está habilitado, los cambios en las suscripciones de WooCommerce se reflejarán automáticamente en las membresías de PMPro.', 'pmpro-woo-sync' ); ?>
        </p>
        <?php
    }

    /**
     * Renderizar campo de modo debug
     */
    public function render_debug_mode_field() {
        $options = $this->get_settings();
        ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr( $this->option_name ); ?>[debug_mode]" 
                   value="1" 
                   <?php checked( 1, $options['debug_mode'] ); ?> />
            <?php _e( 'Habilitar logging detallado para troubleshooting', 'pmpro-woo-sync' ); ?>
        </label>
        <p class="description">
            <?php _e( 'Solo habilitar temporalmente para diagnosticar problemas. Puede generar muchos logs.', 'pmpro-woo-sync' ); ?>
        </p>
        <?php
    }

    /**
     * Renderizar campo de retención de logs
     */
    public function render_log_retention_field() {
        $options = $this->get_settings();
        ?>
        <input type="number" 
               name="<?php echo esc_attr( $this->option_name ); ?>[log_retention_days]" 
               value="<?php echo esc_attr( $options['log_retention_days'] ); ?>" 
               min="1" 
               max="365" 
               class="small-text" />
        <p class="description">
            <?php _e( 'Número de días que se conservarán los logs antes de ser eliminados automáticamente.', 'pmpro-woo-sync' ); ?>
        </p>
        <?php
    }

    /**
     * Renderizar campo de sincronizar suscripciones en espera
     */
    public function render_sync_on_hold_field() {
        $options = $this->get_settings();
        ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr( $this->option_name ); ?>[sync_on_hold_subscriptions]" 
                   value="1" 
                   <?php checked( 1, $options['sync_on_hold_subscriptions'] ); ?> />
            <?php _e( 'Cancelar membresías cuando las suscripciones están en espera', 'pmpro-woo-sync' ); ?>
        </label>
        <p class="description">
            <?php _e( 'Si está deshabilitado, las suscripciones en espera no afectarán las membresías activas.', 'pmpro-woo-sync' ); ?>
        </p>
        <?php
    }

    /**
     * Renderizar campo de auto-vincular productos
     */
    public function render_auto_link_field() {
        $options = $this->get_settings();
        ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr( $this->option_name ); ?>[auto_link_products]" 
                   value="1" 
                   <?php checked( 1, $options['auto_link_products'] ); ?> />
            <?php _e( 'Vincular automáticamente productos con niveles de membresía', 'pmpro-woo-sync' ); ?>
        </label>
        <p class="description">
            <?php _e( 'Busca automáticamente la vinculación entre productos WooCommerce y niveles PMPro.', 'pmpro-woo-sync' ); ?>
        </p>
        <?php
    }

    /**
     * Renderizar campo de registrar pagos en PMPro
     */
    public function render_record_payments_field() {
        $options = $this->get_settings();
        ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr( $this->option_name ); ?>[record_payments_in_pmpro]" 
                   value="1" 
                   <?php checked( 1, $options['record_payments_in_pmpro'] ); ?> />
            <?php _e( 'Registrar pagos de renovación en el historial de PMPro', 'pmpro-woo-sync' ); ?>
        </label>
        <p class="description">
            <?php _e( 'Crea registros de pago en PMPro cuando se procesan renovaciones exitosas.', 'pmpro-woo-sync' ); ?>
        </p>
        <?php
    }

    /**
     * Renderizar página de configuraciones
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Mostrar mensajes de actualización
        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error(
                'pmpro_woo_sync_messages',
                'pmpro_woo_sync_message',
                __( 'Configuraciones guardadas.', 'pmpro-woo-sync' ),
                'updated'
            );
        }

        settings_errors( 'pmpro_woo_sync_messages' );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <div class="pmpro-woo-sync-settings-header">
                <p><?php _e( 'Configura la sincronización automática entre WooCommerce Subscriptions y Paid Memberships Pro.', 'pmpro-woo-sync' ); ?></p>
            </div>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'pmpro_woo_sync_settings_group' );
                do_settings_sections( 'pmpro-woo-sync' );
                submit_button( __( 'Guardar Configuraciones', 'pmpro-woo-sync' ) );
                ?>
            </form>

            <div class="pmpro-woo-sync-settings-footer">
                <h3><?php _e( 'Estado del Sistema', 'pmpro-woo-sync' ); ?></h3>
                <?php $this->render_system_status(); ?>
            </div>
        </div>

        <style>
        .pmpro-woo-sync-settings-header {
            background: #f1f1f1;
            padding: 15px;
            border-left: 4px solid #0073aa;
            margin: 20px 0;
        }
        .pmpro-woo-sync-settings-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .system-status-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .status-ok { background-color: #46b450; }
        .status-warning { background-color: #ffb900; }
        .status-error { background-color: #dc3232; }
        </style>
        <?php
    }

    /**
     * Renderizar estado del sistema
     */
    private function render_system_status() {
        $status_items = array(
            array(
                'label' => __( 'Paid Memberships Pro', 'pmpro-woo-sync' ),
                'status' => function_exists( 'pmpro_getLevel' ) ? 'ok' : 'error',
                'message' => function_exists( 'pmpro_getLevel' ) ? __( 'Activo', 'pmpro-woo-sync' ) : __( 'No instalado', 'pmpro-woo-sync' ),
            ),
            array(
                'label' => __( 'WooCommerce', 'pmpro-woo-sync' ),
                'status' => class_exists( 'WooCommerce' ) ? 'ok' : 'error',
                'message' => class_exists( 'WooCommerce' ) ? __( 'Activo', 'pmpro-woo-sync' ) : __( 'No instalado', 'pmpro-woo-sync' ),
            ),
            array(
                'label' => __( 'WooCommerce Subscriptions', 'pmpro-woo-sync' ),
                'status' => class_exists( 'WC_Subscriptions' ) ? 'ok' : 'warning',
                'message' => class_exists( 'WC_Subscriptions' ) ? __( 'Activo', 'pmpro-woo-sync' ) : __( 'Recomendado', 'pmpro-woo-sync' ),
            ),
            array(
                'label' => __( 'Sincronización', 'pmpro-woo-sync' ),
                'status' => $this->is_sync_enabled() ? 'ok' : 'warning',
                'message' => $this->is_sync_enabled() ? __( 'Habilitada', 'pmpro-woo-sync' ) : __( 'Deshabilitada', 'pmpro-woo-sync' ),
            ),
        );

        foreach ( $status_items as $item ) {
            echo '<div class="system-status-item">';
            echo '<span class="status-indicator status-' . esc_attr( $item['status'] ) . '"></span>';
            echo '<strong>' . esc_html( $item['label'] ) . ':</strong> ';
            echo esc_html( $item['message'] );
            echo '</div>';
        }
    }

    /**
     * Sanitizar configuraciones
     *
     * @param array $input
     * @return array
     */
    public function sanitize_settings( $input ) {
        $output = array();

        // Sanitizar cada campo
        $output['enable_sync'] = isset( $input['enable_sync'] ) ? 1 : 0;
        $output['debug_mode'] = isset( $input['debug_mode'] ) ? 1 : 0;
        $output['log_retention_days'] = isset( $input['log_retention_days'] ) ? absint( $input['log_retention_days'] ) : 30;
        $output['sync_on_hold_subscriptions'] = isset( $input['sync_on_hold_subscriptions'] ) ? 1 : 0;
        $output['auto_link_products'] = isset( $input['auto_link_products'] ) ? 1 : 0;
        $output['record_payments_in_pmpro'] = isset( $input['record_payments_in_pmpro'] ) ? 1 : 0;

        // Validaciones
        if ( $output['log_retention_days'] < 1 ) {
            $output['log_retention_days'] = 1;
        }
        if ( $output['log_retention_days'] > 365 ) {
            $output['log_retention_days'] = 365;
        }

        return $output;
    }

    /**
     * Obtener configuraciones con valores por defecto
     *
     * @return array
     */
    public function get_settings() {
        $settings = get_option( $this->option_name, array() );
        return wp_parse_args( $settings, $this->default_settings );
    }

    /**
     * Obtener una configuración específica
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get_setting( $key, $default = null ) {
        $settings = $this->get_settings();
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

    /**
     * Verificar si el modo debug está habilitado
     *
     * @return bool
     */
    public function is_debug_enabled() {
        return (bool) $this->get_setting( 'debug_mode', false );
    }

    /**
     * Obtener días de retención de logs
     *
     * @return int
     */
    public function get_log_retention_days() {
        return (int) $this->get_setting( 'log_retention_days', 30 );
    }
}
