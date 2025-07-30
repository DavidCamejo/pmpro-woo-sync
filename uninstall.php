<?php
/**
 * Se ejecuta cuando el plugin se desinstala.
 * Elimina todas las opciones, datos y tablas creadas por el plugin.
 * 
 * @package PMPro_Woo_Sync
 * @since 1.0.0
 */

// Si uninstall.php no es llamado por WordPress, salir inmediatamente.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Función principal de desinstalación.
 */
function pmpro_woo_sync_uninstall() {
    // Log de desinstalación (crear una entrada final antes de eliminar todo)
    if ( function_exists( 'error_log' ) ) {
        error_log( 'PMPRO-Woo-Sync: Iniciando proceso de desinstalación completa' );
    }

    // Eliminar opciones del plugin
    pmpro_woo_sync_delete_options();
    
    // Eliminar tablas personalizadas
    pmpro_woo_sync_drop_tables();
    
    // Limpiar metadatos de usuarios y posts
    pmpro_woo_sync_clean_metadata();
    
    // Limpiar tareas cron programadas
    pmpro_woo_sync_clear_cron_jobs();
    
    // Limpiar archivos y directorios
    pmpro_woo_sync_clean_files();
    
    // Limpiar transientes
    pmpro_woo_sync_delete_transients();
    
    // Flush de rewrite rules
    flush_rewrite_rules();
    
    if ( function_exists( 'error_log' ) ) {
        error_log( 'PMPRO-Woo-Sync: Desinstalación completada exitosamente' );
    }
}

/**
 * Elimina todas las opciones del plugin.
 */
function pmpro_woo_sync_delete_options() {
    $options = array(
        // Opciones principales
        'pmpro_woo_sync_settings',
        'pmpro_woo_sync_version',
        'pmpro_woo_sync_db_version',
        
        // Opciones de configuración específicas
        'pmpro_woo_sync_pagbank_settings',
        'pmpro_woo_sync_stripe_settings',
        'pmpro_woo_sync_debug_settings',
        
        // Opciones de estado y cache
        'pmpro_woo_sync_installation_date',
        'pmpro_woo_sync_last_sync',
        'pmpro_woo_sync_sync_stats',
        'pmpro_woo_sync_error_count',
        
        // Opciones de migración y backup
        'pmpro_woo_sync_migration_status',
        'pmpro_woo_sync_backup_settings',
    );

    foreach ( $options as $option ) {
        delete_option( $option );
        
        // Para entornos multisitio
        if ( is_multisite() ) {
            delete_site_option( $option );
            
            // Eliminar de todos los sitios de la red
            $sites = get_sites( array( 'number' => 0 ) );
            foreach ( $sites as $site ) {
                switch_to_blog( $site->blog_id );
                delete_option( $option );
                restore_current_blog();
            }
        }
    }
}

/**
 * Elimina las tablas personalizadas del plugin.
 */
function pmpro_woo_sync_drop_tables() {
    global $wpdb;
    
    // Lista de tablas a eliminar
    $tables = array(
        $wpdb->prefix . 'pmpro_woo_sync_logs',
        $wpdb->prefix . 'pmpro_woo_sync_sync_queue',
        $wpdb->prefix . 'pmpro_woo_sync_error_log',
        $wpdb->prefix . 'pmpro_woo_sync_mapping',
    );
    
    foreach ( $tables as $table ) {
        $wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %i", $table ) );
        
        // Verificar que la tabla fue eliminada
        $table_exists = $wpdb->get_var( $wpdb->prepare( 
            "SHOW TABLES LIKE %s", 
            $wpdb->esc_like( $table ) 
        ));
        
        if ( $table_exists ) {
            error_log( "PMPRO-Woo-Sync: No se pudo eliminar la tabla $table" );
        }
    }
    
    // En entornos multisitio, eliminar de todos los sitios
    if ( is_multisite() ) {
        $sites = get_sites( array( 'number' => 0 ) );
        foreach ( $sites as $site ) {
            $site_tables = array(
                $wpdb->get_blog_prefix( $site->blog_id ) . 'pmpro_woo_sync_logs',
                $wpdb->get_blog_prefix( $site->blog_id ) . 'pmpro_woo_sync_sync_queue',
                $wpdb->get_blog_prefix( $site->blog_id ) . 'pmpro_woo_sync_error_log',
                $wpdb->get_blog_prefix( $site->blog_id ) . 'pmpro_woo_sync_mapping',
            );
            
            foreach ( $site_tables as $table ) {
                $wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %i", $table ) );
            }
        }
    }
}

