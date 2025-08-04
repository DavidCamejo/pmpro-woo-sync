<?php
/**
 * Plugin Name:    PMPro-Woo-Sync
 * Plugin URI:    https://github.com/DavidCamejo/pmpro-woo-sync
 * Description:    Sincroniza membresías de <strong>Paid Memberships Pro</strong> con pedidos de <strong>WooCommerce</strong> y pagos recurrentes con <strong>PagBank Connect</strong> (Próximamente más gateways).
 * Version:    2.0.0
 * Author:    David Camejo
 * Author URI:    https://github.com/DavidCamejo
 * License:    MIT
 * License URI:    https://opensource.org/licenses/MIT
 * Text Domain:    pmpro-woo-sync
 * Domain Path:    /languages
 * Requires at least: 5.0
 * Tested up to:   6.6
 * Requires PHP:   7.4
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 * Network:    false
 *
 * @package PMPro_Woo_Sync
 * @author David Camejo
 * @since 1.0.0
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Información y constantes del plugin.
 */
define( 'PMPRO_WOO_SYNC_VERSION', '2.0.0' );
define( 'PMPRO_WOO_SYNC_PLUGIN_FILE', __FILE__ );
define( 'PMPRO_WOO_SYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'PMPRO_WOO_SYNC_URL', plugin_dir_url( __FILE__ ) );
define( 'PMPRO_WOO_SYNC_BASENAME', plugin_basename( __FILE__ ) );
define( 'PMPRO_WOO_SYNC_MIN_PHP_VERSION', '7.4' );
define( 'PMPRO_WOO_SYNC_MIN_WP_VERSION', '5.0' );

/**
 * Cargar clase principal para hooks de activación/desactivación.
 */
function pmpro_woo_sync_load_main_class() {
    $main_class_file = PMPRO_WOO_SYNC_PATH . 'includes/class-pmpro-woo-sync.php';
    
    if ( file_exists( $main_class_file ) && ! class_exists( 'PMPro_Woo_Sync' ) ) {
        require_once $main_class_file;
    }
    
    return class_exists( 'PMPro_Woo_Sync' );
}

/**
 * Verificar dependencias críticas del plugin.
 *
 * @return bool True si todas las dependencias están satisfechas.
 */
function pmpro_woo_sync_check_dependencies() {
    // Solo verificar is_plugin_active si estamos en admin
    if ( ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    $dependencies = array(
        'plugins' => array(
            'paid-memberships-pro/paid-memberships-pro.php' => array(
                'name' => 'Paid Memberships Pro',
                'function' => 'pmpro_getLevel',
                'required' => true,
            ),
            'woocommerce/woocommerce.php' => array(
                'name' => 'WooCommerce',
                'class' => 'WooCommerce',
                'required' => true,
            ),
        ),
        'php_version' => PMPRO_WOO_SYNC_MIN_PHP_VERSION,
        'wp_version' => PMPRO_WOO_SYNC_MIN_WP_VERSION,
    );
    
    $missing = array();
    $warnings = array();
    
    // Verificar versión de PHP
    if ( version_compare( PHP_VERSION, $dependencies['php_version'], '<' ) ) {
        $missing[] = sprintf( 'PHP %s o superior (actual: %s)', $dependencies['php_version'], PHP_VERSION );
    }
    
    // Verificar versión de WordPress
    if ( version_compare( get_bloginfo( 'version' ), $dependencies['wp_version'], '<' ) ) {
        $missing[] = sprintf( 'WordPress %s o superior (actual: %s)', $dependencies['wp_version'], get_bloginfo( 'version' ) );
    }
    
    // Verificar plugins
    foreach ( $dependencies['plugins'] as $plugin => $info ) {
        $is_active = is_plugin_active( $plugin );
        $has_function = isset( $info['function'] ) ? function_exists( $info['function'] ) : true;
        $has_class = isset( $info['class'] ) ? class_exists( $info['class'] ) : true;
        
        if ( ! $is_active || ! $has_function || ! $has_class ) {
            if ( $info['required'] ) {
                $missing[] = $info['name'];
            } else {
                $warnings[] = $info['name'] . ' (recomendado)';
            }
        }
    }
    
    // Mostrar errores críticos
    if ( ! empty( $missing ) ) {
        add_action( 'admin_notices', function() use ( $missing ) {
            echo '<div class="notice notice-error"><p>';
            printf( 
                '<strong>PMPro-Woo-Sync:</strong> %s <br><strong>%s:</strong> %s',
                __( 'No se puede activar. Faltan dependencias críticas', 'pmpro-woo-sync' ),
                __( 'Requerido', 'pmpro-woo-sync' ),
                implode( ', ', $missing )
            );
            echo '</p></div>';
        });
        return false;
    }
    
    // Mostrar advertencias
    if ( ! empty( $warnings ) ) {
        add_action( 'admin_notices', function() use ( $warnings ) {
            echo '<div class="notice notice-warning"><p>';
            printf( 
                '<strong>PMPro-Woo-Sync:</strong> %s <br><strong>%s:</strong> %s',
                __( 'Funcionalidad limitada', 'pmpro-woo-sync' ),
                __( 'Se recomienda instalar', 'pmpro-woo-sync' ),
                implode( ', ', $warnings )
            );
            echo '</p></div>';
        });
    }
    
    return true;
}

/**
 * Autocarga de clases optimizada con mapeo directo.
 */
spl_autoload_register( function ( $class_name ) {
    // Solo autocargar nuestras clases con prefijo 'PMPro_Woo_Sync_'
    if ( strpos( $class_name, 'PMPro_Woo_Sync_' ) !== 0 ) {
        return;
    }
    
    // Mapeo directo de clases a archivos para mejor performance
    $class_map = array(
        'PMPro_Woo_Sync'           => 'includes/class-pmpro-woo-sync.php',
        'PMPro_Woo_Sync_Logger'    => 'includes/class-pmpro-woo-sync-logger.php',
        'PMPro_Woo_Sync_Settings'  => 'includes/class-pmpro-woo-sync-settings.php',
        'PMPro_Woo_Sync_Integrations' => 'includes/class-pmpro-woo-sync-integrations.php',
        'PMPro_Woo_Sync_Admin'     => 'admin/class-pmpro-woo-sync-admin.php',
    );
    
    if ( isset( $class_map[ $class_name ] ) ) {
        $file_path = PMPRO_WOO_SYNC_PATH . $class_map[ $class_name ];
        
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
            
            // VERIFICAR que la clase se cargó correctamente
            if ( ! class_exists( $class_name, false ) ) {
                error_log( "PMPro-Woo-Sync: Clase {$class_name} no se pudo cargar desde {$file_path}" );
            }
            return;
        } else {
            // LOG de archivo faltante
            error_log( "PMPro-Woo-Sync: Archivo no encontrado: {$file_path}" );
        }
    }

    // Fallback al método original si no está en el mapeo
    $file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';
    $directories = array(
        PMPRO_WOO_SYNC_PATH . 'includes/',
        PMPRO_WOO_SYNC_PATH . 'admin/',
    );
    
    foreach ( $directories as $directory ) {
        $file_path = $directory . $file_name;
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
            return;
        }
    }
    
    // Log si no se encuentra la clase (solo en modo debug)
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( "PMPro-Woo-Sync: No se pudo cargar la clase {$class_name}" );
    }
}, true, true ); // AGREGAR prepend=true para prioridad

