<?php
/**
 * Plantilla para mostrar el estado del sistema en PMPro-Woo-Sync
 *
 * @package PMPro_Woo_Sync
 * @since 2.0.0
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Obtener información del sistema y dependencias
$system_info = $this->get_system_information();
$dependency_status = $this->check_dependencies_status();
$sync_stats = $this->get_sync_statistics();
?>

<div class="wrap pmpro-woo-sync-admin">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <!-- Estado de Dependencias -->
    <div class="pmpro-woo-sync-status-section">
        <h2><?php esc_html_e( 'Estado de Dependencias', 'pmpro-woo-sync' ); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Componente', 'pmpro-woo-sync' ); ?></th>
                    <th><?php esc_html_e( 'Estado', 'pmpro-woo-sync' ); ?></th>
                    <th><?php esc_html_e( 'Versión', 'pmpro-woo-sync' ); ?></th>
                    <th><?php esc_html_e( 'Notas', 'pmpro-woo-sync' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?php esc_html_e( 'Paid Memberships Pro', 'pmpro-woo-sync' ); ?></strong></td>
                    <td>
                        <span class="status-indicator <?php echo $dependency_status['pmpro_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $dependency_status['pmpro_active'] ? __( 'Activo', 'pmpro-woo-sync' ) : __( 'Inactivo', 'pmpro-woo-sync' ); ?>
                        </span>
                    </td>
                    <td><?php echo $dependency_status['pmpro_active'] ? esc_html( $dependency_status['pmpro_version'] ?? '—' ) : '—'; ?></td>
                    <td>
                        <?php if ( ! $dependency_status['pmpro_active'] ) : ?>
                            <span class="error"><?php esc_html_e( 'Requerido para funcionar', 'pmpro-woo-sync' ); ?></span>
                        <?php else : ?>
                            <span class="success"><?php esc_html_e( 'Funcionando correctamente', 'pmpro-woo-sync' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'WooCommerce', 'pmpro-woo-sync' ); ?></strong></td>
                    <td>
                        <span class="status-indicator <?php echo $dependency_status['woocommerce_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $dependency_status['woocommerce_active'] ? __( 'Activo', 'pmpro-woo-sync' ) : __( 'Inactivo', 'pmpro-woo-sync' ); ?>
                        </span>
                    </td>
                    <td><?php echo $dependency_status['woocommerce_active'] ? esc_html( $dependency_status['woocommerce_version'] ?? '—' ) : '—'; ?></td>
                    <td>
                        <?php if ( ! $dependency_status['woocommerce_active'] ) : ?>
                            <span class="error"><?php esc_html_e( 'Requerido para funcionar', 'pmpro-woo-sync' ); ?></span>
                        <?php else : ?>
                            <span class="success"><?php esc_html_e( 'Funcionando correctamente', 'pmpro-woo-sync' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'PMPro - WooCommerce', 'pmpro-woo-sync' ); ?></strong></td>
                    <td>
                        <span class="status-indicator <?php echo $dependency_status['pmpro_woocommerce_active'] ? 'active' : 'warning'; ?>">
                            <?php echo $dependency_status['pmpro_woocommerce_active'] ? __( 'Activo', 'pmpro-woo-sync' ) : __( 'Inactivo', 'pmpro-woo-sync' ); ?>
                        </span>
                    </td>
                    <td><?php echo $dependency_status['pmpro_woocommerce_active'] ? esc_html( $dependency_status['pmpro_woocommerce_version'] ?? '—' ) : '—'; ?></td>
                    <td>
                        <?php if ( ! $dependency_status['pmpro_woocommerce_active'] ) : ?>
                            <span class="warning"><?php esc_html_e( 'Recomendado para integración completa', 'pmpro-woo-sync' ); ?></span>
                            <br><small><a href="https://github.com/strangerstudios/pmpro-woocommerce" target="_blank"><?php esc_html_e( 'Descargar Plugin', 'pmpro-woo-sync' ); ?></a></small>
                        <?php else : ?>
                            <span class="success"><?php esc_html_e( 'Integración disponible', 'pmpro-woo-sync' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'PagBank Connect', 'pmpro-woo-sync' ); ?></strong></td>
                    <td>
                        <span class="status-indicator <?php echo $dependency_status['pagbank_active'] ? 'active' : 'warning'; ?>">
                            <?php echo $dependency_status['pagbank_active'] ? __( 'Activo', 'pmpro-woo-sync' ) : __( 'Inactivo', 'pmpro-woo-sync' ); ?>
                        </span>
                    </td>
                    <td><?php echo $dependency_status['pagbank_active'] ? esc_html( $dependency_status['pagbank_version'] ?? '—' ) : '—'; ?></td>
                    <td>
                        <?php if ( ! $dependency_status['pagbank_active'] ) : ?>
                            <span class="warning"><?php esc_html_e( 'Recomendado para pagos recurrentes', 'pmpro-woo-sync' ); ?></span>
                            <br><small><a href="https://github.com/r-martins/PagBank-WooCommerce" target="_blank"><?php esc_html_e( 'Descargar Plugin', 'pmpro-woo-sync' ); ?></a></small>
                        <?php else : ?>
                            <span class="success"><?php esc_html_e( 'Pagos recurrentes disponibles', 'pmpro-woo-sync' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Información del Sistema -->
    <div class="pmpro-woo-sync-status-section">
        <h2><?php esc_html_e( 'Información del Sistema', 'pmpro-woo-sync' ); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <tbody>
                <tr>
                    <td><strong><?php esc_html_e( 'Versión de WordPress', 'pmpro-woo-sync' ); ?></strong></td>
                    <td><?php echo esc_html( $system_info['wordpress_version'] ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Versión de PHP', 'pmpro-woo-sync' ); ?></strong></td>
                    <td><?php echo esc_html( $system_info['php_version'] ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Versión de MySQL', 'pmpro-woo-sync' ); ?></strong></td>
                    <td><?php echo esc_html( $system_info['mysql_version'] ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Versión del Plugin', 'pmpro-woo-sync' ); ?></strong></td>
                    <td><?php echo esc_html( $system_info['plugin_version'] ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Límite de Memoria', 'pmpro-woo-sync' ); ?></strong></td>
                    <td><?php echo esc_html( $system_info['memory_limit'] ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Tiempo Máximo de Ejecución', 'pmpro-woo-sync' ); ?></strong></td>
                    <td><?php echo esc_html( $system_info['max_execution_time'] ); ?> <?php esc_html_e( 'segundos', 'pmpro-woo-sync' ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Gateways de Pago Activos', 'pmpro-woo-sync' ); ?></strong></td>
                    <td>
                        <?php
                        $gateways = $this->get_active_payment_gateways();
                        if ( ! empty( $gateways ) ) {
                            echo esc_html( implode( ', ', $gateways ) );
                        } else {
                            esc_html_e( 'Ninguno configurado', 'pmpro-woo-sync' );
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Estadísticas de Sincronización -->
    <div class="pmpro-woo-sync-status-section">
        <h2><?php esc_html_e( 'Estadísticas de Sincronización', 'pmpro-woo-sync' ); ?></h2>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number"><?php echo esc_html( $sync_stats['total_synced_users'] ); ?></span>
                <span class="stat-label"><?php esc_html_e( 'Usuarios Sincronizados', 'pmpro-woo-sync' ); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo esc_html( $sync_stats['active_orders'] ); ?></span>
                <span class="stat-label"><?php esc_html_e( 'Pedidos Activos', 'pmpro-woo-sync' ); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo esc_html( $sync_stats['last_sync'] ); ?></span>
                <span class="stat-label"><?php esc_html_e( 'Última Sincronización', 'pmpro-woo-sync' ); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo esc_html( $sync_stats['sync_errors'] ); ?></span>
                <span class="stat-label"><?php esc_html_e( 'Errores (24h)', 'pmpro-woo-sync' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Información de Configuración -->
    <div class="pmpro-woo-sync-status-section">
        <h2><?php esc_html_e( 'Configuración Actual', 'pmpro-woo-sync' ); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <tbody>
                <tr>
                    <td><strong><?php esc_html_e( 'Sincronización Habilitada', 'pmpro-woo-sync' ); ?></strong></td>
                    <td>
                        <?php if ( $this->settings->is_sync_enabled() ) : ?>
                            <span class="status-indicator active"><?php esc_html_e( 'Sí', 'pmpro-woo-sync' ); ?></span>
                        <?php else : ?>
                            <span class="status-indicator inactive"><?php esc_html_e( 'No', 'pmpro-woo-sync' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Modo Debug', 'pmpro-woo-sync' ); ?></strong></td>
                    <td>
                        <?php if ( $this->settings->is_debug_enabled() ) : ?>
                            <span class="status-indicator warning"><?php esc_html_e( 'Activado', 'pmpro-woo-sync' ); ?></span>
                        <?php else : ?>
                            <span class="status-indicator inactive"><?php esc_html_e( 'Desactivado', 'pmpro-woo-sync' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Retención de Logs', 'pmpro-woo-sync' ); ?></strong></td>
                    <td><?php echo esc_html( $this->settings->get_log_retention_days() ); ?> <?php esc_html_e( 'días', 'pmpro-woo-sync' ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Auto-vincular Productos', 'pmpro-woo-sync' ); ?></strong></td>
                    <td>
                        <?php if ( $this->settings->get_setting( 'auto_link_products' ) ) : ?>
                            <span class="status-indicator active"><?php esc_html_e( 'Sí', 'pmpro-woo-sync' ); ?></span>
                        <?php else : ?>
                            <span class="status-indicator inactive"><?php esc_html_e( 'No', 'pmpro-woo-sync' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Acciones de diagnóstico -->
    <div class="pmpro-woo-sync-status-section">
        <h2><?php esc_html_e( 'Acciones de Diagnóstico', 'pmpro-woo-sync' ); ?></h2>
        <div class="diagnostic-actions">
            <button type="button" class="button button-primary" id="run-diagnostic-test">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php esc_html_e( 'Ejecutar Prueba de Diagnóstico', 'pmpro-woo-sync' ); ?>
            </button>
            <button type="button" class="button button-secondary" id="export-system-info">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e( 'Exportar Información del Sistema', 'pmpro-woo-sync' ); ?>
            </button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-woo-sync-logs' ) ); ?>" class="button button-secondary">
                <span class="dashicons dashicons-list-view"></span>
                <?php esc_html_e( 'Ver Logs Detallados', 'pmpro-woo-sync' ); ?>
            </a>
        </div>
    </div>
</div>

<style>
.pmpro-woo-sync-status-section {
    margin-bottom: 30px;
}
.pmpro-woo-sync-status-section h2 {
    margin-top: 0;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}
.status-indicator {
    padding: 3px 10px;
    border-radius: 3px;
    font-weight: bold;
    font-size: 0.9em;
}
.status-indicator.active {
    color: #155724;
    background: #d4edda;
}
.status-indicator.inactive {
    color: #721c24;
    background: #f8d7da;
}
.status-indicator.warning {
    color: #856404;
    background: #fff3cd;
}
.stats-grid {
    display: flex;
    gap: 20px;
    margin-top: 20px;
    flex-wrap: wrap;
}
.stat-item {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    text-align: center;
    min-width: 150px;
    flex: 1;
}
.stat-number {
    font-size: 2.2em;
    font-weight: bold;
    display: block;
    margin-bottom: 8px;
    color: #0073aa;
}
.stat-label {
    color: #666;
    font-size: 0.9em;
}
.diagnostic-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.error {
    color: #d63384;
    font-weight: bold;
}
.warning {
    color: #ffc107;
    font-weight: bold;
}
.success {
    color: #198754;
    font-weight: bold;
}
</style>


<script type="text/javascript">
jQuery(document).ready(function($) {
    // Variables globales escapadas correctamente
    var adminAjaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var nonce = <?php echo wp_json_encode( wp_create_nonce( 'pmpro_woo_sync_admin_nonce' ) ); ?>;
    
    // Prueba de diagnóstico
    $('#run-diagnostic-test').on('click', function() {
        var $button = $(this);
        var originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + <?php echo wp_json_encode( __( 'Ejecutando...', 'pmpro-woo-sync' ) ); ?>);
        
        $.ajax({
            url: adminAjaxUrl,
            type: 'POST',
            data: {
                action: 'pmpro_woo_sync_diagnostic_test',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Escapar contenido antes de mostrar
                    var message = $('<div>').text(response.data.message).html().replace(/\n/g, '<br>');
                    var modalHtml = '<div style="max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; line-height: 1.4; white-space: pre-wrap;">' + message + '</div>';
                    
                    // Crear modal seguro
                    var modal = $('<div>').css({
                        position: 'fixed',
                        top: 0,
                        left: 0,
                        width: '100%',
                        height: '100%',
                        backgroundColor: 'rgba(0,0,0,0.5)',
                        zIndex: 9999,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center'
                    });
                    
                    var content = $('<div>').css({
                        backgroundColor: 'white',
                        padding: '20px',
                        borderRadius: '5px',
                        maxWidth: '600px',
                        width: '90%'
                    }).html('<h3>' + <?php echo wp_json_encode( __( 'Resultado del Diagnóstico', 'pmpro-woo-sync' ) ); ?> + '</h3>' + modalHtml + '<br><button class="button modal-close">' + <?php echo wp_json_encode( __( 'Cerrar', 'pmpro-woo-sync' ) ); ?> + '</button>');
                    
                    modal.append(content);
                    $('body').append(modal);
                    
                    // Event handler para cerrar modal
                    modal.find('.modal-close').on('click', function() {
                        modal.remove();
                    });
                    
                    // Cerrar modal al hacer clic fuera
                    modal.on('click', function(e) {
                        if (e.target === this) {
                            $(this).remove();
                        }
                    });
                } else {
                    alert(<?php echo wp_json_encode( __( 'Error en el diagnóstico: ', 'pmpro-woo-sync' ) ); ?> + (response.data ? $('<div>').text(response.data.message).html() : 'Error desconocido'));
                }
            },
            error: function() {
                alert(<?php echo wp_json_encode( __( 'Error al ejecutar el diagnóstico.', 'pmpro-woo-sync' ) ); ?>);
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });

    // Exportar información del sistema
    $('#export-system-info').on('click', function() {
        window.location.href = adminAjaxUrl + '?action=pmpro_woo_sync_export_system_info&nonce=' + encodeURIComponent(nonce);
    });
});
</script>
