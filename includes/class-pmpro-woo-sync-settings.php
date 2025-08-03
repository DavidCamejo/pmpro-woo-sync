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
        'enable_logging' => 1,
        'log_level' => 'info',
        'log_retention_days' => 30,
        'sync_direction' => 'bidirectional',
        'sync_failed_orders' => 0,
        'auto_link_products' => 1,
        'record_payments_in_pmpro' => 1,
        'level_mappings' => array(),
    );

    /**
    * Constructor
    */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
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

        // Campo: Sincronizar pedidos fallidos
        add_settings_field(
            'sync_failed_orders',
            __( 'Sincronizar Pedidos Fallidos', 'pmpro-woo-sync' ),
            array( $this, 'render_sync_failed_field' ),
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
            <?php _e( 'Sincronizar automáticamente membresías con pagos recurrentes en WooCommerce (PagBank Connect)', 'pmpro-woo-sync' ); ?>
        </label>
        <p class="description">
            <?php _e( 'Cuando está habilitado, los pagos recurrentes en PagBank Connect se reflejarán automáticamente en las membresías de PMPro.', 'pmpro-woo-sync' ); ?>
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
    * Renderizar campo de sincronizar pedidos fallidos
    */
    public function render_sync_failed_field() {
        $options = $this->get_settings();
        ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr( $this->option_name ); ?>[sync_failed_orders]" 
                   value="1" 
                   <?php checked( 1, $options['sync_failed_orders'] ); ?> />
            <?php _e( 'Cancelar membresías cuando los pedidos fallan', 'pmpro-woo-sync' ); ?>
        </label>
        <p class="description">
            <?php _e( 'Si está habilitado, las membresías se cancelarán cuando los pagos fallen.', 'pmpro-woo-sync' ); ?>
        </p>
        <?php
    }

    /**
    * Sanitizar configuraciones
    *
    * @param array $input
    * @return array
    */
    public function sanitize_settings( $input ) {
        $output = array();

        // Sanitizar campos booleanos
        $boolean_fields = array(
            'enable_sync',
            'debug_mode', 
            'enable_logging',
            'sync_failed_orders',
            'auto_link_products',
            'record_payments_in_pmpro'
        );

        foreach ( $boolean_fields as $field ) {
            $output[ $field ] = isset( $input[ $field ] ) ? 1 : 0;
        }

        // Sanitizar campos de texto
        $output['sync_direction'] = isset( $input['sync_direction'] ) 
            ? sanitize_text_field( $input['sync_direction'] ) 
            : 'bidirectional';

        $output['log_level'] = isset( $input['log_level'] ) 
            ? sanitize_text_field( $input['log_level'] ) 
            : 'info';

        // Sanitizar campo numérico
        $output['log_retention_days'] = isset( $input['log_retention_days'] ) 
            ? absint( $input['log_retention_days'] ) 
            : 30;

        // Validaciones
        if ( $output['log_retention_days'] < 1 ) {
            $output['log_retention_days'] = 1;
        }
        if ( $output['log_retention_days'] > 365 ) {
            $output['log_retention_days'] = 365;
        }

        // Validar sync_direction
        $valid_directions = array( 'bidirectional', 'pmpro_to_woo', 'woo_to_pmpro' );
        if ( ! in_array( $output['sync_direction'], $valid_directions ) ) {
            $output['sync_direction'] = 'bidirectional';
        }

        // Validar log_level
        $valid_levels = array( 'error', 'warning', 'info', 'debug' );
        if ( ! in_array( $output['log_level'], $valid_levels ) ) {
            $output['log_level'] = 'info';
        }

        // Sanitizar level_mappings si existe
        if ( isset( $input['level_mappings'] ) && is_array( $input['level_mappings'] ) ) {
            $output['level_mappings'] = array();
            foreach ( $input['level_mappings'] as $level_id => $product_id ) {
                $level_id = absint( $level_id );
                $product_id = absint( $product_id );
                if ( $level_id && $product_id ) {
                    $output['level_mappings'][ $level_id ] = $product_id;
                }
            }
        } else {
            $output['level_mappings'] = array();
        }

        // Log del cambio de configuraciones
        if ( class_exists( 'PMPro_Woo_Sync_Logger' ) ) {
            $logger = PMPro_Woo_Sync_Logger::get_instance();
            
            // Verificar si debug mode cambió
            $old_settings = $this->get_settings();
            if ( $old_settings['debug_mode'] !== $output['debug_mode'] ) {
                $logger->info( sprintf( 
                    'Modo debug %s por el administrador', 
                    $output['debug_mode'] ? 'activado' : 'desactivado' 
                ));
            }
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
    * Verificar si el logging está habilitado
    *
    * @return bool
    */
    public function is_logging_enabled() {
        return (bool) $this->get_setting( 'enable_logging', true );
    }

    /**
    * Obtener nivel de log configurado
    *
    * @return string
    */
    public function get_log_level() {
        return $this->get_setting( 'log_level', 'info' );
    }

    /**
    * Obtener días de retención de logs
    *
    * @return int
    */
    public function get_log_retention_days() {
        return (int) $this->get_setting( 'log_retention_days', 30 );
    }

    /**
    * Verificar si debe loggear según el nivel
    *
    * @param string $level
    * @return bool
    */
    public function should_log_level( $level ) {
        if ( ! $this->is_logging_enabled() ) {
            return false;
        }

        $current_level = $this->get_log_level();
        
        // Si debug está activado, loggear todo
        if ( $this->is_debug_enabled() ) {
            return true;
        }

        // Niveles jerárquicos
        $levels = array(
            'error' => 1,
            'warning' => 2,
            'info' => 3,
            'debug' => 4
        );

        $current_level_value = isset( $levels[ $current_level ] ) ? $levels[ $current_level ] : 3;
        $requested_level_value = isset( $levels[ $level ] ) ? $levels[ $level ] : 3;

        return $requested_level_value <= $current_level_value;
    }
}
