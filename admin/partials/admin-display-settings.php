<?php
/**
 * Plantilla para mostrar la página de configuraciones del plugin PMPro-Woo-Sync
 *
 * @package PMPro_Woo_Sync
 * @since 2.0.0
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Obtener configuraciones actuales con validación
$settings = $this->settings->get_settings();
$system_status = method_exists( $this, 'get_system_status' ) ? $this->get_system_status() : array(
    'sync_enabled' => false,
    'dependencies_ok' => false,
    'debug_mode' => 0
);
?>

<div class="wrap pmpro-woo-sync-admin">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <!-- Indicadores de estado del sistema -->
    <div class="pmpro-woo-sync-status-bar">
        <div class="status-indicators">
            <div class="indicator <?php echo $system_status['sync_enabled'] ? 'active' : 'inactive'; ?>">
                <span class="dashicons <?php echo $system_status['sync_enabled'] ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
                <span class="label"><?php esc_html_e( 'Sincronización', 'pmpro-woo-sync' ); ?></span>
            </div>
            
            <div class="indicator <?php echo $system_status['dependencies_ok'] ? 'active' : 'inactive'; ?>">
                <span class="dashicons <?php echo $system_status['dependencies_ok'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                <span class="label"><?php esc_html_e( 'Dependencias', 'pmpro-woo-sync' ); ?></span>
            </div>
            
            <div class="indicator <?php echo $system_status['debug_mode'] ? 'warning' : 'inactive'; ?>">
                <span class="dashicons <?php echo $system_status['debug_mode'] ? 'dashicons-warning' : 'dashicons-dismiss'; ?>"></span>
                <span class="label"><?php esc_html_e( 'Modo Debug', 'pmpro-woo-sync' ); ?></span>
            </div>
        </div>
    </div>

    <!-- SOLO UN FORMULARIO - CORRECCIÓN PRINCIPAL -->
    <form method="post" action="" class="pmpro-woo-sync-settings-form">
        <?php wp_nonce_field( 'pmpro_woo_sync_save_settings', '_pmpro_woo_sync_nonce' ); ?>
        <input type="hidden" name="pmpro_woo_sync_action" value="save_settings" />
        
        <!-- Sección: Configuración General -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php esc_html_e( 'Configuración General', 'pmpro-woo-sync' ); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="enable_sync"><?php esc_html_e( 'Habilitar Sincronización', 'pmpro-woo-sync' ); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <span><?php esc_html_e( 'Habilitar Sincronización', 'pmpro-woo-sync' ); ?></span>
                                    </legend>
                                    <label for="enable_sync">
                                        <input name="pmpro_woo_sync_settings[enable_sync]" type="hidden" value="0" />
                                        <input name="pmpro_woo_sync_settings[enable_sync]" type="checkbox" id="enable_sync" value="1" <?php checked( $settings['enable_sync'] ?? false ); ?> />
                                        <?php esc_html_e( 'Activar la sincronización automática entre PMPro y WooCommerce', 'pmpro-woo-sync' ); ?>
                                    </label>
                                </fieldset>
                                <p class="description">
                                    <?php esc_html_e( 'Cuando está habilitado, las membresías de PMPro se sincronizarán automáticamente con los pagos de WooCommerce y PagBank.', 'pmpro-woo-sync' ); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="sync_direction"><?php esc_html_e( 'Dirección de Sincronización', 'pmpro-woo-sync' ); ?></label>
                            </th>
                            <td>
                                <select name="pmpro_woo_sync_settings[sync_direction]" id="sync_direction">
                                    <option value="bidirectional" <?php selected( $settings['sync_direction'] ?? 'bidirectional', 'bidirectional' ); ?>>
                                        <?php esc_html_e( 'Bidireccional (PMPro ↔ WooCommerce)', 'pmpro-woo-sync' ); ?>
                                    </option>
                                    <option value="pmpro_to_woo" <?php selected( $settings['sync_direction'] ?? 'bidirectional', 'pmpro_to_woo' ); ?>>
                                        <?php esc_html_e( 'PMPro → WooCommerce', 'pmpro-woo-sync' ); ?>
                                    </option>
                                    <option value="woo_to_pmpro" <?php selected( $settings['sync_direction'] ?? 'bidirectional', 'woo_to_pmpro' ); ?>>
                                        <?php esc_html_e( 'WooCommerce → PMPro', 'pmpro-woo-sync' ); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e( 'Selecciona en qué dirección debe ocurrir la sincronización de datos.', 'pmpro-woo-sync' ); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="record_payments_in_pmpro"><?php esc_html_e( 'Registrar Pagos en PMPro', 'pmpro-woo-sync' ); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <span><?php esc_html_e( 'Registrar Pagos en PMPro', 'pmpro-woo-sync' ); ?></span>
                                    </legend>
                                    <label for="record_payments_in_pmpro">
                                        <input name="pmpro_woo_sync_settings[record_payments_in_pmpro]" type="hidden" value="0" />
                                        <input name="pmpro_woo_sync_settings[record_payments_in_pmpro]" type="checkbox" id="record_payments_in_pmpro" value="1" <?php checked( $settings['record_payments_in_pmpro'] ?? true ); ?> />
                                        <?php esc_html_e( 'Registrar los pagos de WooCommerce en el historial de PMPro', 'pmpro-woo-sync' ); ?>
                                    </label>
                                </fieldset>
                                <p class="description">
                                    <?php esc_html_e( 'Mantiene un registro de todos los pagos en ambos sistemas para mejor trazabilidad.', 'pmpro-woo-sync' ); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sección: Configuración de Logs -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php esc_html_e( 'Configuración de Logs', 'pmpro-woo-sync' ); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="enable_logging"><?php esc_html_e( 'Habilitar Logs', 'pmpro-woo-sync' ); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <span><?php esc_html_e( 'Habilitar Logs', 'pmpro-woo-sync' ); ?></span>
                                    </legend>
                                    <label for="enable_logging">
                                        <input name="pmpro_woo_sync_settings[enable_logging]" type="hidden" value="0" />
                                        <input name="pmpro_woo_sync_settings[enable_logging]" type="checkbox" id="enable_logging" value="1" <?php checked( $settings['enable_logging'] ?? 1 ); ?> />
                                        <?php esc_html_e( 'Registrar actividades de sincronización en logs', 'pmpro-woo-sync' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="log_level"><?php esc_html_e( 'Nivel de Log', 'pmpro-woo-sync' ); ?></label>
                            </th>
                            <td>
                                <select name="pmpro_woo_sync_settings[log_level]" id="log_level">
                                    <option value="error" <?php selected( $settings['log_level'] ?? 'info', 'error' ); ?>>
                                        <?php esc_html_e( 'Solo errores', 'pmpro-woo-sync' ); ?>
                                    </option>
                                    <option value="warning" <?php selected( $settings['log_level'] ?? 'info', 'warning' ); ?>>
                                        <?php esc_html_e( 'Advertencias y errores', 'pmpro-woo-sync' ); ?>
                                    </option>
                                    <option value="info" <?php selected( $settings['log_level'] ?? 'info', 'info' ); ?>>
                                        <?php esc_html_e( 'Información, advertencias y errores', 'pmpro-woo-sync' ); ?>
                                    </option>
                                    <option value="debug" <?php selected( $settings['log_level'] ?? 'info', 'debug' ); ?>>
                                        <?php esc_html_e( 'Todos los niveles (debug)', 'pmpro-woo-sync' ); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e( 'Selecciona qué nivel de detalle quieres en los logs.', 'pmpro-woo-sync' ); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="log_retention_days"><?php esc_html_e( 'Retención de Logs', 'pmpro-woo-sync' ); ?></label>
                            </th>
                            <td>
                                <input name="pmpro_woo_sync_settings[log_retention_days]" type="number" id="log_retention_days" value="<?php echo esc_attr( $settings['log_retention_days'] ?? 30 ); ?>" min="1" max="365" class="small-text" />
                                <span><?php esc_html_e( 'días', 'pmpro-woo-sync' ); ?></span>
                                <p class="description">
                                    <?php esc_html_e( 'Los logs más antiguos que este período serán eliminados automáticamente.', 'pmpro-woo-sync' ); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sección: Configuración Avanzada -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php esc_html_e( 'Configuración Avanzada', 'pmpro-woo-sync' ); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="debug_mode"><?php esc_html_e( 'Modo Debug', 'pmpro-woo-sync' ); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <span><?php esc_html_e( 'Modo Debug', 'pmpro-woo-sync' ); ?></span>
                                    </legend>
                                    <label for="debug_mode">
                                        <input name="pmpro_woo_sync_settings[debug_mode]" type="hidden" value="0" />
                                        <input name="pmpro_woo_sync_settings[debug_mode]" type="checkbox" id="debug_mode" value="1" <?php checked( $settings['debug_mode'] ?? 0 ); ?> />
                                        <?php esc_html_e( 'Habilitar logging detallado para troubleshooting', 'pmpro-woo-sync' ); ?>
                                    </label>
                                </fieldset>
                                <p class="description">
                                    <?php esc_html_e( 'Solo activar para solucionar problemas. Puede generar muchos logs.', 'pmpro-woo-sync' ); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="sync_failed_orders"><?php esc_html_e( 'Sincronizar Pedidos Fallidos', 'pmpro-woo-sync' ); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <span><?php esc_html_e( 'Sincronizar Pedidos Fallidos', 'pmpro-woo-sync' ); ?></span>
                                    </legend>
                                    <label for="sync_failed_orders">
                                        <input name="pmpro_woo_sync_settings[sync_failed_orders]" type="hidden" value="0" />
                                        <input name="pmpro_woo_sync_settings[sync_failed_orders]" type="checkbox" id="sync_failed_orders" value="1" <?php checked( $settings['sync_failed_orders'] ?? 0 ); ?> />
                                        <?php esc_html_e( 'Cancelar membresías cuando los pedidos fallan', 'pmpro-woo-sync' ); ?>
                                    </label>
                                </fieldset>
                                <p class="description">
                                    <?php esc_html_e( 'Si está habilitado, las membresías se cancelarán cuando los pagos fallen.', 'pmpro-woo-sync' ); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="auto_link_products"><?php esc_html_e( 'Auto-vincular Productos', 'pmpro-woo-sync' ); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <span><?php esc_html_e( 'Auto-vincular Productos', 'pmpro-woo-sync' ); ?></span>
                                    </legend>
                                    <label for="auto_link_products">
                                        <input name="pmpro_woo_sync_settings[auto_link_products]" type="hidden" value="0" />
                                        <input name="pmpro_woo_sync_settings[auto_link_products]" type="checkbox" id="auto_link_products" value="1" <?php checked( $settings['auto_link_products'] ?? 1 ); ?> />
                                        <?php esc_html_e( 'Vincular automáticamente productos con niveles de membresía', 'pmpro-woo-sync' ); ?>
                                    </label>
                                </fieldset>
                                <p class="description">
                                    <?php esc_html_e( 'Busca automáticamente la vinculación entre productos WooCommerce y niveles PMPro.', 'pmpro-woo-sync' ); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mapeo de Niveles -->
        <?php
        $membership_levels = function_exists( 'pmpro_getAllLevels' ) ? pmpro_getAllLevels( true, true ) : array();
        $woo_products = $this->get_woocommerce_products();
        $level_mappings = $settings['level_mappings'] ?? array();
        
        if ( ! empty( $membership_levels ) ) :
        ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php esc_html_e( 'Mapeo de Niveles de Membresía', 'pmpro-woo-sync' ); ?></h2>
            </div>
            <div class="inside">
                <p class="description">
                    <?php esc_html_e( 'Configura cómo se relacionan los niveles de membresía de PMPro con los productos de WooCommerce.', 'pmpro-woo-sync' ); ?>
                </p>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Nivel PMPro', 'pmpro-woo-sync' ); ?></th>
                            <th><?php esc_html_e( 'Producto WooCommerce', 'pmpro-woo-sync' ); ?></th>
                            <th><?php esc_html_e( 'Estado', 'pmpro-woo-sync' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $membership_levels as $level ) : ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $level->name ); ?></strong>
                                <br>
                                <small class="description">ID: <?php echo esc_html( $level->id ); ?></small>
                            </td>
                            <td>
                                <select name="pmpro_woo_sync_settings[level_mappings][<?php echo esc_attr( $level->id ); ?>]">
                                    <option value=""><?php esc_html_e( '-- Seleccionar producto --', 'pmpro-woo-sync' ); ?></option>
                                    <?php foreach ( $woo_products as $product_id => $product_name ) : ?>
                                        <option value="<?php echo esc_attr( $product_id ); ?>" <?php selected( $level_mappings[ $level->id ] ?? '', $product_id ); ?>>
                                            <?php echo esc_html( $product_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <?php if ( ! empty( $level_mappings[ $level->id ] ) ) : ?>
                                    <span class="status-badge active"><?php esc_html_e( 'Mapeado', 'pmpro-woo-sync' ); ?></span>
                                <?php else : ?>
                                    <span class="status-badge inactive"><?php esc_html_e( 'Sin mapear', 'pmpro-woo-sync' ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php submit_button( __( 'Guardar Configuraciones', 'pmpro-woo-sync' ), 'primary', 'submit', true, array( 'id' => 'save-settings' ) ); ?>
    </form>

    <!-- Panel de acciones rápidas -->
    <div class="postbox">
        <div class="postbox-header">
            <h2 class="hndle"><?php esc_html_e( 'Acciones Rápidas', 'pmpro-woo-sync' ); ?></h2>
        </div>
        <div class="inside">
            <div class="quick-actions-grid">
                <div class="action-item">
                    <h4><?php esc_html_e( 'Probar Sincronización', 'pmpro-woo-sync' ); ?></h4>
                    <p><?php esc_html_e( 'Verifica que la sincronización esté funcionando correctamente.', 'pmpro-woo-sync' ); ?></p>
                    <button type="button" class="button" id="test-sync-connection">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php esc_html_e( 'Ejecutar Prueba', 'pmpro-woo-sync' ); ?>
                    </button>
                </div>
                
                <div class="action-item">
                    <h4><?php esc_html_e( 'Ver Logs', 'pmpro-woo-sync' ); ?></h4>
                    <p><?php esc_html_e( 'Revisa la actividad reciente de sincronización.', 'pmpro-woo-sync' ); ?></p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-woo-sync-logs' ) ); ?>" class="button">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php esc_html_e( 'Abrir Logs', 'pmpro-woo-sync' ); ?>
                    </a>
                </div>
                
                <div class="action-item">
                    <h4><?php esc_html_e( 'Estado del Sistema', 'pmpro-woo-sync' ); ?></h4>
                    <p><?php esc_html_e( 'Verifica el estado de las dependencias y configuración.', 'pmpro-woo-sync' ); ?></p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-woo-sync-status' ) ); ?>" class="button">
                        <span class="dashicons dashicons-dashboard"></span>
                        <?php esc_html_e( 'Ver Estado', 'pmpro-woo-sync' ); ?>
                    </a>
                </div>

                <div class="action-item">
                    <h4><?php esc_html_e( 'Verificar Debug', 'pmpro-woo-sync' ); ?></h4>
                    <p><?php esc_html_e( 'Verifica el estado del modo debug y logging.', 'pmpro-woo-sync' ); ?></p>
                    <button type="button" class="button" id="check-debug-status">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e( 'Verificar Debug', 'pmpro-woo-sync' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para resultados de pruebas -->
<div id="test-sync-modal" class="pmpro-woo-sync-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php esc_html_e( 'Resultado de la Prueba', 'pmpro-woo-sync' ); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="test-sync-results"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="button modal-close"><?php esc_html_e( 'Cerrar', 'pmpro-woo-sync' ); ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Variables globales escapadas correctamente
    var adminAjaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var nonce = <?php echo wp_json_encode( wp_create_nonce( 'pmpro_woo_sync_admin_nonce' ) ); ?>;
    
    // Manejar prueba de sincronización
    $('#test-sync-connection').on('click', function() {
        var $button = $(this);
        var originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + <?php echo wp_json_encode( __( 'Probando...', 'pmpro-woo-sync' ) ); ?>);
        
        $.ajax({
            url: adminAjaxUrl,
            type: 'POST',
            data: {
                action: 'pmpro_woo_sync_test_sync',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#test-sync-results').html('<div class="notice notice-success"><p>' + $('<div>').text(response.data.message).html() + '</p></div>');
                } else {
                    $('#test-sync-results').html('<div class="notice notice-error"><p>' + $('<div>').text(response.data.message).html() + '</p></div>');
                }
                $('#test-sync-modal').show();
            },
            error: function() {
                $('#test-sync-results').html('<div class="notice notice-error"><p>' + <?php echo wp_json_encode( __( 'Error al ejecutar la prueba.', 'pmpro-woo-sync' ) ); ?> + '</p></div>');
                $('#test-sync-modal').show();
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Manejar cierre de modal
    $('.modal-close').on('click', function() {
        $('#test-sync-modal').hide();
    });
    
    // Cerrar modal al hacer clic fuera
    $('#test-sync-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });

    // Verificar debug status
    $('#check-debug-status').on('click', function() {
        var $button = $(this);
        var originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + <?php echo wp_json_encode( __( 'Verificando...', 'pmpro-woo-sync' ) ); ?>);
        
        $.ajax({
            url: adminAjaxUrl,
            type: 'POST',
            data: {
                action: 'pmpro_woo_sync_debug_status',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    var info = response.data.debug_info;
                    var message = 'Estado del Debug:\n';
                    message += '• Debug habilitado: ' + (info.debug_enabled ? 'SÍ' : 'NO') + '\n';
                    message += '• Logging habilitado: ' + (info.logging_enabled ? 'SÍ' : 'NO') + '\n';
                    message += '• Nivel de log: ' + info.log_level + '\n';
                    message += '• Configuraciones: ' + JSON.stringify(info.settings_raw, null, 2);
                    
                    $('#test-sync-results').html('<div class="notice notice-info"><pre>' + $('<div>').text(message).html() + '</pre></div>');
                } else {
                    $('#test-sync-results').html('<div class="notice notice-error"><p>' + $('<div>').text(response.data.message).html() + '</p></div>');
                }
                $('#test-sync-modal').show();
            },
            error: function() {
                $('#test-sync-results').html('<div class="notice notice-error"><p>' + <?php echo wp_json_encode( __( 'Error al verificar debug.', 'pmpro-woo-sync' ) ); ?> + '</p></div>');
                $('#test-sync-modal').show();
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>

<style>
.pmpro-woo-sync-admin .status-indicators {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 5px;
}

.pmpro-woo-sync-admin .indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
}

.pmpro-woo-sync-admin .indicator.active {
    background: #d4edda;
    color: #155724;
}

.pmpro-woo-sync-admin .indicator.inactive {
    background: #f8d7da;
    color: #721c24;
}

.pmpro-woo-sync-admin .indicator.warning {
    background: #fff3cd;
    color: #856404;
}

.pmpro-woo-sync-admin .status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.pmpro-woo-sync-admin .status-badge.active {
    background: #d4edda;
    color: #155724;
}

.pmpro-woo-sync-admin .status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.pmpro-woo-sync-admin .quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.pmpro-woo-sync-admin .action-item {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    text-align: center;
}

.pmpro-woo-sync-admin .action-item h4 {
    margin-top: 0;
    margin-bottom: 10px;
}

.pmpro-woo-sync-admin .action-item p {
    margin-bottom: 15px;
    color: #666;
}

.pmpro-woo-sync-admin .action-item .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.pmpro-woo-sync-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pmpro-woo-sync-modal .modal-content {
    background: white;
    border-radius: 5px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.pmpro-woo-sync-modal .modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pmpro-woo-sync-modal .modal-header h3 {
    margin: 0;
}

.pmpro-woo-sync-modal .modal-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pmpro-woo-sync-modal .modal-body {
    padding: 20px;
}

.pmpro-woo-sync-modal .modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.dashicons.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
