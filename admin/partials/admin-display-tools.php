<?php
/**
 * Plantilla para mostrar la página de herramientas del plugin PMPro-Woo-Sync
 *
 * @package PMPro_Woo_Sync
 * @since 2.0.0
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Obtener configuraciones actuales
$settings = $this->settings->get_settings();
$sync_stats = $this->get_sync_statistics();
?>

<div class="wrap pmpro-woo-sync-admin">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <!-- Resumen del estado actual -->
    <div class="pmpro-woo-sync-tools-summary">
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-number"><?php echo esc_html( $sync_stats['total_synced_users'] ); ?></span>
                <span class="summary-label"><?php esc_html_e( 'Usuarios Sincronizados', 'pmpro-woo-sync' ); ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-number"><?php echo esc_html( $sync_stats['active_subscriptions'] ); ?></span>
                <span class="summary-label"><?php esc_html_e( 'Suscripciones Activas', 'pmpro-woo-sync' ); ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-number"><?php echo esc_html( $sync_stats['sync_errors'] ); ?></span>
                <span class="summary-label"><?php esc_html_e( 'Errores Recientes', 'pmpro-woo-sync' ); ?></span>
            </div>
        </div>
    </div>

    <div class="pmpro-woo-sync-tools-grid">
        <!-- Herramientas de Sincronización -->
        <div class="tool-section">
            <h2><?php esc_html_e( 'Herramientas de Sincronización', 'pmpro-woo-sync' ); ?></h2>
            
            <div class="tool-item">
                <div class="tool-header">
                    <h3><?php esc_html_e( 'Sincronización Manual de Usuario', 'pmpro-woo-sync' ); ?></h3>
                    <span class="tool-badge safe"><?php esc_html_e( 'Seguro', 'pmpro-woo-sync' ); ?></span>
                </div>
                <p class="tool-description">
                    <?php esc_html_e( 'Sincroniza manualmente un usuario específico entre PMPro y WooCommerce.', 'pmpro-woo-sync' ); ?>
                </p>
                <form method="post" action="" class="tool-form">
                    <?php wp_nonce_field( 'pmpro_woo_sync_tools_action', '_wpnonce' ); ?>
                    <input type="hidden" name="pmpro_woo_sync_tools_action" value="sync_user" />
                    <div class="form-row">
                        <label for="user_id"><?php esc_html_e( 'ID del Usuario:', 'pmpro-woo-sync' ); ?></label>
                        <input type="number" name="user_id" id="user_id" placeholder="<?php esc_attr_e( 'Ej: 123', 'pmpro-woo-sync' ); ?>" required min="1" />
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e( 'Sincronizar Usuario', 'pmpro-woo-sync' ); ?>
                        </button>
                    </div>
                </form>
            </div>

            <div class="tool-item">
                <div class="tool-header">
                    <h3><?php esc_html_e( 'Sincronización Masiva', 'pmpro-woo-sync' ); ?></h3>
                    <span class="tool-badge warning"><?php esc_html_e( 'Cuidado', 'pmpro-woo-sync' ); ?></span>
                </div>
                <p class="tool-description">
                    <?php esc_html_e( 'Ejecuta una sincronización completa entre PMPro y WooCommerce para todos los usuarios activos. Esta operación puede tomar varios minutos.', 'pmpro-woo-sync' ); ?>
                </p>
                <form method="post" action="" class="tool-form">
                    <?php wp_nonce_field( 'pmpro_woo_sync_tools_action', '_wpnonce' ); ?>
                    <input type="hidden" name="pmpro_woo_sync_tools_action" value="sync_all_memberships" />
                    <div class="form-row">
                        <label>
                            <input type="checkbox" name="confirm_sync_all" required />
                            <?php esc_html_e( 'Confirmo que quiero sincronizar todas las membresías', 'pmpro-woo-sync' ); ?>
                        </label>
                        <button type="submit" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( '¿Está seguro? Esta operación puede tomar varios minutos y afectar el rendimiento del sitio.', 'pmpro-woo-sync' ); ?>')">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php esc_html_e( 'Sincronizar Todo', 'pmpro-woo-sync' ); ?>
                        </button>
                    </div>
                </form>
            </div>

            <div class="tool-item">
                <div class="tool-header">
                    <h3><?php esc_html_e( 'Reparar Enlaces de Suscripciones', 'pmpro-woo-sync' ); ?></h3>
                    <span class="tool-badge safe"><?php esc_html_e( 'Seguro', 'pmpro-woo-sync' ); ?></span>
                </div>
                <p class="tool-description">
                    <?php esc_html_e( 'Repara las vinculaciones rotas entre suscripciones de WooCommerce y niveles de membresía de PMPro.', 'pmpro-woo-sync' ); ?>
                </p>
                <form method="post" action="" class="tool-form">
                    <?php wp_nonce_field( 'pmpro_woo_sync_tools_action', '_wpnonce' ); ?>
                    <input type="hidden" name="pmpro_woo_sync_tools_action" value="repair_subscription_links" />
                    <div class="form-row">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-admin-links"></span>
                            <?php esc_html_e( 'Reparar Enlaces', 'pmpro-woo-sync' ); ?>
                        </button>
                    </div>
                </form>
            </div>

            <div class="tool-item">
                <div class="tool-header">
                    <h3><?php esc_html_e( 'Verificar Estados de Suscripciones', 'pmpro-woo-sync' ); ?></h3>
                    <span class="tool-badge safe"><?php esc_html_e( 'Seguro', 'pmpro-woo-sync' ); ?></span>
                </div>
                <p class="tool-description">
                    <?php esc_html_e( 'Verifica y actualiza el estado de todas las suscripciones sincronizadas entre PMPro y WooCommerce.', 'pmpro-woo-sync' ); ?>
                </p>
                <form method="post" action="" class="tool-form">
                    <?php wp_nonce_field( 'pmpro_woo_sync_tools_action', '_wpnonce' ); ?>
                    <input type="hidden" name="pmpro_woo_sync_tools_action" value="verify_subscription_states" />
                    <div class="form-row">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e( 'Verificar Estados', 'pmpro-woo-sync' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Herramientas de Mantenimiento -->
        <div class="tool-section">
            <h2><?php esc_html_e( 'Herramientas de Mantenimiento', 'pmpro-woo-sync' ); ?></h2>
            
            <div class="tool-item">
                <div class="tool-header">
                    <h3><?php esc_html_e( 'Limpiar Metadatos Huérfanos', 'pmpro-woo-sync' ); ?></h3>
                    <span class="tool-badge safe"><?php esc_html_e( 'Seguro', 'pmpro-woo-sync' ); ?></span>
                </div>
                <p class="tool-description">
                    <?php esc_html_e( 'Elimina metadatos del plugin que ya no están asociados a usuarios o pedidos válidos.', 'pmpro-woo-sync' ); ?>
                </p>
                <form method="post" action="" class="tool-form">
                    <?php wp_nonce_field( 'pmpro_woo_sync_tools_action', '_wpnonce' ); ?>
                    <input type="hidden" name="pmpro_woo_sync_tools_action" value="cleanup_orphaned_metadata" />
                    <div class="form-row">
                        <button type="submit" class="button button-secondary">
                            <span class="dashicons dashicons-trash"></span>
                            <?php esc_html_e( 'Limpiar Metadatos', 'pmpro-woo-sync' ); ?>
                        </button>
                    </div>
                </form>
            </div>

            <div class="tool-item">
                <div class="tool-header">
                    <h3><?php esc_html_e( 'Limpiar Logs Antiguos', 'pmpro-woo-sync' ); ?></h3>
                    <span class="tool-badge safe"><?php esc_html_e( 'Seguro', 'pmpro-woo-sync' ); ?></span>
                </div>
                <p class="tool-description">
                    <?php esc_html_e( 'Elimina logs más antiguos que el período de retención configurado.', 'pmpro-woo-sync' ); ?>
                </p>
                <form method="post" action="" class="tool-form">
                    <?php wp_nonce_field( 'pmpro_woo_sync_tools_action', '_wpnonce' ); ?>
                    <input type="hidden" name="pmpro_woo_sync_tools_action" value="cleanup_old_logs" />
                    <div class="form-row">
                        <button type="submit" class="button button-secondary">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php esc_html_e( 'Limpiar Logs', 'pmpro-woo-sync' ); ?>
                        </button>
                    </div>
                </form>
            </div>

            <div class="tool-item">
                <div class="tool-header">
                    <h3><?php esc_html_e( 'Reiniciar Configuraciones', 'pmpro-woo-sync' ); ?></h3>
                    <span class="tool-badge danger"><?php esc_html_e( 'Peligroso', 'pmpro-woo-sync' ); ?></span>
                </div>
                <p class="tool-description">
                    <?php esc_html_e( 'Restaura todas las configuraciones del plugin a sus valores predeterminados. Esta acción no se puede deshacer.', 'pmpro-woo-sync' ); ?>
                </p>
                <form method="post" action="" class="tool-form">
                    <?php wp_nonce_field( 'pmpro_woo_sync_tools_action', '_wpnonce' ); ?>
                    <input type="hidden" name="pmpro_woo_sync_tools_action" value="reset_settings" />
                    <div class="form-row">
                        <label>
                            <input type="checkbox" name="confirm_reset" required />
                            <?php esc_html_e( 'Confirmo que quiero reiniciar todas las configuraciones', 'pmpro-woo-sync' ); ?>
                        </label>
                        <button type="submit" class="button button-delete" onclick="return confirm('<?php esc_attr_e( '¿Está seguro? Se perderán todas las configuraciones personalizadas y esta acción no se puede deshacer.', 'pmpro-woo-sync' ); ?>')">
                            <span class="dashicons dashicons-warning"></span>
                            <?php esc_html_e( 'Reiniciar Todo', 'pmpro-woo-sync' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Herramientas de Debug -->
        <div class="tool-section">
            <h2><?php esc_html_e( 'Herramientas de Debug', 'pmpro-woo-sync' ); ?></h2>
            
            <div class="tool-item">
                <div class="tool-header">
                    <h3><?php esc_html_e( 'Debug de Usuario Específico', 'pmpro-woo-sync' ); ?></h3>
                    <span class="tool-badge safe"><?php esc_html_e( 'Seguro', 'pmpro-woo-sync' ); ?></span>
                </div>
                <p class="tool-description">
                    <?php esc_html_e( 'Obtiene información detallada de debug para un usuario específico, incluyendo su estado de membresía y suscripciones.', 'pmpro-woo-sync' ); ?>
                </p>
                <form method="post" action="" class="tool-form">
                    <?php wp_nonce_field( 'pmpro_woo_sync_tools_action', '_wpnonce' ); ?>
                    <input type="hidden" name="pmpro_woo_sync_tools_action" value="debug_user" />
                    <div class="form-row">
                        <label for="debug_user_id"><?php esc_html_e( 'ID de Usuario:', 'pmpro-woo-sync' ); ?></label>
                        <input type="number" name="debug_user_id" id="debug_user_id" placeholder="<?php esc_attr_e( 'Ej: 123', 'pmpro-woo-sync' ); ?>" required min="1" />
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-search"></span>
                            <?php esc_html_e( 'Generar Debug', 'pmpro-woo-sync' ); ?>
                        </button>
                    </div>
                </form>
            </div>

            <div class="tool-item">
                <div class="tool-header">
                    <h3><?php esc_html_e( 'Exportar Información del Sistema', 'pmpro-woo-sync' ); ?></h3>
                    <span class="tool-badge safe"><?php esc_html_e( 'Seguro', 'pmpro-woo-sync' ); ?></span>
                </div>
                <p class="tool-description">
                    <?php esc_html_e( 'Genera un archivo con información completa del sistema para soporte técnico.', 'pmpro-woo-sync' ); ?>
                </p>
                <form method="post" action="" class="tool-form">
                    <?php wp_nonce_field( 'pmpro_woo_sync_tools_action', '_wpnonce' ); ?>
                    <input type="hidden" name="pmpro_woo_sync_tools_action" value="export_system_info" />
                    <div class="form-row">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e( 'Exportar Información', 'pmpro-woo-sync' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Resultados de Debug -->
    <?php if ( isset( $_POST['debug_user_id'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'pmpro_woo_sync_tools_action' ) ) : ?>
        <div class="pmpro-woo-sync-debug-results">
            <h2><?php esc_html_e( 'Resultados del Debug:', 'pmpro-woo-sync' ); ?></h2>
            <div class="debug-output">
                <pre><?php 
                    $user_id = intval( $_POST['debug_user_id'] );
                    $debug_info = $this->get_user_debug_info( $user_id );
                    echo esc_html( wp_json_encode( $debug_info, JSON_PRETTY_PRINT ) );
                ?></pre>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.pmpro-woo-sync-tools-summary {
    margin-bottom: 30px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 5px;
}

.summary-grid {
    display: flex;
    gap: 30px;
    justify-content: center;
    flex-wrap: wrap;
}

.summary-item {
    text-align: center;
    min-width: 150px;
}

.summary-number {
    font-size: 2.5em;
    font-weight: bold;
    display: block;
    color: #0073aa;
}

.summary-label {
    color: #666;
    font-size: 0.9em;
}

.pmpro-woo-sync-tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 30px;
}

.tool-section {
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
}

.tool-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #0073aa;
}

.tool-item {
    margin-bottom: 25px;
    padding: 15px;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    background: #fafafa;
}

.tool-item:last-child {
    margin-bottom: 0;
}

.tool-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.tool-header h3 {
    margin: 0;
    font-size: 1.1em;
}

.tool-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.tool-badge.safe {
    background: #d4edda;
    color: #155724;
}

.tool-badge.warning {
    background: #fff3cd;
    color: #856404;
}

.tool-badge.danger {
    background: #f8d7da;
    color: #721c24;
}

.tool-description {
    margin-bottom: 15px;
    color: #666;
    font-size: 0.95em;
    line-height: 1.4;
}

.tool-form .form-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.tool-form input[type="number"] {
    width: 100px;
}

.tool-form label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9em;
}

.button .dashicons {
    margin-right: 5px;
}

.button-delete {
    background: #dc3545;
    border-color: #dc3545;
    color: white;
}

.button-delete:hover {
    background: #c82333;
    border-color: #bd2130;
}

.pmpro-woo-sync-debug-results {
    margin-top: 30px;
    padding: 20px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.debug-output {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 15px;
    max-height: 400px;
    overflow-y: auto;
}

.debug-output pre {
    margin: 0;
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
    line-height: 1.4;
    white-space: pre-wrap;
    word-wrap: break-word;
}

@media (max-width: 768px) {
    .pmpro-woo-sync-tools-grid {
        grid-template-columns: 1fr;
    }
    
    .summary-grid {
        flex-direction: column;
        align-items: center;
    }
    
    .tool-form .form-row {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
