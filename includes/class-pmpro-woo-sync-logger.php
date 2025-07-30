<?php
/**
 * Clase para el sistema de logs del plugin PMPRO-WooCommerce Sync.
 * Guarda logs en una tabla de base de datos personalizada y archivo de texto.
 */
class PMPro_Woo_Sync_Logger {

    /**
     * Nombre de la tabla de logs.
     * @var string
     */
    protected $table_name;

    /**
     * Directorio de archivos de log.
     * @var string
     */
    protected $log_dir;

    /**
     * Constructor de la clase.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'pmpro_woo_sync_logs';
        $this->log_dir = PMPRO_WOO_SYNC_PATH . 'logs/';
        
        // Asegurarse de que el directorio de logs existe
        $this->ensure_log_directory();
    }

    /**
     * Asegura que el directorio de logs existe y está protegido.
     */
    private function ensure_log_directory() {
        if ( ! file_exists( $this->log_dir ) ) {
            wp_mkdir_p( $this->log_dir );
        }
        
        // Crear/actualizar archivo .htaccess para proteger los logs
        $htaccess_file = $this->log_dir . '.htaccess';
        if ( ! file_exists( $htaccess_file ) ) {
            file_put_contents( $htaccess_file, "Deny from all\n" );
        }
    }

    /**
     * Registra un mensaje de log en la base de datos y archivo.
     *
     * @param string $level   Nivel del log (info, warning, error, debug).
     * @param string $message Mensaje a registrar.
     * @param array  $context Datos adicionales para el log.
     * @param string $method  Método que llama al log (opcional).
     */
    public function log( $level, $message, $context = array(), $method = '' ) {
        // Validar nivel de log
        $valid_levels = array( 'info', 'warning', 'error', 'debug', 'success' );
        if ( ! in_array( $level, $valid_levels ) ) {
            $level = 'info';
        }

        // Verificar si el logging está habilitado
        $settings = get_option( 'pmpro_woo_sync_settings', array() );
        if ( isset( $settings['enable_logging'] ) && 'no' === $settings['enable_logging'] ) {
            return;
        }

        // Si el modo depuración no está activo, no registrar mensajes de 'debug'
        if ( 'debug' === $level && ( ! isset( $settings['debug_mode'] ) || 'no' === $settings['debug_mode'] ) ) {
            return;
        }

        // Agregar información del método si se proporciona
        if ( ! empty( $method ) ) {
            $context['calling_method'] = $method;
        }

        // Agregar información de contexto adicional
        $context['timestamp'] = current_time( 'mysql' );
        $context['user_id'] = get_current_user_id();
        $context['request_uri'] = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';

        // Registrar en base de datos
        $this->log_to_database( $level, $message, $context );

        // Registrar en archivo
        $this->log_to_file( $level, $message, $context );

        // Log crítico también al error_log de PHP
        if ( 'error' === $level && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( sprintf( 
                'PMPRO-Woo-Sync [%s]: %s | Context: %s', 
                strtoupper( $level ), 
                $message, 
                wp_json_encode( $context, JSON_UNESCAPED_UNICODE ) 
            ));
        }
    }

