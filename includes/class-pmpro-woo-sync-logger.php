<?php
/**
 * Clase para el sistema de logs del plugin PMPro-Woo-Sync
 *
 * @package PMPro_Woo_Sync
 * @since 2.0.0
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PMPro_Woo_Sync_Logger {

    /**
     * Instancia única del logger
     *
     * @var PMPro_Woo_Sync_Logger
     */
    private static $instance = null;

    /**
     * Nombre de la tabla de logs
     *
     * @var string
     */
    protected $table_name;

    /**
     * Directorio de archivos de log
     *
     * @var string
     */
    protected $log_dir;

    /**
     * Configuraciones del plugin
     *
     * @var PMPro_Woo_Sync_Settings
     */
    protected $settings;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'pmpro_woo_sync_logs';
        $this->log_dir = WP_CONTENT_DIR . '/uploads/pmpro-woo-sync-logs/';
        
        // Obtener instancia de configuraciones
        if ( class_exists( 'PMPro_Woo_Sync_Settings' ) ) {
            $this->settings = new PMPro_Woo_Sync_Settings();
        }
        
        // Asegurar que el directorio de logs existe
        $this->ensure_log_directory();
        
        // Programar limpieza automática
        $this->schedule_cleanup();
    }

    /**
     * Obtener instancia única del logger
     *
     * @return PMPro_Woo_Sync_Logger
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Método estático para logging rápido
     *
     * @param string $message
     * @param string $level
     * @param array $context
     */
    public static function log( $message, $level = 'info', $context = array() ) {
        $logger = self::get_instance();
        $logger->write_log( $level, $message, $context );
    }

    /**
     * Asegurar que el directorio de logs existe y está protegido
     */
    private function ensure_log_directory() {
        if ( ! file_exists( $this->log_dir ) ) {
            wp_mkdir_p( $this->log_dir );
        }
        
        // Crear archivo .htaccess para proteger los logs
        $htaccess_file = $this->log_dir . '.htaccess';
        if ( ! file_exists( $htaccess_file ) ) {
            $htaccess_content = "# Proteger logs de PMPro-Woo-Sync\n";
            $htaccess_content .= "Order deny,allow\n";
            $htaccess_content .= "Deny from all\n";
            $htaccess_content .= "<Files ~ \"\.log$\">\n";
            $htaccess_content .= "    Order deny,allow\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents( $htaccess_file, $htaccess_content );
        }

        // Crear archivo index.php para mayor seguridad
        $index_file = $this->log_dir . 'index.php';
        if ( ! file_exists( $index_file ) ) {
            file_put_contents( $index_file, "<?php\n// Silence is golden.\n" );
        }
    }

    /**
     * Escribir entrada de log
     *
     * @param string $level Nivel del log
     * @param string $message Mensaje
     * @param array $context Contexto adicional
     * @param string $method Método que llama al log
     */
    public function write_log( $level, $message, $context = array(), $method = '' ) {
        // Validar nivel de log
        $valid_levels = array( 'info', 'warning', 'error', 'debug', 'success' );
        if ( ! in_array( $level, $valid_levels ) ) {
            $level = 'info';
        }

        // Verificar si el logging está habilitado
        if ( ! $this->is_logging_enabled() ) {
            return;
        }

        // Si el modo debug no está activo, no registrar mensajes de debug
        if ( 'debug' === $level && ! $this->is_debug_enabled() ) {
            return;
        }

        // Preparar contexto
        $context = $this->prepare_context( $context, $method );

        // Registrar en base de datos si la tabla existe
        if ( $this->table_exists() ) {
            $this->log_to_database( $level, $message, $context );
        }

        // Registrar en archivo
        $this->log_to_file( $level, $message, $context );

        // Log crítico también al error_log de PHP
        if ( 'error' === $level && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( sprintf( 
                'PMPro-Woo-Sync [%s]: %s | Context: %s', 
                strtoupper( $level ), 
                $message, 
                wp_json_encode( $context, JSON_UNESCAPED_UNICODE ) 
            ));
        }

        // Disparar acción para extensibilidad
        do_action( 'pmpro_woo_sync_log_entry', $level, $message, $context );
    }

    /**
     * Preparar contexto del log
     *
     * @param array $context
     * @param string $method
     * @return array
     */
    private function prepare_context( $context, $method = '' ) {
        // Agregar información del método si se proporciona
        if ( ! empty( $method ) ) {
            $context['calling_method'] = $method;
        }

        // Agregar información de contexto adicional
        $context['timestamp'] = current_time( 'mysql' );
        $context['user_id'] = get_current_user_id();
        $context['ip_address'] = $this->get_client_ip();
        
        // Solo agregar URI si estamos en una petición web
        if ( ! wp_doing_cron() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
            $context['request_uri'] = isset( $_SERVER['REQUEST_URI'] ) ? 
                sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';
        }

        // Agregar información de memoria si está en modo debug
        if ( $this->is_debug_enabled() ) {
            $context['memory_usage'] = size_format( memory_get_usage( true ) );
            $context['memory_peak'] = size_format( memory_get_peak_usage( true ) );
        }

        return $context;
    }

    /**
     * Obtener IP del cliente
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = array( 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' );
        
        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( $_SERVER[ $key ] );
                // Tomar solo la primera IP si hay múltiples
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                    return $ip;
                }
            }
        }
        
        return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : 'unknown';
    }

    /**
     * Verificar si la tabla de logs existe
     *
     * @return bool
     */
    private function table_exists() {
        global $wpdb;
        $table_name = $wpdb->get_var( $wpdb->prepare( 
            "SHOW TABLES LIKE %s", 
            $this->table_name 
        ));
        return $table_name === $this->table_name;
    }

    /**
     * Registrar en la base de datos
     *
     * @param string $level
     * @param string $message
     * @param array $context
     */
    private function log_to_database( $level, $message, $context ) {
        global $wpdb;

        // Ensure context can be safely encoded
        $context_json = wp_json_encode( $context, JSON_UNESCAPED_UNICODE );
        if ( false === $context_json ) {
            $context_json = wp_json_encode( array( 'error' => 'Failed to encode context data' ) );
        }

        $data = array(
            'timestamp' => current_time( 'mysql' ),
            'level'     => $level,
            'message'   => $message,
            'context'   => $context_json,
            'user_id'   => get_current_user_id(),
        );

        $format = array( '%s', '%s', '%s', '%s', '%d' );

        $result = $wpdb->insert( $this->table_name, $data, $format );
        
        if ( false === $result && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'PMPro-Woo-Sync: Error al insertar log en base de datos: ' . $wpdb->last_error );
        }
    }

    /**
     * Registrar en archivo de texto
     *
     * @param string $level
     * @param string $message
     * @param array $context
     */
    private function log_to_file( $level, $message, $context ) {
        $log_file = $this->log_dir . 'pmpro-woo-sync-' . gmdate( 'Y-m-d' ) . '.log';
        
        $log_entry = sprintf(
            "[%s] [%s] %s | Context: %s\n",
            gmdate( 'Y-m-d H:i:s' ),
            strtoupper( $level ),
            $message,
            wp_json_encode( $context, JSON_UNESCAPED_UNICODE )
        );

        // Usar file_put_contents con LOCK_EX para evitar problemas de concurrencia
        $result = file_put_contents( $log_file, $log_entry, FILE_APPEND | LOCK_EX );
        
        if ( false === $result && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'PMPro-Woo-Sync: Error al escribir en archivo de log: ' . $log_file );
        }
    }

    /**
     * Métodos de conveniencia para diferentes niveles de log
     */

    public function info( $message, $context = array(), $method = '' ) {
        $this->write_log( 'info', $message, $context, $method );
    }

    public function success( $message, $context = array(), $method = '' ) {
        $this->write_log( 'success', $message, $context, $method );
    }

    public function warning( $message, $context = array(), $method = '' ) {
        $this->write_log( 'warning', $message, $context, $method );
    }

    public function error( $message, $context = array(), $method = '' ) {
        $this->write_log( 'error', $message, $context, $method );
    }

    public function debug( $message, $context = array(), $method = '' ) {
        $this->write_log( 'debug', $message, $context, $method );
    }

    /**
     * Obtener logs de la base de datos
     *
     * @param int $limit
     * @param int $offset
     * @param string $level
     * @param string $search
     * @param string $date_from
     * @param string $date_to
     * @return array
     */
    public function get_logs( $limit = 20, $offset = 0, $level = '', $search = '', $date_from = '', $date_to = '' ) {
        global $wpdb;
        
        if ( ! $this->table_exists() ) {
            return array();
        }
        
        $where_clauses = array();
        $params = array();

        if ( ! empty( $level ) ) {
            $where_clauses[] = 'level = %s';
            $params[] = $level;
        }

        if ( ! empty( $search ) ) {
            $where_clauses[] = 'message LIKE %s';
            $params[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        if ( ! empty( $date_from ) ) {
            $where_clauses[] = 'timestamp >= %s';
            $params[] = $date_from . ' 00:00:00';
        }

        if ( ! empty( $date_to ) ) {
            $where_clauses[] = 'timestamp <= %s';
            $params[] = $date_to . ' 23:59:59';
        }

        $where_sql = '';
        if ( ! empty( $where_clauses ) ) {
            $where_sql = ' WHERE ' . implode( ' AND ', $where_clauses );
        }

        $sql = "SELECT * FROM {$this->table_name}{$where_sql} ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        $prepared_sql = $wpdb->prepare( $sql, $params );
        return $wpdb->get_results( $prepared_sql );
    }

    /**
     * Obtener número total de logs
     *
     * @param string $level
     * @param string $search
     * @param string $date_from
     * @param string $date_to
     * @return int
     */
    public function get_total_logs( $level = '', $search = '', $date_from = '', $date_to = '' ) {
        global $wpdb;
        
        if ( ! $this->table_exists() ) {
            return 0;
        }
        
        $where_clauses = array();
        $params = array();

        if ( ! empty( $level ) ) {
            $where_clauses[] = 'level = %s';
            $params[] = $level;
        }

        if ( ! empty( $search ) ) {
            $where_clauses[] = 'message LIKE %s';
            $params[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        if ( ! empty( $date_from ) ) {
            $where_clauses[] = 'timestamp >= %s';
            $params[] = $date_from . ' 00:00:00';
        }

        if ( ! empty( $date_to ) ) {
            $where_clauses[] = 'timestamp <= %s';
            $params[] = $date_to . ' 23:59:59';
        }

        $where_sql = '';
        if ( ! empty( $where_clauses ) ) {
            $where_sql = ' WHERE ' . implode( ' AND ', $where_clauses );
        }

        $sql = "SELECT COUNT(*) FROM {$this->table_name}{$where_sql}";

        if ( ! empty( $params ) ) {
            $prepared_sql = $wpdb->prepare( $sql, $params );
        } else {
            $prepared_sql = $sql;
        }

        return (int) $wpdb->get_var( $prepared_sql );
    }

    /**
     * Limpiar logs antiguos basado en la configuración de retención
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        $retention_days = $this->get_log_retention_days();
        
        if ( $retention_days <= 0 ) {
            return; // No limpiar si está configurado en 0 o negativo
        }

        // Limpiar base de datos
        if ( $this->table_exists() ) {
            $cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );
            
            $deleted = $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE timestamp < %s",
                $cutoff_date
            ));

            if ( $deleted ) {
                $this->info( sprintf( 'Limpieza automática: %d registros eliminados de logs antiguos', $deleted ) );
            }
        }

        // Limpiar archivos de log antiguos
        $this->cleanup_old_log_files( $retention_days );
    }

    /**
     * Limpiar archivos de log antiguos
     *
     * @param int $retention_days
     */
    private function cleanup_old_log_files( $retention_days ) {
        if ( ! is_dir( $this->log_dir ) ) {
            return;
        }

        $files = glob( $this->log_dir . 'pmpro-woo-sync-*.log' );
        $deleted_files = 0;
        
        foreach ( $files as $file ) {
            if ( filemtime( $file ) < strtotime( "-{$retention_days} days" ) ) {
                if ( unlink( $file ) ) {
                    $deleted_files++;
                }
            }
        }

        if ( $deleted_files > 0 ) {
            $this->info( sprintf( 'Limpieza automática: %d archivos de log eliminados', $deleted_files ) );
        }
    }

    /**
     * Programar limpieza automática
     */
    private function schedule_cleanup() {
        if ( ! wp_next_scheduled( 'pmpro_woo_sync_cleanup_logs' ) ) {
            wp_schedule_event( time(), 'daily', 'pmpro_woo_sync_cleanup_logs' );
        }
        
        add_action( 'pmpro_woo_sync_cleanup_logs', array( $this, 'cleanup_old_logs' ) );
    }

    /**
     * Obtener estadísticas de logs
     *
     * @return array
     */
    public function get_log_stats() {
        global $wpdb;
        
        $stats = array(
            'total' => 0,
            'by_level' => array(),
            'last_24h' => 0,
            'last_7d' => 0,
            'database_size' => 0,
            'files_size' => 0,
        );
        
        if ( ! $this->table_exists() ) {
            return $stats;
        }
        
        // Total de logs
        $stats['total'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
        
        // Logs por nivel
        $level_stats = $wpdb->get_results( 
            "SELECT level, COUNT(*) as count FROM {$this->table_name} GROUP BY level"
        );
        
        foreach ( $level_stats as $stat ) {
            $stats['by_level'][ $stat->level ] = (int) $stat->count;
        }
        
        // Logs de las últimas 24 horas
        $stats['last_24h'] = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE timestamp >= %s",
            gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) )
        ));
        
        // Logs de los últimos 7 días
        $stats['last_7d'] = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE timestamp >= %s",
            gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) )
        ));
        
        // Tamaño de la tabla en la base de datos
        $table_size = $wpdb->get_row( $wpdb->prepare(
            "SELECT 
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
            FROM information_schema.TABLES 
            WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $this->table_name
        ));
        
        if ( $table_size ) {
            $stats['database_size'] = $table_size->size_mb . ' MB';
        }
        
        // Tamaño de archivos de log
        $stats['files_size'] = $this->get_log_files_size();
        
        return $stats;
    }

    /**
     * Obtener tamaño total de archivos de log
     *
     * @return string
     */
    private function get_log_files_size() {
        if ( ! is_dir( $this->log_dir ) ) {
            return '0 B';
        }

        $files = glob( $this->log_dir . 'pmpro-woo-sync-*.log' );
        $total_size = 0;
        
        foreach ( $files as $file ) {
            $total_size += filesize( $file );
        }
        
        return size_format( $total_size );
    }

    /**
     * Crear tabla de logs
     */
    public function create_log_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext,
            user_id bigint(20) unsigned DEFAULT 0,
            PRIMARY KEY (id),
            KEY level (level),
            KEY timestamp (timestamp),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Eliminar tabla de logs
     */
    public function drop_log_table() {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS {$this->table_name}" );
    }

    /**
     * Verificar si el logging está habilitado
     *
     * @return bool
     */
    private function is_logging_enabled() {
        if ( $this->settings ) {
            return $this->settings->get_setting( 'enable_logging', true );
        }
        
        $settings = get_option( 'pmpro_woo_sync_settings', array() );
        return isset( $settings['enable_logging'] ) ? (bool) $settings['enable_logging'] : true;
    }

    /**
     * Verificar si el modo debug está habilitado
     *
     * @return bool
     */
    private function is_debug_enabled() {
        if ( $this->settings ) {
            return $this->settings->is_debug_enabled();
        }
        
        $settings = get_option( 'pmpro_woo_sync_settings', array() );
        return isset( $settings['debug_mode'] ) ? (bool) $settings['debug_mode'] : false;
    }

    /**
     * Obtener días de retención de logs
     *
     * @return int
     */
    private function get_log_retention_days() {
        if ( $this->settings ) {
            return $this->settings->get_log_retention_days();
        }
        
        $settings = get_option( 'pmpro_woo_sync_settings', array() );
        return isset( $settings['log_retention_days'] ) ? (int) $settings['log_retention_days'] : 30;
    }

    /**
     * Exportar logs a CSV
     *
     * @param array $filters
     * @return string|false Ruta del archivo CSV o false en caso de error
     */
    public function export_logs_to_csv( $filters = array() ) {
        $logs = $this->get_logs( 
            isset( $filters['limit'] ) ? $filters['limit'] : 1000,
            isset( $filters['offset'] ) ? $filters['offset'] : 0,
            isset( $filters['level'] ) ? $filters['level'] : '',
            isset( $filters['search'] ) ? $filters['search'] : '',
            isset( $filters['date_from'] ) ? $filters['date_from'] : '',
            isset( $filters['date_to'] ) ? $filters['date_to'] : ''
        );

        if ( empty( $logs ) ) {
            return false;
        }

        $csv_file = $this->log_dir . 'export-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';
        $handle = fopen( $csv_file, 'w' );

        if ( ! $handle ) {
            return false;
        }

        // Escribir encabezados
        fputcsv( $handle, array( 'Timestamp', 'Level', 'Message', 'User ID', 'Context' ) );

        // Escribir datos
        foreach ( $logs as $log ) {
            fputcsv( $handle, array(
                $log->timestamp,
                $log->level,
                $log->message,
                $log->user_id,
                $log->context
            ));
        }

        fclose( $handle );
        return $csv_file;
    }
}