/**
 * Limpia metadatos relacionados con el plugin.
 */
function pmpro_woo_sync_clean_metadata() {
    global $wpdb;
    
    // Metadatos de usuarios
    $user_meta_keys = array(
        '_pmpro_woo_sync_subscription_id',
        '_pmpro_woo_sync_gateway_customer_id',
        '_pmpro_woo_sync_last_sync',
        '_pmpro_woo_sync_sync_status',
    );
    
    foreach ( $user_meta_keys as $meta_key ) {
        $wpdb->delete( 
            $wpdb->usermeta, 
            array( 'meta_key' => $meta_key ),
            array( '%s' )
        );
    }
    
    // Metadatos de posts (productos, órdenes, suscripciones)
    $post_meta_keys = array(
        '_pmpro_membership_level',
        '_pmpro_woo_sync_linked_level_id',
        '_pmpro_woo_sync_gateway_subscription_id',
        '_pagbank_subscription_id',
        '_pmpro_woo_sync_sync_status',
        '_pmpro_woo_sync_last_sync_date',
    );
    
    foreach ( $post_meta_keys as $meta_key ) {
        $wpdb->delete( 
            $wpdb->postmeta, 
            array( 'meta_key' => $meta_key ),
            array( '%s' )
        );
    }
    
    // Metadatos de comentarios si se usan
    $comment_meta_keys = array(
        '_pmpro_woo_sync_gateway_transaction_id',
    );
    
    foreach ( $comment_meta_keys as $meta_key ) {
        $wpdb->delete( 
            $wpdb->commentmeta, 
            array( 'meta_key' => $meta_key ),
            array( '%s' )
        );
    }
}

/**
 * Limpia tareas cron programadas por el plugin.
 */
function pmpro_woo_sync_clear_cron_jobs() {
    $cron_hooks = array(
        'pmpro_woo_sync_cleanup_logs',
        'pmpro_woo_sync_retry_failed_operations',
        'pmpro_woo_sync_daily_sync',
        'pmpro_woo_sync_hourly_check',
        'pmpro_woo_sync_weekly_report',
    );
    
    foreach ( $cron_hooks as $hook ) {
        // Limpiar todas las instancias programadas del hook
        $timestamps = wp_next_scheduled( $hook );
        while ( $timestamps ) {
            wp_unschedule_event( $timestamps, $hook );
            $timestamps = wp_next_scheduled( $hook );
        }
        
        // Limpiar hooks con argumentos
        wp_clear_scheduled_hook( $hook );
    }
}

/**
 * Limpia archivos y directorios creados por el plugin.
 */
function pmpro_woo_sync_clean_files() {
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/pmpro-woo-sync/';
    
    // Directorios a limpiar
    $directories_to_clean = array(
        WP_CONTENT_DIR . '/plugins/pmpro-woo-sync/logs/',
        $plugin_upload_dir . 'logs/',
        $plugin_upload_dir . 'temp/',
        $plugin_upload_dir . 'exports/',
    );
    
    foreach ( $directories_to_clean as $dir ) {
        if ( is_dir( $dir ) ) {
            pmpro_woo_sync_recursive_rmdir( $dir );
        }
    }
    
    // Archivos específicos a eliminar
    $files_to_clean = array(
        WP_CONTENT_DIR . '/pmpro-woo-sync-debug.log',
        WP_CONTENT_DIR . '/pmpro-woo-sync-error.log',
        $upload_dir['basedir'] . '/pmpro-woo-sync-export.json',
    );
    
    foreach ( $files_to_clean as $file ) {
        if ( file_exists( $file ) ) {
            wp_delete_file( $file );
        }
    }
}