    /**
     * Registra en la base de datos.
     *
     * @param string $level   Nivel del log.
     * @param string $message Mensaje.
     * @param array  $context Contexto.
     */
    private function log_to_database( $level, $message, $context ) {
        global $wpdb;

        $data = array(
            'timestamp' => current_time( 'mysql' ),
            'level'     => $level,
            'message'   => $message,
            'context'   => wp_json_encode( $context, JSON_UNESCAPED_UNICODE ),
        );

        $format = array( '%s', '%s', '%s', '%s' );

        $result = $wpdb->insert( $this->table_name, $data, $format );
        
        if ( false === $result && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'PMPRO-Woo-Sync: Error al insertar log en base de datos: ' . $wpdb->last_error );
        }
    }

    /**
     * Registra en archivo de texto.
     *
     * @param string $level   Nivel del log.
     * @param string $message Mensaje.
     * @param array  $context Contexto.
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
        file_put_contents( $log_file, $log_entry, FILE_APPEND | LOCK_EX );
    }

    /**
     * Registra un mensaje de nivel 'info'.
     *
     * @param string $message Mensaje.
     * @param array  $context Contexto.
     * @param string $method  Método que llama al log.
     */
    public function info( $message, $context = array(), $method = '' ) {
        $this->log( 'info', $message, $context, $method );
    }

    /**
     * Registra un mensaje de nivel 'success'.
     *
     * @param string $message Mensaje.
     * @param array  $context Contexto.
     * @param string $method  Método que llama al log.
     */
    public function success( $message, $context = array(), $method = '' ) {
        $this->log( 'success', $message, $context, $method );
    }

    /**
     * Registra un mensaje de nivel 'warning'.
     *
     * @param string $message Mensaje.
     * @param array  $context Contexto.
     * @param string $method  Método que llama al log.
     */
    public function warning( $message, $context = array(), $method = '' ) {
        $this->log( 'warning', $message, $context, $method );
    }

    /**
     * Registra un mensaje de nivel 'error'.
     *
     * @param string $message Mensaje.
     * @param array  $context Contexto.
     * @param string $method  Método que llama al log.
     */
    public function error( $message, $context = array(), $method = '' ) {
        $this->log( 'error', $message, $context, $method );
    }

    /**
     * Registra un mensaje de nivel 'debug'.
     *
     * @param string $message Mensaje.
     * @param array  $context Contexto.
     * @param string $method  Método que llama al log.
     */
    public function debug( $message, $context = array(), $method = '' ) {
        $this->log( 'debug', $message, $context, $method );
    }

    /**
     * Obtiene los logs de la base de datos.
     *
     * @param int    $limit  Número máximo de logs a obtener.
     * @param int    $offset Offset para la paginación.
     * @param string $level  Filtrar por nivel de log.
     * @param string $search Buscar en el mensaje.
     * @return array
     */
    public function get_logs( $limit = 20, $offset = 0, $level = '', $search = '' ) {
        global $wpdb;
        
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

        $where_sql = '';
        if ( ! empty( $where_clauses ) ) {
            $where_sql = ' WHERE ' . implode( ' AND ', $where_clauses );
        }

        $sql = "SELECT * FROM {$this->table_name}{$where_sql} ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        if ( ! empty( $params ) ) {
            $prepared_sql = $wpdb->prepare( $sql, $params );
        } else {
            $prepared_sql = $sql;
        }

        return $wpdb->get_results( $prepared_sql );
    }

    /**
     * Obtiene el número total de logs.
     *
     * @param string $level  Filtrar por nivel de log.
     * @param string $search Buscar en el mensaje.
     * @return int
     */
    public function get_total_logs( $level = '', $search = '' ) {
        global $wpdb;
        
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
     * Limpia logs antiguos basado en la configuración de retención.
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        $settings = get_option( 'pmpro_woo_sync_settings', array() );
        $retention_days = isset( $settings['log_retention_days'] ) ? intval( $settings['log_retention_days'] ) : 30;
        
        if ( $retention_days <= 0 ) {
            return; // No limpiar si está configurado en 0 o negativo
        }

        // Limpiar base de datos
        $cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );
        
        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE timestamp < %s",
            $cutoff_date
        ));

        // Limpiar archivos de log antiguos
        $this->cleanup_old_log_files( $retention_days );

        if ( $deleted ) {
            $this->info( sprintf( 'Limpieza automática: %d registros eliminados de logs antiguos', $deleted ) );
        }
    }

    /**
     * Limpia archivos de log antiguos.
     *
     * @param int $retention_days Días de retención.
     */
    private function cleanup_old_log_files( $retention_days ) {
        if ( ! is_dir( $this->log_dir ) ) {
            return;
        }

        $files = glob( $this->log_dir . 'pmpro-woo-sync-*.log' );
        
        foreach ( $files as $file ) {
            if ( filemtime( $file ) < strtotime( "-{$retention_days} days" ) ) {
                unlink( $file );
            }
        }
    }

    /**
     * Obtiene estadísticas de logs.
     *
     * @return array
     */
    public function get_log_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total de logs
        $stats['total'] = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
        
        // Logs por nivel
        $level_stats = $wpdb->get_results( 
            "SELECT level, COUNT(*) as count FROM {$this->table_name} GROUP BY level"
        );
        
        foreach ( $level_stats as $stat ) {
            $stats['by_level'][ $stat->level ] = $stat->count;
        }
        
        // Logs de las últimas 24 horas
        $stats['last_24h'] = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE timestamp >= %s",
            gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) )
        ));
        
        return $stats;
    }
}
