<?php
/**
 * Plantilla para la página de herramientas del plugin.
 * Variables disponibles: $this (PMPro_Woo_Sync_Admin instance)
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Herramientas - PMPro-Woo-Sync', 'pmpro-woo-sync' ); ?></h1>

    <div id="pmpro-woo-sync-admin-notices"></div>

    <div class="pmpro-woo-sync-tools-container">
        
        <!-- Herramientas de Sincronización -->
        <div class="pmpro-woo-sync-tool-section">
            <h2><?php esc_html_e( 'Herramientas de Sincronización', 'pmpro-woo-sync' ); ?></h2>
            
            <div class="pmpro-woo-sync-tool-grid">
                <div class="tool-card">
                    <h3><?php esc_html_e( 'Sincronizar Todas las Membresías', 'pmpro-woo-sync' ); ?></h3>
                    <p><?php esc_html_e( 'Ejecuta una sincronización completa entre PMPro y WooCommerce para todos los usuarios activos.', 'pmpro-woo-sync' ); ?></p>
                    <form method="post">
                        <?php wp_nonce_field( 'pmpro_woo_sync_tools_action' ); ?>
                        <input type="hidden" name="pmpro_woo_sync_tools_action" value="sync_all_memberships">
                        <button type="submit" class="button button-primary pmpro-woo-sync-tool-button" 
                                data-action="sync_all_memberships"
                                data-confirm="<?php esc_attr_e( '¿Estás seguro? Esta operación puede tomar varios minutos.', 'pmpro-woo-sync' ); ?>">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e( 'Sincronizar Todo', 'pmpro-woo-sync' ); ?>
                        </button>
                    </form>
                </div>

                <div class="tool-card">
                    <h3><?php esc_html_e( 'Reparar Enlaces de Suscripciones', 'pmpro-woo-sync' ); ?></h3>
                    <p><?php esc_html_e( 'Repara las vinculaciones entre suscripciones de WooCommerce y niveles de membresía de PMPro.', 'pmpro-woo-sync' ); ?></p>
                    <form method="post">
                        <?php wp_nonce_field( 'pmpro_woo_sync_tools_action' ); ?>
                        <input type="hidden" name="pmpro_woo_sync_tools_action" value="repair_subscription_links">
                        <button type="submit" class="button button-secondary pmpro-woo-sync-tool-button" 
                                data-action="repair_subscription_links">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php esc_html_e( 'Reparar Enlaces', 'pmpro-woo-sync' ); ?>
                        </button>
                    </form>
                </div>

                <div class="tool-card">
                    <h3><?php esc_html_e( 'Verificar Estados de Gateway', 'pmpro-woo-sync' ); ?></h3>
                    <p><?php esc_html_e( 'Verifica el estado de todas las suscripciones en los gateways externos como PagBank.', 'pmpro-woo-sync' ); ?></p>
                    <form method="post">
                        <?php wp_nonce_field( 'pmpro_woo_sync_tools_action' ); ?>
                        <input type="hidden" name="pmpro_woo_sync_tools_action" value="verify_gateway_statuses">
                        <button type="submit" class="button button-secondary pmpro-woo-sync-tool-button" 
                                data-action="verify_gateway_statuses">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e( 'Verificar Estados', 'pmpro-woo-sync' ); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Herramientas de Limpieza -->
        <div class="pmpro-woo-sync-tool-section">
            <h2><?php esc_html_e( 'Herramientas de Limpieza', 'pmpro-woo-sync' ); ?></h2>
            
            <div class="pmpro-woo-sync-tool-grid">
                <div class="tool-card warning">
                    <h3><?php esc_html_e( 'Limpiar Metadatos Huérfanos', 'pmpro-woo-sync' ); ?></h3>
                    <p><?php esc_html_e( 'Elimina metadatos del plugin que ya no están asociados a usuarios o pedidos válidos.', 'pmpro-woo-sync' ); ?></p>
                    <form method="post">
                        <?php wp_nonce_field( 'pmpro_woo_sync_tools_action' ); ?>
                        <input type="hidden" name="pmpro_woo_sync_tools_action" value="clean_orphaned_metadata">
                        <button type="submit" class="button button-secondary pmpro-woo-sync-tool-button" 
                                data-action="clean_orphaned_metadata"
                                data-confirm="<?php esc_attr_e( '¿Estás seguro? Esta acción no se puede deshacer.', 'pmpro-woo-sync' ); ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <?php esc_html_e( 'Limpiar Metadatos', 'pmpro-woo-sync' ); ?>
                        </button>
                    </form>
                </div>

                <div class="tool-card warning">
                    <h3><?php esc_html_e( 'Reiniciar Configuraciones', 'pmpro-woo-sync' ); ?></h3>
                    <p><?php esc_html_e( 'Restaura todas las configuraciones del plugin a sus valores predeterminados.', 'pmpro-woo-sync' ); ?></p>
                    <form method="post">
                        <?php wp_nonce_field( 'pmpro_woo_sync_tools_action' ); ?>
                        <input type="hidden" name="pmpro_woo_sync_tools_action" value="reset_all_settings">
                        <button type="submit" class="button button-secondary pmpro-woo-sync-tool-button" 
                                data-action="reset_all_settings"
                                data-confirm="<?php esc_attr_e( '¿Estás seguro? Perderás todas las configuraciones personalizadas.', 'pmpro-woo-sync' ); ?>">
                            <span class="dashicons dashicons-undo"></span>
                            <?php esc_html_e( 'Reiniciar Todo', 'pmpro-woo-sync' ); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Herramientas de Debug -->
        <div class="pmpro-woo-sync-tool-section">
            <h2><?php esc_html_e( 'Herramientas de Debug', 'pmpro-woo-sync' ); ?></h2>
            
            <div class="pmpro-woo-sync-debug-tool">
                <h3><?php esc_html_e( 'Debug de Usuario Específico', 'pmpro-woo-sync' ); ?></h3>
                <p><?php esc_html_e( 'Obtiene información detallada de debug para un usuario específico.', 'pmpro-woo-sync' ); ?></p>
                
                <form method="post" id="debug-user-form">
                    <?php wp_nonce_field( 'pmpro_woo_sync_tools_action' ); ?>
                    <input type="hidden" name="pmpro_woo_sync_tools_action" value="debug_user">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="debug_user_id"><?php esc_html_e( 'ID de Usuario:', 'pmpro-woo-sync' ); ?></label>
                            </th>
                            <td>
                                <input type="number" name="debug_user_id" id="debug_user_id" min="1" required>
                                <p class="description"><?php esc_html_e( 'Ingresa el ID del usuario para obtener información de debug.', 'pmpro-woo-sync' ); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <button type="submit" class="button button-secondary">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e( 'Generar Debug', 'pmpro-woo-sync' ); ?>
                    </button>
                </form>

                <?php if ( isset( $_POST['debug_user_id'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'pmpro_woo_sync_tools_action' ) ) : ?>
                    <div class="debug-results">
                        <h4><?php esc_html_e( 'Resultados del Debug:', 'pmpro-woo-sync' ); ?></h4>
                        <pre><?php 
                            $user_id = intval( $_POST['debug_user_id'] );
                            $debug_info = function_exists( 'pmpro_woo_sync_debug_info' ) ? pmpro_woo_sync_debug_info( $user_id ) : array( 'error' => 'Función no disponible' );
                            echo esc_html( print_r( $debug_info, true ) );
                        ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.pmpro-woo-sync-tool-section {
    margin-bottom: 40px;
}

.pmpro-woo-sync-tool-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.tool-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.tool-card.warning {
    border-left: 4px solid #d63638;
}

.tool-card h3 {
    margin-top: 0;
    margin-bottom: 10px;
}

.tool-card p {
    color: #646970;
    margin-bottom: 15px;
}

.tool-card .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.pmpro-woo-sync-debug-tool {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
    border-radius: 4px;
    margin-top: 20px;
}

.debug-results {
    margin-top: 20px;
    padding: 15px;
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.debug-results pre {
    background: #fff;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
    overflow-x: auto;
    font-size: 12px;
    max-height: 400px;
    overflow-y: auto;
}
</style>