/**
 * Inicializa el plugin principal.
 *
 * @return PMPro_Woo_Sync|false Instancia del plugin o false si falla.
 */
function pmpro_woo_sync_init() {
    // Verificar dependencias antes de inicializar
    if ( ! pmpro_woo_sync_check_dependencies() ) {
        return false;
    }

    // CARGAR CLASE PRINCIPAL EXPLÍCITAMENTE antes de usarla
    if ( ! pmpro_woo_sync_load_main_class() ) {
        return false;
    }

    // Inicializar plugin
    $plugin = new PMPro_Woo_Sync();
    $plugin->run();
    
    // Hacer disponible globalmente para otros plugins/temas
    $GLOBALS['pmpro_woo_sync'] = $plugin;
    
    return $plugin;
}

/**
 * Hook de activación del plugin.
 */
function activate_pmpro_woo_sync() {
    // Verificar requisitos del sistema
    if ( version_compare( PHP_VERSION, PMPRO_WOO_SYNC_MIN_PHP_VERSION, '<' ) ) {
        deactivate_plugins( PMPRO_WOO_SYNC_BASENAME );
        wp_die( sprintf(
            __( 'PMPro-Woo-Sync requiere PHP %s o superior. Tu versión actual es %s.', 'pmpro-woo-sync' ),
            PMPRO_WOO_SYNC_MIN_PHP_VERSION,
            PHP_VERSION
        ));
    }
    
    if ( version_compare( get_bloginfo( 'version' ), PMPRO_WOO_SYNC_MIN_WP_VERSION, '<' ) ) {
        deactivate_plugins( PMPRO_WOO_SYNC_BASENAME );
        wp_die( sprintf(
            __( 'PMPro-Woo-Sync requiere WordPress %s o superior.', 'pmpro-woo-sync' ),
            PMPRO_WOO_SYNC_MIN_WP_VERSION
        ));
    }
    
    // Cargar la clase principal antes de usarla
    if ( ! pmpro_woo_sync_load_main_class() ) {
        wp_die( __( 'No se pudo cargar la clase principal del plugin. Verifique que todos los archivos estén presentes.', 'pmpro-woo-sync' ) );
    }
    
    // Ejecutar activación
    PMPro_Woo_Sync::activate();
    
    // Registrar fecha de instalación
    add_option( 'pmpro_woo_sync_installation_date', current_time( 'mysql' ) );
    
    // Limpiar cache de permalinks
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'activate_pmpro_woo_sync' );

