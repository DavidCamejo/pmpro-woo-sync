<?php
/**
 * Clase para el sistema de logs del plugin PMPRO-WooCommerce Sync.
 * Guarda logs en una tabla de base de datos personalizada.
 */
class PMPro_Woo_Sync_Logger {

    /**
     * Nombre de la tabla de logs.
     * @var string
     */
    protected $table_name;

    /**
     * Constructor de la clase.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'pmpro_woo_sync_logs';
    }

    /**
     * Registra un mensaje de log en la base de datos.
     *
     * @param string $level   Nivel del log (info, warning, error, debug).
     * @param string $message Mensaje a registrar.
     * @param array  $context Datos adicionales para el log.
     */
    protected function log( $level, $message, $context = [] ) {
        global $wpdb;

        if ( ! in_array( $level, [ 'info', 'warning', 'error', 'debug' ] ) ) {
            $level = 'info'; // Nivel por defecto.
        }

        // Si el modo depuración no está activo, no registrar mensajes de 'debug'.
        if ( 'debug' === $level && 'yes' !== get_option( 'pmpro_woo_sync_settings', [] )['debug_mode'] ) {
            return;
        }

        $data = [
            'timestamp' => current_time( 'mysql' ),
            'level'     => $level,
            'message'   => $message,
            'context'   => wp_json_encode( $context ),
        ];

        $format = [ '%s', '%s', '%s', '%s' ];

        $wpdb->insert( $this->table_name, $data, $format );

        // También podemos enviar al log de PHP si es un error crítico.
        if ( 'error' === $level ) {
            error_log( sprintf( 'PMPRO-Woo Sync Error [%s]: %s (Context: %s)', $level, $message, wp_json_encode( $context ) ) );
        }
    }

    /**
     * Registra un mensaje de nivel 'info'.
     *
     * @param string $message Mensaje.
     * @param array  $context Contexto.
     */
    public function info( $message, $context = [] ) {
        $this->log( 'info', $message, $context );
    }

    /**
     * Registra un mensaje de nivel 'warning'.
     *
     * @param string $message Mensaje.
     * @param array  $context Contexto.
     */
    public function warning( $message, $context = [] ) {
        $this->log( 'warning', $message, $context );
    }

    /**
     * Registra un mensaje de nivel 'error'.
     *
     * @param string $message Mensaje.
     * @param array  $context Contexto.
     */
    public function error( $message, $context = [] ) {
        $this->log( 'error', $message, $context );
    }

    /**
     * Registra un mensaje de nivel 'debug'.
     * Solo se registrará si el modo depuración está activo en los ajustes.
     *
     * @param string $message Mensaje.
     * @param array  $context Contexto.
     */
    public function debug( $message, $context = [] ) {
        $this->log( 'debug', $message, $context );
    }

    /**
     * Obtiene los logs de la base de datos.
     *
     * @param int $limit Número máximo de logs a obtener.
     * @param int $offset Offset para la paginación.
     * @param string $level Filtrar por nivel de log.
     * @return array
     */
    public function get_logs( $limit = 20, $offset = 0, $level = '' ) {
        global $wpdb;
        $sql = "SELECT * FROM {$this->table_name}";
        $where_clauses = [];
        $params = [];
        $param_types = '';

        if ( ! empty( $level ) ) {
            $where_clauses[] = 'level = %s';
            $params[] = $level;
            $param_types .= 's';
        }

        if ( ! empty( $where_clauses ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where_clauses );
        }

        $sql .= ' ORDER BY timestamp DESC LIMIT %d OFFSET %d';
        $params[] = $limit;
        $params[] = $offset;
        $param_types .= 'ii';

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
     * @param string $level Filtrar por nivel de log.
     * @return int
     */
    public function get_total_logs( $level = '' ) {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$this->table_name}";
        $where_clauses = [];
        $params = [];
        $param_types = '';

        if ( ! empty( $level ) ) {
            $where_clauses[] = 'level = %s';
            $params[] = $level;
            $param_types .= 's';
        }

        if ( ! empty( $where_clauses ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where_clauses );
        }

        if ( ! empty( $params ) ) {
            $prepared_sql = $wpdb->prepare( $sql, $params );
        } else {
            $prepared_sql = $sql;
        }

        return $wpdb->get_var( $prepared_sql );
    }
}
