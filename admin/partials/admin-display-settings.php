<?php
/**
 * Plantilla para la página de ajustes del plugin PMPRO-Woo-Sync.
 * Variables disponibles: $this (PMPro_Woo_Sync_Admin instance)
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Configuraciones de PMPro-Woo-Sync', 'pmpro-woo-sync' ); ?></h1>

    <div id="pmpro-woo-sync-admin-notices"></div>

    <div class="pmpro-woo-sync-admin-header">
        <div class="pmpro-woo-sync-status-indicators">
            <?php 
            // Renderizar indicadores de estado directamente
            $sync_enabled = $this->settings->get_setting( 'enable_sync', 'yes' ) === 'yes';
            $debug_mode = $this->settings->get_setting( 'debug_mode', 'no' ) === 'yes';
            $pagbank_configured = ! empty( $this->settings->get_setting( 'pagbank_api_settings.api_key' ) );
            ?>
            <div class="pmpro-woo-sync-indicators">
                <div class="indicator <?php echo $sync_enabled ? 'active' : 'inactive'; ?>">
                    <span class="dashicons <?php echo $sync_enabled ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
                    <?php esc_html_e( 'Sincronización', 'pmpro-woo-sync' ); ?>
                </div>
                
                <div class="indicator <?php echo $debug_mode ? 'warning' : 'inactive'; ?>">
                    <span class="dashicons <?php echo $debug_mode ? 'dashicons-warning' : 'dashicons-dismiss'; ?>"></span>
                    <?php esc_html_e( 'Modo Debug', 'pmpro-woo-sync' ); ?>
                </div>
                
                <div class="indicator <?php echo $pagbank_configured ? 'active' : 'inactive'; ?>">
                    <span class="dashicons <?php echo $pagbank_configured ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
                    <?php esc_html_e( 'PagBank API', 'pmpro-woo-sync' ); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="pmpro-woo-sync-admin-content">
        <div class="pmpro-woo-sync-main-content">
            <form method="post" action="options.php" id="pmpro-woo-sync-settings-form">
                <?php
                settings_fields( PMPro_Woo_Sync_Settings::SETTINGS_GROUP_NAME );
                do_settings_sections( 'pmpro-woo-sync' );
                ?>
                
                <div class="pmpro-woo-sync-form-actions">
                    <?php submit_button( __( 'Guardar Configuraciones', 'pmpro-woo-sync' ), 'primary', 'submit', false ); ?>
                    
                    <button type="button" id="test-pagbank-connection" class="button button-secondary">
                        <span class="dashicons dashicons-admin-plugins"></span>
                        <?php esc_html_e( 'Probar Conexión PagBank', 'pmpro-woo-sync' ); ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="pmpro-woo-sync-sidebar">
            <div class="pmpro-woo-sync-widget">
                <h3><?php esc_html_e( 'Acciones Rápidas', 'pmpro-woo-sync' ); ?></h3>
                
                <div class="pmpro-woo-sync-quick-actions">
                    <form method="post" style="margin-bottom: 10px;">
                        <?php wp_nonce_field( 'pmpro_woo_sync_settings_action' ); ?>
                        <input type="hidden" name="pmpro_woo_sync_action" value="test_pagbank_connection">
                        <button type="submit" class="button button-secondary button-large">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php esc_html_e( 'Verificar Configuración', 'pmpro-woo-sync' ); ?>
                        </button>
                    </form>

                    <form method="post" onsubmit="return confirm('<?php esc_attr_e( '¿Estás seguro? Esta acción restaurará todas las configuraciones a sus valores por defecto.', 'pmpro-woo-sync' ); ?>');">
                        <?php wp_nonce_field( 'pmpro_woo_sync_settings_action' ); ?>
                        <input type="hidden" name="pmpro_woo_sync_action" value="reset_settings">
                        <button type="submit" class="button button-secondary button-large">
                            <span class="dashicons dashicons-undo"></span>
                            <?php esc_html_e( 'Restaurar Configuraciones', 'pmpro-woo-sync' ); ?>
                        </button>
                    </form>
                </div>
            </div>

            <div class="pmpro-woo-sync-widget">
                <h3><?php esc_html_e( 'Información del Sistema', 'pmpro-woo-sync' ); ?></h3>
                
                <div class="pmpro-woo-sync-system-info">
                    <p><strong><?php esc_html_e( 'Versión del Plugin:', 'pmpro-woo-sync' ); ?></strong> <?php echo esc_html( PMPRO_WOO_SYNC_VERSION ); ?></p>
                    <p><strong><?php esc_html_e( 'WordPress:', 'pmpro-woo-sync' ); ?></strong> <?php echo esc_html( get_bloginfo( 'version' ) ); ?></p>
                    <p><strong><?php esc_html_e( 'PHP:', 'pmpro-woo-sync' ); ?></strong> <?php echo esc_html( PHP_VERSION ); ?></p>
                    
                    <div class="pmpro-woo-sync-dependencies">
                        <h4><?php esc_html_e( 'Dependencias:', 'pmpro-woo-sync' ); ?></h4>
                        <ul>
                            <li>
                                <span class="dashicons <?php echo function_exists( 'pmpro_getLevel' ) ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
                                <?php esc_html_e( 'Paid Memberships Pro', 'pmpro-woo-sync' ); ?>
                            </li>
                            <li>
                                <span class="dashicons <?php echo class_exists( 'WooCommerce' ) ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
                                <?php esc_html_e( 'WooCommerce', 'pmpro-woo-sync' ); ?>
                            </li>
                            <li>
                                <span class="dashicons <?php echo class_exists( 'WC_Subscriptions' ) ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
                                <?php esc_html_e( 'WooCommerce Subscriptions', 'pmpro-woo-sync' ); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="pmpro-woo-sync-widget">
                <h3><?php esc_html_e( 'Estado de Conexiones', 'pmpro-woo-sync' ); ?></h3>
                
                <div class="pmpro-woo-sync-connection-status">
                    <?php 
                    // Estado de PagBank
                    $pagbank_api_key = $this->settings->get_setting( 'pagbank_api_settings.api_key' );
                    $pagbank_mode = $this->settings->get_setting( 'pagbank_api_settings.mode', 'live' );
                    ?>
                    
                    <div class="connection-item">
                        <h4><?php esc_html_e( 'PagBank API:', 'pmpro-woo-sync' ); ?></h4>
                        <?php if ( ! empty( $pagbank_api_key ) ) : ?>
                            <span class="status-badge active">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e( 'Configurado', 'pmpro-woo-sync' ); ?>
                            </span>
                            <p class="connection-details">
                                <strong><?php esc_html_e( 'Modo:', 'pmpro-woo-sync' ); ?></strong> 
                                <?php echo esc_html( ucfirst( $pagbank_mode ) ); ?>
                                <?php if ( $pagbank_mode === 'sandbox' ) : ?>
                                    <span class="mode-indicator sandbox"><?php esc_html_e( '(Pruebas)', 'pmpro-woo-sync' ); ?></span>
                                <?php endif; ?>
                            </p>
                        <?php else : ?>
                            <span class="status-badge inactive">
                                <span class="dashicons dashicons-dismiss"></span>
                                <?php esc_html_e( 'No Configurado', 'pmpro-woo-sync' ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Validación del formulario
    $('#pmpro-woo-sync-settings-form').on('submit', function(e) {
        var apiKey = $('input[name*="[api_key]"]').val();
        
        if (apiKey && apiKey.length < 20) {
            if (!confirm('<?php echo esc_js( __( 'La API Key parece ser muy corta. ¿Deseas continuar?', 'pmpro-woo-sync' ) ); ?>')) {
                e.preventDefault();
                return false;
            }
        }
    });

    // Test de conexión PagBank
    $('#test-pagbank-connection').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.html();
        
        $button.html('<span class="dashicons dashicons-update spin"></span> <?php echo esc_js( __( 'Probando...', 'pmpro-woo-sync' ) ); ?>');
        $button.prop('disabled', true);
        
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
                    alert('<?php echo esc_js( __( 'Conexión exitosa con PagBank', 'pmpro-woo-sync' ) ); ?>');
                } else {
                    alert('<?php echo esc_js( __( 'Error de conexión:', 'pmpro-woo-sync' ) ); ?> ' + (response.data.message || '<?php echo esc_js( __( 'Error desconocido', 'pmpro-woo-sync' ) ); ?>'));
                }
            },
            error: function() {
                alert('<?php echo esc_js( __( 'Error de conexión AJAX', 'pmpro-woo-sync' ) ); ?>');
            },
            complete: function() {
                $button.html(originalText);
                $button.prop('disabled', false);
            }
        });
    });
});
</script>

<style>
/* Estilos adicionales para la página de configuraciones */
.pmpro-woo-sync-connection-status .connection-item {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f1;
}

.pmpro-woo-sync-connection-status .connection-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.pmpro-woo-sync-connection-status h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #1d2327;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.active {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.status-badge.inactive {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.connection-details {
    margin: 8px 0 0 0;
    font-size: 13px;
    color: #646970;
}

.mode-indicator.sandbox {
    color: #856404;
    font-weight: 600;
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