/**
 * Hook de desactivación del plugin.
 */
function deactivate_pmpro_woo_sync() {
    // Cargar la clase principal si es necesario
    if ( pmpro_woo_sync_load_main_class() ) {
        PMPro_Woo_Sync::deactivate();
    }
    
    // Limpiar cache de permalinks
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'deactivate_pmpro_woo_sync' );

/**
 * Inicializar el plugin después de que todos los plugins estén cargados.
 */
add_action( 'plugins_loaded', 'pmpro_woo_sync_init', 10 );

/**
 * Función de debug para troubleshooting (mencionada en documentación).
 *
 * @param int $user_id ID del usuario a debuggear.
 * @return array Información de debug del usuario.
 */
function pmpro_woo_sync_debug_info( $user_id ) {
    if ( ! $user_id || ! get_userdata( $user_id ) ) {
        return array( 'error' => 'Usuario inválido' );
    }
    
    $debug_info = array(
        'user_id' => $user_id,
        'current_membership' => null,
        'recent_orders' => array(),
        'wc_subscriptions' => array(),
        'plugin_metadata' => array(),
    );
    
    // Información de membresía actual
    if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
        $level = pmpro_getMembershipLevelForUser( $user_id );
        if ( $level ) {
            $debug_info['current_membership'] = array(
                'ID' => $level->id,
                'name' => $level->name,
                'enddate' => $level->enddate,
                'startdate' => $level->startdate,
            );
        }
    }
    
    // Órdenes recientes de WooCommerce
    if ( function_exists( 'wc_get_orders' ) ) {
        $orders = wc_get_orders( array(
            'customer' => $user_id,
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        foreach ( $orders as $order ) {
            $debug_info['recent_orders'][] = array(
                'id' => $order->get_id(),
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'date' => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
                'is_subscription' => $order->get_meta( '_subscription_renewal' ) ? true : false,
            );
        }
    }
    
    // Suscripciones de WooCommerce
    if ( function_exists( 'wcs_get_users_subscriptions' ) ) {
        $subscriptions = wcs_get_users_subscriptions( $user_id );
        foreach ( $subscriptions as $subscription ) {
            $debug_info['wc_subscriptions'][] = array(
                'id' => $subscription->get_id(),
                'status' => $subscription->get_status(),
                'start_date' => $subscription->get_date( 'start' ),
                'next_payment' => $subscription->get_date( 'next_payment' ),
                'pmpro_linked_level' => $subscription->get_meta( '_pmpro_linked_level_id' ),
            );
        }
    }
    
    // Metadatos del plugin
    $meta_keys = array(
        '_pmpro_woo_sync_subscription_id',
        '_pmpro_woo_sync_last_sync',
        '_pmpro_woo_sync_sync_status',
    );
    
    foreach ( $meta_keys as $key ) {
        $value = get_user_meta( $user_id, $key, true );
        if ( $value ) {
            $debug_info['plugin_metadata'][ $key ] = $value;
        }
    }
    
    return $debug_info;
}

/**
 * Enlaces útiles en la página de plugins.
 */
function pmpro_woo_sync_plugin_action_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=pmpro-woo-sync' ) . '">' . __( 'Configuración', 'pmpro-woo-sync' ) . '</a>',
        '<a href="' . admin_url( 'admin.php?page=pmpro-woo-sync-logs' ) . '">' . __( 'Logs', 'pmpro-woo-sync' ) . '</a>',
        '<a href="https://github.com/DavidCamejo/pmpro-woo-sync" target="_blank">' . __( 'Documentación', 'pmpro-woo-sync' ) . '</a>',
    );
    
    return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . PMPRO_WOO_SYNC_BASENAME, 'pmpro_woo_sync_plugin_action_links' );

/**
 * Metadatos adicionales en la página de plugins.
 */
function pmpro_woo_sync_plugin_row_meta( $links, $file ) {
    if ( $file === PMPRO_WOO_SYNC_BASENAME ) {
        $row_meta = array(
            'issues' => '<a href="https://github.com/DavidCamejo/pmpro-woo-sync/issues" target="_blank">' . __( 'Reportar Issue', 'pmpro-woo-sync' ) . '</a>',
            'support' => '<a href="mailto:jdavidcamejo@gmail.com">' . __( 'Soporte', 'pmpro-woo-sync' ) . '</a>',
        );
        
        return array_merge( $links, $row_meta );
    }
    
    return $links;
}
add_filter( 'plugin_row_meta', 'pmpro_woo_sync_plugin_row_meta', 10, 2 );
