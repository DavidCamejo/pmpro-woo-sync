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
        add_action( 'admin_init', array( $this, 'maybe_update_logs_table' ) ); // AGREGADO
    }

    /**
    * Verificar y actualizar estructura de tabla de logs
    */
    public function maybe_update_logs_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pmpro_woo_sync_logs';
        
        // Verificar si la columna user_id existe
        $column_exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'user_id'",
            DB_NAME, $table_name
        ));
        
        if ( ! $column_exists ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN user_id bigint(20) DEFAULT NULL AFTER id" );
            error_log('PMPro Woo Sync: Agregada columna user_id a tabla de logs');
        }
    }

    /**
    * Sanitizar configuraciones
    *
    * @param array $input
    * @return array
    */
    public function sanitize_settings( $input ) {
        // DEBUGGING SIMPLIFICADO - opcional mantener temporalmente
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log('SANITIZE: Input=' . print_r($input, true));
        }

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

        // FIX: Corregido el procesamiento de checkboxes
        // Los checkboxes no se envían cuando están desmarcados, pero 
        // tenemos hidden fields con valor 0 antes de cada checkbox
        foreach ( $boolean_fields as $field ) {
            // Si el campo existe y tiene valor 1, entonces está marcado
            // Si no existe o tiene otro valor, no está marcado
            $output[ $field ] = (isset( $input[ $field ] ) && $input[ $field ] == 1) ? 1 : 0;
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

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log('SANITIZE: Output=' . print_r($output, true));
        }

        return $output;
    }

    /**
     * Obtener configuraciones con valores por defecto
     *
     * @return array
     */
    public function get_settings() {
        $settings = get_option( 'pmpro_woo_sync_settings', array() );
        $merged_settings = wp_parse_args( $settings, $this->default_settings );
        
        // DEBUG: Log para verificar valores
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'PMPro-Woo-Sync Settings: ' . print_r( $merged_settings, true ) );
        }
        
        return $merged_settings;
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
        return (bool) $this->get_setting( 'debug_mode', 0 );
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
