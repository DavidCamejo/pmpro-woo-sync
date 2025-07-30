<?php
/**
 * Clase para manejar los ajustes del plugin PMPRO-WooCommerce Sync.
 */
class PMPro_Woo_Sync_Settings {

    /**
     * Nombre de la opción en la base de datos donde se guardarán los ajustes.
     * @var string
     */
    const SETTINGS_OPTION_NAME = 'pmpro_woo_sync_settings';

    /**
     * Define el nombre del grupo de opciones para WordPress Settings API.
     * @var string
     */
    const SETTINGS_GROUP_NAME = 'pmpro_woo_sync_option_group';

    /**
     * Cache de configuraciones para evitar múltiples consultas.
     * @var array
     */
    protected $settings_cache;

    /**
     * Instancia de PMPro_Woo_Sync_Logger.
     *
     * @var PMPro_Woo_Sync_Logger
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param PMPro_Woo_Sync_Logger $logger Instancia de la clase de logs.
     */
    public function __construct( PMPro_Woo_Sync_Logger $logger ) {
        $this->logger = $logger;
        
        // Cargar configuraciones en cache
        $this->settings_cache = get_option( self::SETTINGS_OPTION_NAME, $this->get_default_settings() );
        
        // Hooks
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'update_option_' . self::SETTINGS_OPTION_NAME, array( $this, 'on_settings_update' ), 10, 2 );
    }

    /**
     * Define y retorna los ajustes predeterminados del plugin.
     *
     * @return array Array asociativo de ajustes predeterminados.
     */
    private function get_default_settings() {
        return array(
            'enable_sync'          => 'yes',
            'debug_mode'           => 'no',
            'enable_logging'       => 'yes',
            'log_retention_days'   => 30,
            'retry_attempts'       => 3,
            'retry_delay'          => 300, // 5 minutos
            'webhook_enabled'      => 'no',
            'batch_size'           => 50,
            'api_timeout'          => 30,
            'pagbank_api_settings' => array(
                'api_key' => '',
                'mode'    => 'live',
            ),
            'sync_options' => array(
                'bidirectional_sync'     => 'yes',
                'auto_create_products'   => 'no',
                'sync_membership_data'   => 'yes',
                'handle_downgrades'      => 'yes',
            ),
        );
    }

    /**
     * Retorna un ajuste específico o todos los ajustes si no se especifica una clave.
     *
     * @param string $key     Clave del ajuste a obtener.
     * @param mixed  $default Valor por defecto si el ajuste no existe.
     * @return mixed El valor del ajuste o el array completo de ajustes.
     */
    public function get_setting( $key = null, $default = null ) {
        if ( null === $key ) {
            return $this->settings_cache;
        }

        // Soporte para claves anidadas usando notación de punto
        if ( strpos( $key, '.' ) !== false ) {
            return $this->get_nested_setting( $key, $default );
        }

        return isset( $this->settings_cache[ $key ] ) ? $this->settings_cache[ $key ] : $default;
    }

    /**
     * Obtiene configuración anidada usando notación de punto.
     *
     * @param string $key     Clave con notación de punto (ej: 'pagbank_api_settings.api_key').
     * @param mixed  $default Valor por defecto.
     * @return mixed
     */
    private function get_nested_setting( $key, $default = null ) {
        $keys = explode( '.', $key );
        $value = $this->settings_cache;

        foreach ( $keys as $nested_key ) {
            if ( isset( $value[ $nested_key ] ) ) {
                $value = $value[ $nested_key ];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Actualiza una configuración específica.
     *
     * @param string $key   Clave del ajuste.
     * @param mixed  $value Valor del ajuste.
     * @return bool
     */
    public function update_setting( $key, $value ) {
        $this->settings_cache[ $key ] = $value;
        return update_option( self::SETTINGS_OPTION_NAME, $this->settings_cache );
    }

    /**
     * Registra los ajustes, secciones y campos del plugin.
     */
    public function register_settings() {
        // Registrar grupo de ajustes con validación
        register_setting(
            self::SETTINGS_GROUP_NAME,
            self::SETTINGS_OPTION_NAME,
            array(
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => $this->get_default_settings(),
            )
        );

        // Sección de Ajustes Generales
        add_settings_section(
            'pmpro_woo_sync_general_section',
            __( 'Ajustes Generales', 'pmpro-woo-sync' ),
            array( $this, 'print_general_section_info' ),
            'pmpro-woo-sync'
        );

        // Campos de Ajustes Generales
        $this->add_general_fields();

        // Sección de Ajustes de PagBank
        add_settings_section(
            'pmpro_woo_sync_pagbank_section',
            __( 'Ajustes de PagBank', 'pmpro-woo-sync' ),
            array( $this, 'print_pagbank_section_info' ),
            'pmpro-woo-sync'
        );

        // Campos de PagBank
        $this->add_pagbank_fields();

        // Sección de Sincronización Avanzada
        add_settings_section(
            'pmpro_woo_sync_advanced_section',
            __( 'Opciones Avanzadas de Sincronización', 'pmpro-woo-sync' ),
            array( $this, 'print_advanced_section_info' ),
            'pmpro-woo-sync'
        );

        // Campos avanzados
        $this->add_advanced_fields();
    }

    /**
     * Agrega campos de configuración general.
     */
    private function add_general_fields() {
        add_settings_field(
            'enable_sync',
            __( 'Habilitar Sincronización', 'pmpro-woo-sync' ),
            array( $this, 'enable_sync_callback' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_general_section'
        );

        add_settings_field(
            'enable_logging',
            __( 'Habilitar Logging', 'pmpro-woo-sync' ),
            array( $this, 'enable_logging_callback' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_general_section'
        );

        add_settings_field(
            'debug_mode',
            __( 'Modo Depuración', 'pmpro-woo-sync' ),
            array( $this, 'debug_mode_callback' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_general_section'
        );

        add_settings_field(
            'log_retention_days',
            __( 'Días de Retención de Logs', 'pmpro-woo-sync' ),
            array( $this, 'log_retention_callback' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_general_section'
        );
    }

    /**
     * Agrega campos de configuración de PagBank.
     */
    private function add_pagbank_fields() {
        add_settings_field(
            'pagbank_api_key',
            __( 'API Key de PagBank', 'pmpro-woo-sync' ),
            array( $this, 'pagbank_api_key_callback' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_pagbank_section'
        );

        add_settings_field(
            'pagbank_mode',
            __( 'Modo de PagBank', 'pmpro-woo-sync' ),
            array( $this, 'pagbank_mode_callback' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_pagbank_section'
        );
    }

    /**
     * Agrega campos de configuración avanzada.
     */
    private function add_advanced_fields() {
        add_settings_field(
            'retry_attempts',
            __( 'Intentos de Reintento', 'pmpro-woo-sync' ),
            array( $this, 'retry_attempts_callback' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_advanced_section'
        );

        add_settings_field(
            'batch_size',
            __( 'Tamaño de Lote', 'pmpro-woo-sync' ),
            array( $this, 'batch_size_callback' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_advanced_section'
        );

        add_settings_field(
            'bidirectional_sync',
            __( 'Sincronización Bidireccional', 'pmpro-woo-sync' ),
            array( $this, 'bidirectional_sync_callback' ),
            'pmpro-woo-sync',
            'pmpro_woo_sync_advanced_section'
        );
    }

    /**
     * Callbacks para mostrar información de secciones.
     */
    public function print_general_section_info() {
        echo '<p>' . esc_html__( 'Configure las opciones generales del plugin de sincronización.', 'pmpro-woo-sync' ) . '</p>';
    }

    public function print_pagbank_section_info() {
        echo '<p>' . esc_html__( 'Configure las credenciales y opciones de la API de PagBank.', 'pmpro-woo-sync' ) . '</p>';
    }

    public function print_advanced_section_info() {
        echo '<p>' . esc_html__( 'Configuraciones avanzadas para usuarios experimentados.', 'pmpro-woo-sync' ) . '</p>';
    }

    /**
     * Callbacks para campos de configuración.
     */
    public function enable_sync_callback() {
        $value = $this->get_setting( 'enable_sync', 'yes' );
        $checked = checked( 'yes', $value, false );
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_OPTION_NAME ); ?>[enable_sync]" value="yes" <?php echo $checked; ?> />
            <?php esc_html_e( 'Activar sincronización entre PMPro y WooCommerce', 'pmpro-woo-sync' ); ?>
        </label>
        <?php
    }

    public function enable_logging_callback() {
        $value = $this->get_setting( 'enable_logging', 'yes' );
        $checked = checked( 'yes', $value, false );
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_OPTION_NAME ); ?>[enable_logging]" value="yes" <?php echo $checked; ?> />
            <?php esc_html_e( 'Habilitar sistema de logging', 'pmpro-woo-sync' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Desactivar para mejorar rendimiento si no necesitas logs.', 'pmpro-woo-sync' ); ?></p>
        <?php
    }

    public function debug_mode_callback() {
        $value = $this->get_setting( 'debug_mode', 'no' );
        $checked = checked( 'yes', $value, false );
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_OPTION_NAME ); ?>[debug_mode]" value="yes" <?php echo $checked; ?> />
            <?php esc_html_e( 'Activar modo de depuración', 'pmpro-woo-sync' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Genera logs más detallados para troubleshooting.', 'pmpro-woo-sync' ); ?></p>
        <?php
    }

    public function log_retention_callback() {
        $value = $this->get_setting( 'log_retention_days', 30 );
        ?>
        <input type="number" min="0" max="365" name="<?php echo esc_attr( self::SETTINGS_OPTION_NAME ); ?>[log_retention_days]" value="<?php echo esc_attr( $value ); ?>" />
        <p class="description"><?php esc_html_e( 'Días que se conservarán los logs. 0 = mantener indefinidamente.', 'pmpro-woo-sync' ); ?></p>
        <?php
    }

    public function pagbank_api_key_callback() {
        $value = $this->get_setting( 'pagbank_api_settings.api_key', '' );
        ?>
        <input type="password" class="regular-text" name="<?php echo esc_attr( self::SETTINGS_OPTION_NAME ); ?>[pagbank_api_settings][api_key]" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'Ingresa tu API Key de PagBank', 'pmpro-woo-sync' ); ?>" />
        <button type="button" class="button" onclick="this.previousElementSibling.type = this.previousElementSibling.type === 'password' ? 'text' : 'password'">
            <?php esc_html_e( 'Mostrar/Ocultar', 'pmpro-woo-sync' ); ?>
        </button>
        <p class="description"><?php esc_html_e( 'API Key de PagBank para realizar solicitudes autenticadas.', 'pmpro-woo-sync' ); ?></p>
        <?php
    }

    public function pagbank_mode_callback() {
        $value = $this->get_setting( 'pagbank_api_settings.mode', 'live' );
        ?>
        <select name="<?php echo esc_attr( self::SETTINGS_OPTION_NAME ); ?>[pagbank_api_settings][mode]">
            <option value="live" <?php selected( $value, 'live' ); ?>><?php esc_html_e( 'Producción', 'pmpro-woo-sync' ); ?></option>
            <option value="sandbox" <?php selected( $value, 'sandbox' ); ?>><?php esc_html_e( 'Sandbox (Pruebas)', 'pmpro-woo-sync' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Ambiente de PagBank a utilizar.', 'pmpro-woo-sync' ); ?></p>
        <?php
    }

    public function retry_attempts_callback() {
        $value = $this->get_setting( 'retry_attempts', 3 );
        ?>
        <input type="number" min="0" max="10" name="<?php echo esc_attr( self::SETTINGS_OPTION_NAME ); ?>[retry_attempts]" value="<?php echo esc_attr( $value ); ?>" />
        <p class="description"><?php esc_html_e( 'Número de reintentos para operaciones fallidas.', 'pmpro-woo-sync' ); ?></p>
        <?php
    }

    public function batch_size_callback() {
        $value = $this->get_setting( 'batch_size', 50 );
        ?>
        <input type="number" min="1" max="200" name="<?php echo esc_attr( self::SETTINGS_OPTION_NAME ); ?>[batch_size]" value="<?php echo esc_attr( $value ); ?>" />
        <p class="description"><?php esc_html_e( 'Número de elementos a procesar por lote en operaciones masivas.', 'pmpro-woo-sync' ); ?></p>
        <?php
    }

    public function bidirectional_sync_callback() {
        $value = $this->get_setting( 'sync_options.bidirectional_sync', 'yes' );
        $checked = checked( 'yes', $value, false );
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_OPTION_NAME ); ?>[sync_options][bidirectional_sync]" value="yes" <?php echo $checked; ?> />
            <?php esc_html_e( 'Permitir sincronización en ambas direcciones', 'pmpro-woo-sync' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Sincronizar cambios tanto de PMPro a WooCommerce como viceversa.', 'pmpro-woo-sync' ); ?></p>
        <?php
    }

    /**
     * Sanea y valida los ajustes antes de guardarlos.
     *
     * @param array $input Los datos de entrada del formulario.
     * @return array Los datos saneados y validados.
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        // Configuraciones generales
        $sanitized['enable_sync'] = isset( $input['enable_sync'] ) ? 'yes' : 'no';
        $sanitized['enable_logging'] = isset( $input['enable_logging'] ) ? 'yes' : 'no';
        $sanitized['debug_mode'] = isset( $input['debug_mode'] ) ? 'yes' : 'no';
        $sanitized['log_retention_days'] = isset( $input['log_retention_days'] ) ? absint( $input['log_retention_days'] ) : 30;
        $sanitized['retry_attempts'] = isset( $input['retry_attempts'] ) ? max( 0, min( 10, absint( $input['retry_attempts'] ) ) ) : 3;
        $sanitized['batch_size'] = isset( $input['batch_size'] ) ? max( 1, min( 200, absint( $input['batch_size'] ) ) ) : 50;

        // Configuraciones de PagBank
        if ( isset( $input['pagbank_api_settings'] ) ) {
            $sanitized['pagbank_api_settings'] = array(
                'api_key' => sanitize_text_field( $input['pagbank_api_settings']['api_key'] ?? '' ),
                'mode'    => in_array( $input['pagbank_api_settings']['mode'] ?? 'live', array( 'live', 'sandbox' ) ) 
                           ? $input['pagbank_api_settings']['mode'] 
                           : 'live',
            );
        }

        // Opciones de sincronización
        if ( isset( $input['sync_options'] ) ) {
            $sanitized['sync_options'] = array(
                'bidirectional_sync' => isset( $input['sync_options']['bidirectional_sync'] ) ? 'yes' : 'no',
            );
        }

        // Conservar configuraciones no presentes en el formulario
        $current_settings = $this->settings_cache;
        $sanitized = array_merge( $current_settings, $sanitized );

        $this->logger->info( 'Configuraciones del plugin actualizadas', array( 'changed_settings' => array_keys( $sanitized ) ) );

        return $sanitized;
    }

    /**
     * Callback ejecutado cuando se actualizan las configuraciones.
     *
     * @param array $old_value Valor anterior.
     * @param array $new_value Nuevo valor.
     */
    public function on_settings_update( $old_value, $new_value ) {
        // Actualizar cache
        $this->settings_cache = $new_value;
        
        // Log de cambios significativos
        $significant_changes = array( 'enable_sync', 'debug_mode', 'pagbank_api_settings' );
        foreach ( $significant_changes as $key ) {
            if ( isset( $old_value[ $key ] ) && isset( $new_value[ $key ] ) && $old_value[ $key ] !== $new_value[ $key ] ) {
                $this->logger->info( "Configuración crítica cambiada: {$key}", array(
                    'old_value' => $old_value[ $key ],
                    'new_value' => $new_value[ $key ],
                ));
            }
        }
    }

    /**
     * Obtiene todas las configuraciones (alias para get_setting sin parámetros).
     *
     * @return array
     */
    public function get_settings() {
        return $this->get_setting();
    }

    /**
     * Valida si una API key de PagBank tiene formato válido.
     *
     * @param string $api_key La API key a validar.
     * @return bool
     */
    public function validate_pagbank_api_key( $api_key ) {
        // PagBank API keys típicamente tienen un formato específico
        return ! empty( $api_key ) && strlen( $api_key ) >= 20;
    }

    /**
     * Obtiene la configuración de un gateway específico.
     *
     * @param string $gateway_id ID del gateway.
     * @return array
     */
    public function get_gateway_settings( $gateway_id ) {
        $settings_key = $gateway_id . '_api_settings';
        return $this->get_setting( $settings_key, array() );
    }
}
