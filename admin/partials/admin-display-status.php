<?php
/**
 * Plantilla para la página de estado del sistema.
 * Variables disponibles: $system_info, $dependency_status, $this (PMPro_Woo_Sync_Admin instance)
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Estado del Sistema - PMPro-Woo-Sync', 'pmpro-woo-sync' ); ?></h1>

    <div class="pmpro-woo-sync-status-grid">
        
        <!-- Estado General -->
        <div class="status-section">
            <h2><?php esc_html_e( 'Estado General', 'pmpro-woo-sync' ); ?></h2>
            
            <table class="widefat fixed" cellspacing="0">
                <tbody>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Plugin Activo', 'pmpro-woo-sync' ); ?></td>
                        <td><span class="status-indicator active">✓</span> <?php esc_html_e( 'Sí', 'pmpro-woo-sync' ); ?></td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Sincronización Habilitada', 'pmpro-woo-sync' ); ?></td>
                        <td>
                            <?php 
                            $sync_enabled = $this->settings->get_setting( 'enable_sync', 'yes' ) === 'yes';
                            if ( $sync_enabled ) : ?>
                                <span class="status-indicator active">✓</span> <?php esc_html_e( 'Sí', 'pmpro-woo-sync' ); ?>
                            <?php else : ?>
                                <span class="status-indicator inactive">✗</span> <?php esc_html_e( 'No', 'pmpro-woo-sync' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Modo Debug', 'pmpro-woo-sync' ); ?></td>
                        <td>
                            <?php 
                            $debug_mode = $this->settings->get_setting( 'debug_mode', 'no' ) === 'yes';
                            if ( $debug_mode ) : ?>
                                <span class="status-indicator warning">⚠</span> <?php esc_html_e( 'Activo', 'pmpro-woo-sync' ); ?>
                            <?php else : ?>
                                <span class="status-indicator inactive">✗</span> <?php esc_html_e( 'Inactivo', 'pmpro-woo-sync' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Información del Sistema -->
        <div class="status-section">
            <h2><?php esc_html_e( 'Información del Sistema', 'pmpro-woo-sync' ); ?></h2>
            
            <table class="widefat fixed" cellspacing="0">
                <tbody>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Versión del Plugin', 'pmpro-woo-sync' ); ?></td>
                        <td><?php echo esc_html( $system_info['plugin_version'] ); ?></td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'WordPress', 'pmpro-woo-sync' ); ?></td>
                        <td><?php echo esc_html( $system_info['wordpress_version'] ); ?></td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'PHP', 'pmpro-woo-sync' ); ?></td>
                        <td>
                            <?php 
                            $php_ok = version_compare( $system_info['php_version'], '7.4', '>=' );
                            echo esc_html( $system_info['php_version'] );
                            if ( ! $php_ok ) : ?>
                                <span class="status-indicator warning">⚠</span> <?php esc_html_e( 'Se recomienda PHP 7.4+', 'pmpro-woo-sync' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'MySQL', 'pmpro-woo-sync' ); ?></td>
                        <td><?php echo esc_html( $system_info['mysql_version'] ); ?></td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Límite de Memoria', 'pmpro-woo-sync' ); ?></td>
                        <td><?php echo esc_html( $system_info['memory_limit'] ); ?></td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Tiempo Max. Ejecución', 'pmpro-woo-sync' ); ?></td>
                        <td><?php echo esc_html( $system_info['max_execution_time'] ); ?> <?php esc_html_e( 'segundos', 'pmpro-woo-sync' ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Estado de Dependencias -->
        <div class="status-section">
            <h2><?php esc_html_e( 'Dependencias', 'pmpro-woo-sync' ); ?></h2>
            
            <table class="widefat fixed" cellspacing="0">
                <tbody>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Paid Memberships Pro', 'pmpro-woo-sync' ); ?></td>
                        <td>
                            <?php if ( $dependency_status['pmpro_active'] ) : ?>
                                <span class="status-indicator active">✓</span> <?php esc_html_e( 'Activo', 'pmpro-woo-sync' ); ?>
                                <?php 
                                if ( defined( 'PMPRO_VERSION' ) ) {
                                    echo ' (v' . esc_html( PMPRO_VERSION ) . ')';
                                }
                                ?>
                            <?php else : ?>
                                <span class="status-indicator error">✗</span> <?php esc_html_e( 'No Instalado/Activo', 'pmpro-woo-sync' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'WooCommerce', 'pmpro-woo-sync' ); ?></td>
                        <td>
                            <?php if ( $dependency_status['woocommerce_active'] ) : ?>
                                <span class="status-indicator active">✓</span> <?php esc_html_e( 'Activo', 'pmpro-woo-sync' ); ?>
                                <?php 
                                if ( defined( 'WC_VERSION' ) ) {
                                    echo ' (v' . esc_html( WC_VERSION ) . ')';
                                }
                                ?>
                            <?php else : ?>
                                <span class="status-indicator error">✗</span> <?php esc_html_e( 'No Instalado/Activo', 'pmpro-woo-sync' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'WooCommerce Subscriptions', 'pmpro-woo-sync' ); ?></td>
                        <td>
                            <?php if ( $dependency_status['wc_subscriptions_active'] ) : ?>
                                <span class="status-indicator active">✓</span> <?php esc_html_e( 'Activo', 'pmpro-woo-sync' ); ?>
                                <?php 
                                if ( class_exists( 'WC_Subscriptions' ) && defined( 'WC_Subscriptions::$version' ) ) {
                                    echo ' (v' . esc_html( WC_Subscriptions::$version ) . ')';
                                }
                                ?>
                            <?php else : ?>
                                <span class="status-indicator warning">⚠</span> <?php esc_html_e( 'Recomendado pero no crítico', 'pmpro-woo-sync' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Estado de Gateways -->
        <div class="status-section">
            <h2><?php esc_html_e( 'Estado de Gateways', 'pmpro-woo-sync' ); ?></h2>
            
            <table class="widefat fixed" cellspacing="0">
                <tbody>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'PagBank API', 'pmpro-woo-sync' ); ?></td>
                        <td>
                            <?php 
                            $pagbank_api_key = $this->settings->get_setting( 'pagbank_api_settings.api_key' );
                            if ( ! empty( $pagbank_api_key ) ) : ?>
                                <span class="status-indicator active">✓</span> <?php esc_html_e( 'Configurado', 'pmpro-woo-sync' ); ?>
                                <button type="button" id="test-pagbank-status" class="button button-small" style="margin-left: 10px;">
                                    <?php esc_html_e( 'Probar Conexión', 'pmpro-woo-sync' ); ?>
                                </button>
                            <?php else : ?>
                                <span class="status-indicator warning">⚠</span> <?php esc_html_e( 'No Configurado', 'pmpro-woo-sync' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Modo PagBank', 'pmpro-woo-sync' ); ?></td>
                        <td>
                            <?php 
                            $pagbank_mode = $this->settings->get_setting( 'pagbank_api_settings.mode', 'live' );
                            echo esc_html( ucfirst( $pagbank_mode ) );
                            if ( $pagbank_mode === 'sandbox' ) : ?>
                                <span class="status-indicator warning">⚠</span> <?php esc_html_e( 'Modo Pruebas', 'pmpro-woo-sync' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Estadísticas de Logs -->
        <?php $log_stats = $this->logger->get_log_stats(); ?>
        <div class="status-section">
            <h2><?php esc_html_e( 'Estadísticas de Logs', 'pmpro-woo-sync' ); ?></h2>
            
            <table class="widefat fixed" cellspacing="0">
                <tbody>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Total de Logs', 'pmpro-woo-sync' ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $log_stats['total'] ?? 0 ) ); ?></td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Logs (Últimas 24h)', 'pmpro-woo-sync' ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $log_stats['last_24h'] ?? 0 ) ); ?></td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Errores Totales', 'pmpro-woo-sync' ); ?></td>
                        <td>
                            <?php 
                            $error_count = $log_stats['by_level']['error'] ?? 0;
                            echo esc_html( number_format_i18n( $error_count ) );
                            if ( $error_count > 0 ) : ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-woo-sync-logs&log_level_filter=error' ) ); ?>" class="button button-small" style="margin-left: 10px;">
                                    <?php esc_html_e( 'Ver Errores', 'pmpro-woo-sync' ); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Advertencias Totales', 'pmpro-woo-sync' ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $log_stats['by_level']['warning'] ?? 0 ) ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Configuraciones Críticas -->
        <div class="status-section">
            <h2><?php esc_html_e( 'Configuraciones Críticas', 'pmpro-woo-sync' ); ?></h2>
            
            <table class="widefat fixed" cellspacing="0">
                <tbody>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Reintentos Máximos', 'pmpro-woo-sync' ); ?></td>
                        <td><?php echo esc_html( $this->settings->get_setting( 'retry_attempts', 3 ) ); ?></td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Tamaño de Lote', 'pmpro-woo-sync' ); ?></td>
                        <td><?php echo esc_html( $this->settings->get_setting( 'batch_size', 50 ) ); ?></td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Retención de Logs', 'pmpro-woo-sync' ); ?></td>
                        <td><?php echo esc_html( $this->settings->get_setting( 'log_retention_days', 30 ) ); ?> <?php esc_html_e( 'días', 'pmpro-woo-sync' ); ?></td>
                    </tr>
                    <tr>
                        <td class="row-title"><?php esc_html_e( 'Timeout API', 'pmpro-woo-sync' ); ?></td>
                        <td><?php echo esc_html( $this->settings->get_setting( 'api_timeout', 30 ) ); ?> <?php esc_html_e( 'segundos', 'pmpro-woo-sync' ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="pmpro-woo-sync-quick-actions-footer">
        <h3><?php esc_html_e( 'Acciones Rápidas', 'pmpro-woo-sync' ); ?></h3>
        
        <p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-woo-sync' ) ); ?>" class="button button-primary">
                <?php esc_html_e( 'Ir a Configuración', 'pmpro-woo-sync' ); ?>
            </a>
            
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-woo-sync-logs' ) ); ?>" class="button button-secondary">
                <?php esc_html_e( 'Ver Logs', 'pmpro-woo-sync' ); ?>
            </a>
            
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-woo-sync-tools' ) ); ?>" class="button button-secondary">
                <?php esc_html_e( 'Abrir Herramientas', 'pmpro-woo-sync' ); ?>
            </a>
        </p>
    </div>
</div>

<style>
.pmpro-woo-sync-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.status-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.status-section h2 {
    margin: 0;
    padding: 15px 20px;
    border-bottom: 1px solid #c3c4c7;
    background: #f6f7f7;
    font-size: 14px;
}

.status-section table {
    border: none;
    margin: 0;
}

.status-section .row-title {
    width: 40%;
    font-weight: 600;
    padding-left: 20px;
}

.status-indicator {
    display: inline-block;
    width: 16px;
    height: 16px;
    text-align: center;
    line-height: 16px;
    border-radius: 50%;
    color: #fff;
    font-size: 12px;
    font-weight: bold;
    margin-right: 5px;
}

.status-indicator.active {
    background-color: #00a32a;
}

.status-indicator.inactive {
    background-color: #646970;
}

.status-indicator.warning {
    background-color: #dba617;
}

.status-indicator.error {
    background-color: #d63638;
}

.pmpro-woo-sync-quick-actions-footer {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
    border-radius: 4px;
    text-align: center;
}

.pmpro-woo-sync-quick-actions-footer .button {
    margin: 0 5px;
}

@media (max-width: 768px) {
    .pmpro-woo-sync-status-grid {
        grid-template-columns: 1fr;
    }
    
    .status-section .row-title {
        width: 100%;
        display: block;
        padding-bottom: 5px;
    }
    
    .status-section td:last-child {
        padding-left: 20px;
        display: block;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#test-pagbank-status').on('click', function() {
        var $button = $(this);
        var originalText = $button.text();
        
        $button.text('Probando...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pmpro_woo_sync_test_connection',
                gateway: 'pagbank',
                nonce: '<?php echo wp_create_nonce( 'pmpro_woo_sync_admin_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Conexión exitosa con PagBank');
                } else {
                    alert('Error de conexión: ' + (response.data.message || 'Error desconocido'));
                }
            },
            error: function() {
                alert('Error de conexión AJAX');
            },
            complete: function() {
                $button.text(originalText).prop('disabled', false);
            }
        });
    });
});
</script>
