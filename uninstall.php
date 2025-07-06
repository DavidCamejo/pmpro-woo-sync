<?php
/**
 * Se ejecuta cuando el plugin se desinstala.
 * Elimina todas las opciones, datos y tablas creadas por el plugin.
 */

// Si uninstall.php no es llamado por WordPress, salir.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// Opciones a eliminar.
$options = array(
    'pmpro_woo_sync_settings', // La opción que guardará todas las configuraciones del plugin.
    // Añadir aquí otras opciones o transitorios si se crean.
);

foreach ( $options as $option ) {
    delete_option( $option );
    delete_site_option( $option ); // Para entornos multisitio
}

// Si se creó una tabla personalizada para logs, eliminarla aquí.
global $wpdb;
$table_name = $wpdb->prefix . 'pmpro_woo_sync_logs';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
