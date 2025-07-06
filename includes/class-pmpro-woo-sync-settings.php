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
    const SETTINGS_GROUP_NAME  = 'pmpro_woo_sync_option_group'; // <- NUEVA CONSTANTE

    /**
     * Instancia de PMPro_Woo_Sync_Settings
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
     * Constructor.
     *
     * @param PMPro_Woo_Sync_Logger $logger Instancia de la clase de logs.
     */
    public function __construct( PMPro_Woo_Sync_Logger $logger ) {
        $this->logger = $logger;
        // Carga los ajustes existentes o los predeterminados.
        $this->settings = get_option( self::SETTINGS_OPTION_NAME, $this->get_default_settings() );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Define y retorna los ajustes predeterminados del plugin.
     *
     * @return array Array asociativo de ajustes predeterminados.
     */
    private function get_default_settings() { // <-- AÑADE ESTE MÉTODO
        return [
            'enable_sync'        => true,  // Por defecto, la sincronización habilitada.
            'debug_mode'         => false, // Por defecto, el modo depuración deshabilitado.
            'pagbank_api_settings' => [
                'api_key' => '',
                'mode'    => 'live',
            ],
            // TODO: Añadir otros ajustes predeterminados aquí a medida que los implementes.
        ];
    }

    /**
     * Retorna un ajuste específico o todos los ajustes si no se especifica una clave.
     *
     * @param string $key Clave del ajuste a obtener.
     * @param mixed $default Valor por defecto si el ajuste no existe.
     * @return mixed El valor del ajuste o el array completo de ajustes.
     */
    public function get_setting( $key = null, $default = null ) {
        if ( $key === null ) {
            return $this->settings; // Retorna todos los ajustes si no se especifica una clave.
        }
        return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
    }

    /**
     * Registra los ajustes, secciones y campos del plugin.
     */
    public function register_settings() {
        // Registra el grupo de ajustes principal.
        // El primer parámetro DEBE ser el mismo que se usa en settings_fields() en el formulario.
        register_setting(
            self::SETTINGS_GROUP_NAME, // <- USA LA CONSTANTE AQUÍ
            self::SETTINGS_OPTION_NAME,
            [ $this, 'sanitize_settings' ]
        );

        // Sección para Ajustes Generales.
        add_settings_section(
            'pmpro_woo_sync_general_section', // ID de la sección.
            __( 'Ajustes Generales', 'pmpro-woo-sync' ), // Título de la sección.
            [ $this, 'print_general_section_info' ], // Callback para la descripción.
            'pmpro-woo-sync'                      // Página del menú.
        );

        // Campo: Habilitar sincronización.
        add_settings_field(
            'enable_sync',
            __( 'Habilitar Sincronización', 'pmpro-woo-sync' ),
            [ $this, 'enable_sync_callback' ],
            'pmpro-woo-sync',
            'pmpro_woo_sync_general_section'
        );

        // Campo: Modo Depuración.
        add_settings_field(
            'debug_mode',
            __( 'Habilitar Modo Depuración', 'pmpro-woo-sync' ),
            [ $this, 'debug_mode_callback' ],
            'pmpro-woo-sync',
            'pmpro_woo_sync_general_section'
        );

        // Nueva sección para Ajustes de PagBank.
        add_settings_section(
            'pmpro_woo_sync_pagbank_section', // ID de la sección.
            __( 'Ajustes de PagBank', 'pmpro-woo-sync' ), // Título de la sección.
            [ $this, 'print_pagbank_section_info' ], // Callback para la descripción de la sección.
            'pmpro-woo-sync'                      // Página del menú.
        );

        // Campo: PagBank API Key.
        add_settings_field(
            'pagbank_api_key',
            __( 'PagBank API Key', 'pmpro-woo-sync' ),
            [ $this, 'pagbank_api_key_callback' ],
            'pmpro-woo-sync',
            'pmpro_woo_sync_pagbank_section'
        );

        // Campo: Modo (Sandbox/Live).
        add_settings_field(
            'pagbank_mode',
            __( 'Modo de PagBank', 'pmpro-woo-sync' ),
            [ $this, 'pagbank_mode_callback' ],
            'pmpro-woo-sync',
            'pmpro_woo_sync_pagbank_section'
        );

        // TODO: Añadir más campos si se necesitan (ej. URL del webhook de confirmación).
    }

    // ... (funciones de print y callbacks existentes) ...

    /**
     * Imprime el texto de la sección de Ajustes de PagBank.
     */
    public function print_pagbank_section_info() {
        echo '<p>' . __( 'Configure las credenciales de la API de PagBank para la sincronización de cancelaciones.', 'pmpro-woo-sync' ) . '</p>';
    }

    /**
     * Callback para renderizar el campo 'PagBank API Key'.
     */
    public function pagbank_api_key_callback() {
        $options = $this->get_settings();
        $pagbank_settings = isset( $options['pagbank_api_settings'] ) ? $options['pagbank_api_settings'] : [];
        $api_key = isset( $pagbank_settings['api_key'] ) ? sanitize_text_field( $pagbank_settings['api_key'] ) : '';
        ?>
        <input type="text" class="regular-text" name="<?php echo self::SETTINGS_OPTION_NAME; ?>[pagbank_api_settings][api_key]" value="<?php echo esc_attr( $api_key ); ?>" placeholder="<?php esc_attr_e( 'Ingresa tu API Key de PagBank', 'pmpro-woo-sync' ); ?>" />
        <p class="description"><?php esc_html_e( 'Tu API Key de PagBank para realizar solicitudes seguras.', 'pmpro-woo-sync' ); ?></p>
        <?php
    }

    /**
     * Callback para renderizar el campo 'Modo de PagBank'.
     */
    public function pagbank_mode_callback() {
        $options = $this->get_settings();
        $pagbank_settings = isset( $options['pagbank_api_settings'] ) ? $options['pagbank_api_settings'] : [];
        $mode = isset( $pagbank_settings['mode'] ) ? sanitize_text_field( $pagbank_settings['mode'] ) : 'live';
        ?>
        <select name="<?php echo self::SETTINGS_OPTION_NAME; ?>[pagbank_api_settings][mode]">
            <option value="live" <?php selected( $mode, 'live' ); ?>><?php esc_html_e( 'Modo en vivo (Producción)', 'pmpro-woo-sync' ); ?></option>
            <option value="sandbox" <?php selected( $mode, 'sandbox' ); ?>><?php esc_html_e( 'Modo Sandbox (Pruebas)', 'pmpro-woo-sync' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Selecciona el entorno de PagBank (Sandbox para pruebas, Live para producción).', 'pmpro-woo-sync' ); ?></p>
        <?php
    }

    /**
     * Sanea y valida los ajustes antes de guardarlos.
     *
     * @param array $input Los datos de entrada del formulario.
     * @return array Los datos saneados y validados.
     */
    public function sanitize_settings( $input ) {
        $new_input = [];

        // Saneamiento del campo 'enable_sync'.
        $new_input['enable_sync'] = isset( $input['enable_sync'] ) && $input['enable_sync'] === 'yes' ? 'yes' : 'no';

        // Saneamiento del campo 'debug_mode'.
        $new_input['debug_mode'] = isset( $input['debug_mode'] ) && $input['debug_mode'] === 'yes' ? 'yes' : 'no';

        // TODO: Saneamiento y validación para otros campos si se añaden.
        // Ejemplo para un campo de texto:
        // if ( isset( $input['api_key'] ) ) {
        //     $new_input['api_key'] = sanitize_text_field( $input['api_key'] );
        // }

        $this->logger->info( 'Ajustes del plugin actualizados.', [ 'settings' => $new_input ] );

        return $new_input;
    }

    /**
     * Imprime el texto de la sección de Ajustes Generales.
     */
    public function print_general_section_info() {
        echo '<p>' . __( 'Configure las opciones generales del plugin.', 'pmpro-woo-sync' ) . '</p>';
    }

    /**
     * Callback para renderizar el campo 'Habilitar Sincronización'.
     */
    public function enable_sync_callback() {
        $options = $this->get_settings();
        $checked = isset( $options['enable_sync'] ) ? checked( 'yes', $options['enable_sync'], false ) : '';
        ?>
        <label>
            <input type="checkbox" name="<?php echo self::SETTINGS_OPTION_NAME; ?>[enable_sync]" value="yes" <?php echo $checked; ?> />
            <?php esc_html_e( 'Habilitar la sincronización entre PMPro y WooCommerce.', 'pmpro-woo-sync' ); ?>
        </label>
        <?php
    }

    /**
     * Callback para renderizar el campo 'Modo Depuración'.
     */
    public function debug_mode_callback() {
        $options = $this->get_settings();
        $checked = isset( $options['debug_mode'] ) ? checked( 'yes', $options['debug_mode'], false ) : '';
        ?>
        <label>
            <input type="checkbox" name="<?php echo self::SETTINGS_OPTION_NAME; ?>[debug_mode]" value="yes" <?php echo $checked; ?> />
            <?php esc_html_e( 'Habilitar el modo de depuración para logs más detallados.', 'pmpro-woo-sync' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Los logs de depuración solo se guardan si esta opción está activada.', 'pmpro-woo-sync' ); ?></p>
        <?php
    }

    // TODO: Si se añade una sección de mapeo, aquí iría print_mapping_section_info()
    // y level_product_mapping_callback() con la lógica para seleccionar niveles y productos.

    /**
     * Obtiene todos los ajustes del plugin.
     *
     * @return array Los ajustes del plugin.
     */
    public function get_settings() {
        return get_option( self::SETTINGS_OPTION_NAME, [] );
    }
}