/**
 * Elimina recursivamente un directorio y su contenido.
 *
 * @param string $dir Ruta del directorio a eliminar.
 */
function pmpro_woo_sync_recursive_rmdir( $dir ) {
    if ( ! is_dir( $dir ) ) {
        return;
    }
    
    $files = array_diff( scandir( $dir ), array( '.', '..' ) );
    
    foreach ( $files as $file ) {
        $file_path = $dir . '/' . $file;
        
        if ( is_dir( $file_path ) ) {
            pmpro_woo_sync_recursive_rmdir( $file_path );
        } else {
            wp_delete_file( $file_path );
        }
    }
    
    rmdir( $dir );
}

/**
 * Elimina transientes relacionados con el plugin.
 */
function pmpro_woo_sync_delete_transients() {
    global $wpdb;
    
    // Transientes específicos del plugin
    $transient_patterns = array(
        'pmpro_woo_sync_%',
        'pmpro_woo_pagbank_%',
        'pmpro_woo_stripe_%',
        'pmpro_woo_gateway_%',
    );
    
    foreach ( $transient_patterns as $pattern ) {
        // Eliminar transientes normales
        $wpdb->query( $wpdb->prepare( 
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_' . $pattern,
            '_transient_timeout_' . $pattern
        ));
        
        // Eliminar transientes de sitio (multisitio)
        if ( is_multisite() ) {
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
                '_site_transient_' . $pattern,
                '_site_transient_timeout_' . $pattern
            ));
        }
    }
}

/**
 * Crear backup de configuraciones antes de desinstalar (opcional).
 */
function pmpro_woo_sync_create_uninstall_backup() {
    $settings = get_option( 'pmpro_woo_sync_settings' );
    
    if ( ! empty( $settings ) ) {
        $backup_data = array(
            'settings' => $settings,
            'uninstall_date' => current_time( 'mysql' ),
            'wordpress_version' => get_bloginfo( 'version' ),
            'plugin_version' => defined( 'PMPRO_WOO_SYNC_VERSION' ) ? PMPRO_WOO_SYNC_VERSION : 'unknown',
        );
        
        // Guardar backup en uploads directory
        $upload_dir = wp_upload_dir();
        $backup_file = $upload_dir['basedir'] . '/pmpro-woo-sync-uninstall-backup-' . date( 'Y-m-d-H-i-s' ) . '.json';
        
        file_put_contents( $backup_file, wp_json_encode( $backup_data, JSON_PRETTY_PRINT ) );
        
        // Opcional: Enviar backup por email al administrador
        $admin_email = get_option( 'admin_email' );
        if ( $admin_email ) {
            wp_mail(
                $admin_email,
                'Backup de Configuraciones - PMPro-Woo Sync',
                'Se ha creado un backup de las configuraciones antes de desinstalar el plugin. Archivo: ' . basename( $backup_file ),
                array( 'Content-Type: text/html; charset=UTF-8' )
            );
        }
    }
}

/**
 * Verificar si el usuario realmente quiere desinstalar (medida de seguridad adicional).
 */
function pmpro_woo_sync_confirm_uninstall() {
    // Esta función podría expandirse para incluir confirmaciones adicionales
    // o notificaciones antes de proceder con la desinstalación
    
    return true; // Por ahora, siempre confirma
}

// Ejecutar desinstalación
if ( pmpro_woo_sync_confirm_uninstall() ) {
    // Opcional: Crear backup antes de desinstalar
    // pmpro_woo_sync_create_uninstall_backup();
    
    // Ejecutar desinstalación completa
    pmpro_woo_sync_uninstall();
}
