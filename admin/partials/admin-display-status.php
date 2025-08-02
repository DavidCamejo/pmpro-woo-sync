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
$system_info      = $this->get_system_information();
$dependency_status = $this->check_dependencies_status();
$sync_stats       = $this->get_sync_statistics();
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
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e( 'Paid Memberships Pro', 'pmpro-woo-sync' ); ?></td>
                    <td>
                        <span class="status-indicator <?php echo $dependency_status['pmpro_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $dependency_status['pmpro_active'] ? __( 'Activo', 'pmpro-woo-sync' ) : __( 'Inactivo', 'pmpro-woo-sync' ); ?>
                        </span>
                    </td>
                    <td><?php echo $dependency_status['pmpro_active'] ? esc_html( $dependency_status['pmpro_version'] ) : '—'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'WooCommerce', 'pmpro-woo-sync' ); ?></td>
                    <td>
                        <span class="status-indicator <?php echo $dependency_status['woocommerce_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $dependency_status['woocommerce_active'] ? __( 'Activo', 'pmpro-woo-sync' ) : __( 'Inactivo', 'pmpro-woo-sync' ); ?>
                        </span>
                    </td>
                    <td><?php echo $dependency_status['woocommerce_active'] ? esc_html( $dependency_status['woocommerce_version'] ) : '—'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'WooCommerce Subscriptions', 'pmpro-woo-sync' ); ?></td>
                    <td>
                        <span class="status-indicator <?php echo $dependency_status['wc_subscriptions_active'] ? 'active' : 'warning'; ?>">
                            <?php echo $dependency_status['wc_subscriptions_active'] ? __( 'Activo', 'pmpro-woo-sync' ) : __( 'Recomendado', 'pmpro-woo-sync' ); ?>
                        </span>
                    </td>
                    <td><?php echo $dependency_status['wc_subscriptions_active'] ? esc_html( $dependency_status['wc_subscriptions_version'] ) : '—'; ?></td>
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
                    <td><?php esc_html_e( 'Versión de WordPress', 'pmpro-woo-sync' ); ?></td>
                    <td><?php echo esc_html( $system_info['wordpress_version'] ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Versión de PHP', 'pmpro-woo-sync' ); ?></td>
                    <td><?php echo esc_html( $system_info['php_version'] ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Versión de MySQL', 'pmpro-woo-sync' ); ?></td>
                    <td><?php echo esc_html( $system_info['mysql_version'] ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Versión del Plugin', 'pmpro-woo-sync' ); ?></td>
                    <td><?php echo esc_html( $system_info['plugin_version'] ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Límite de Memoria', 'pmpro-woo-sync' ); ?></td>
                    <td><?php echo esc_html( $system_info['memory_limit'] ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Tiempo Máximo de Ejecución', 'pmpro-woo-sync' ); ?></td>
                    <td><?php echo esc_html( $system_info['max_execution_time'] ); ?> <?php esc_html_e( 'segundos', 'pmpro-woo-sync' ); ?></td>
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
                <span class="stat-number"><?php echo esc_html( $sync_stats['active_subscriptions'] ); ?></span>
                <span class="stat-label"><?php esc_html_e( 'Suscripciones Activas', 'pmpro-woo-sync' ); ?></span>
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
</div>

<style>
.pmpro-woo-sync-status-section {
    margin-bottom: 30px;
}
.pmpro-woo-sync-status-section h2 {
    margin-top: 0;
}
.status-indicator.active {
    color: #155724;
    background: #d4edda;
    padding: 3px 10px;
    border-radius: 3px;
    font-weight: bold;
}
.status-indicator.inactive {
    color: #721c24;
    background: #f8d7da;
    padding: 3px 10px;
    border-radius: 3px;
    font-weight: bold;
}
.status-indicator.warning {
    color: #856404;
    background: #fff3cd;
    padding: 3px 10px;
    border-radius: 3px;
    font-weight: bold;
}
.stats-grid {
    display: flex;
    gap: 30px;
    margin-top: 20px;
    flex-wrap: wrap;
}
.stat-item {
    background: #f9f9f9;
    border-radius: 5px;
    padding: 20px 30px;
    text-align: center;
    min-width: 180px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.stat-number {
    font-size: 2.2em;
    font-weight: bold;
    display: block;
    margin-bottom: 8px;
}
.stat-label {
    color: #666;
    font-size: 1em;
}
</style>