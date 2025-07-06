<?php
/**
 * Plugin Name:       PMPRO-WooCommerce Sync
 * Plugin URI:        https://github.com/DavidCamejo/pmpro-woo-sync
 * Description:       Sincroniza niveles de membresía de Paid Memberships Pro con productos de WooCommerce.
 * Version:           1.0.0
 * Author:            David Camejo
 * Author URI:        https://github.com/DavidCamejo
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pmpro-woo-sync
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      7.0
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Constantes del plugin.
 */
define( 'PMPRO_WOO_SYNC_VERSION', '1.0.0' );
define( 'PMPRO_WOO_SYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'PMPRO_WOO_SYNC_URL', plugin_dir_url( __FILE__ ) );
define( 'PMPRO_WOO_SYNC_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autocarga de clases.
 * Una forma simple de autocarga para este ejemplo.
 * Para plugins más grandes, considere usar Composer PSR-4.
 */
spl_autoload_register( function ( $class_name ) {
    // Solo autocargar nuestras clases con prefijo 'PMPro_Woo_Sync_'
    if ( strpos( $class_name, 'PMPro_Woo_Sync_' ) === 0 ) {
        $file_name = str_replace( 'PMPro_Woo_Sync_', 'class-pmpro-woo-sync-', strtolower( $class_name ) );

        // Rutas potenciales para el archivo de clase
        $include_path = PMPRO_WOO_SYNC_PATH . 'includes/' . $file_name . '.php';
        $admin_path   = PMPRO_WOO_SYNC_PATH . 'admin/' . $file_name . '.php';
        $gateway_path = PMPRO_WOO_SYNC_PATH . 'includes/gateways/' . $file_name . '.php'; // ¡NUEVO!

        if ( file_exists( $include_path ) ) {
            require_once $include_path;
        } elseif ( file_exists( $admin_path ) ) {
            require_once $admin_path;
        } elseif ( file_exists( $gateway_path ) ) { // ¡NUEVO!
            require_once $gateway_path;
        }
    }
});

/**
 * Asegúrate de que las clases principales estén disponibles antes de instanciar el plugin.
 * El autoloader gestiona las clases con el prefijo PMPro_Woo_Sync_.
 * La clase principal PMPro_Woo_Sync no usa ese prefijo para su propio archivo,
 * por lo que necesita ser incluida explícitamente.
 * Además, las clases que son inmediatamente necesarias para la inicialización (Logger, Settings, Admin, Integrations)
 * también deberían ser incluidas explícitamente para asegurar su disponibilidad,
 * ya que el autoloader podría no haber terminado de registrarse o ejecutarse en el momento exacto.
 */
require_once PMPRO_WOO_SYNC_PATH . 'includes/class-pmpro-woo-sync.php';
require_once PMPRO_WOO_SYNC_PATH . 'includes/class-pmpro-woo-sync-logger.php';
require_once PMPRO_WOO_SYNC_PATH . 'includes/class-pmpro-woo-sync-settings.php';
require_once PMPRO_WOO_SYNC_PATH . 'includes/class-pmpro-woo-sync-integrations.php';
require_once PMPRO_WOO_SYNC_PATH . 'includes/class-pmpro-woo-sync-gateway-manager.php'; // ¡NUEVO!
require_once PMPRO_WOO_SYNC_PATH . 'admin/class-pmpro-woo-sync-admin.php';

/**
 * Inicializa el plugin.
 *
 * @since 1.0.0
 */
function run_pmpro_woo_sync() {
    $plugin = new PMPro_Woo_Sync();
    $plugin->run();
}
run_pmpro_woo_sync();

/**
 * Hook de activación del plugin.
 * Registra acciones necesarias al activar el plugin.
 * Por ejemplo, crear tablas de base de datos, configurar opciones predeterminadas.
 */
function activate_pmpro_woo_sync() {
    // Estas clases ya deberían estar cargadas por los require_once, pero se mantienen por seguridad.
    // Aunque el autoloader podría no estar completamente operativo durante el hook de activación.
    // Por eso se usan las llamadas estáticas para la activación/desactivación.
    PMPro_Woo_Sync::activate();
}
register_activation_hook( __FILE__, 'activate_pmpro_woo_sync' );

/**
 * Hook de desactivación del plugin.
 * Registra acciones necesarias al desactivar el plugin.
 * Por ejemplo, limpiar opciones temporales.
 */
function deactivate_pmpro_woo_sync() {
    // Similar a la activación, estas clases ya deberían estar cargadas.
    PMPro_Woo_Sync::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_pmpro_woo-sync' ); // Corregido: pmpro-woo-sync
