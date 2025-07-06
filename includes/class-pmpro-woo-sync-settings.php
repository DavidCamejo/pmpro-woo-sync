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
    }

    /**
     * Registra los ajustes, secciones y campos del plugin.
     */
    public function register_settings() {
        // Registrar la opción principal del plugin.
        register_setting(
            'pmpro_woo_sync_option_group', // Nombre del grupo de opciones.
            self::SETTINGS_OPTION_NAME,    // Nombre de la opción en la DB.
            [ $this, 'sanitize_settings' ]  // Callback de sanitización.
        );

        // Sección general de ajustes.
        add_settings_section(
            'pmpro_woo_sync_general_section', // ID de la sección.
            __( 'Ajustes Generales', 'pmpro-woo-sync' ), // Título de la sección.
            [ $this, 'print_general_section_info' ], // Callback para la descripción de la sección.
            'pmpro-woo-sync'                      // Página del menú donde se mostrará.
        );

        // Campo: Habilitar/Deshabilitar sincronización.
        add_settings_field(
            'enable_sync', // ID del campo.
            __( 'Habilitar Sincronización', 'pmpro-woo-sync' ), // Título del campo.
            [ $this, 'enable_sync_callback' ], // Callback para renderizar el campo.
            'pmpro-woo-sync',                 // Página del menú.
            'pmpro_woo_sync_general_section'  // ID de la sección a la que pertenece.
        );

        // Campo: Modo depuración.
        add_settings_field(
            'debug_mode',
            __( 'Modo Depuración', 'pmpro-woo-sync' ),
            [ $this, 'debug_mode_callback' ],
            'pmpro-woo-sync',
            'pmpro_woo_sync_general_section'
        );

        // TODO: Añadir más secciones y campos aquí según las necesidades del plugin.
        // Por ejemplo, mapeo de niveles PMPRO a productos WooCommerce.
        /*
        add_settings_section(
            'pmpro_woo_sync_mapping_section',
            __( 'Mapeo de Niveles/Productos', 'pmpro-woo-sync' ),
            [ $this, 'print_mapping_section_info' ],
            'pmpro-woo-sync'
        );

        add_settings_field(
            'level_product_mapping',
            __( 'Configuración de Mapeo', 'pmpro-woo-sync' ),
            [ $this, 'level_product_mapping_callback' ],
            'pmpro-woo-sync',
            'pmpro_woo_sync_mapping_section'
        );
        */
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
            <?php esc_html_e( 'Habilitar la sincronización entre PMPRO y WooCommerce.', 'pmpro-woo-sync' ); ?>
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
     * Obtiene todos los ajustes del plugin.
     *
     * @return array Los ajustes del plugin.
     */
    public function get_settings() {
        return get_option( self::SETTINGS_OPTION_NAME, [] );
    }

    /**
     * Obtiene un ajuste específico del plugin.
     *
     * @param string $key La clave del ajuste a obtener.
     * @param mixed  $default El valor por defecto si el ajuste no existe.
     * @return mixed El valor del ajuste.
     */
    public function get_setting( $key, $default = false ) {
        $options = $this->get_settings();
        return isset( $options[ $key ] ) ? $options[ $key ] : $default;
    }
}
