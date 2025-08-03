<?php
/**
 * Plantilla para mostrar los logs del plugin PMPro-Woo-Sync
 *
 * @package PMPro_Woo_Sync
 * @since 2.0.0
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sanitizar y obtener parámetros de filtrado
$search_term = sanitize_text_field( $_GET['s'] ?? '' );
$level_filter = sanitize_text_field( $_GET['level'] ?? '' );
$current_page = max( 1, intval( $_GET['paged'] ?? 1 ) );
$per_page = 50;

// Obtener logs con filtros y paginación aplicados
$log_args = array(
    'search' => $search_term,
    'level' => $level_filter,
    'page' => $current_page,
    'per_page' => $per_page
);

$logs_data = $this->logger->get_logs( $log_args );
$logs_page = $logs_data['logs'] ?? array();
$total_logs = $logs_data['total'] ?? 0;
$total_pages = max( 1, ceil( $total_logs / $per_page ) );
$log_levels = $this->logger->get_log_levels();
?>

<div class="wrap pmpro-woo-sync-admin">
    <h1><?php esc_html_e( 'Logs de Sincronización', 'pmpro-woo-sync' ); ?></h1>

    <p class="description">
        <?php esc_html_e( 'Aquí puedes revisar la actividad reciente de sincronización entre PMPro y WooCommerce. Usa los filtros para buscar por nivel o palabra clave.', 'pmpro-woo-sync' ); ?>
    </p>

    <form method="get" class="pmpro-woo-sync-logs-filter">
        <input type="hidden" name="page" value="pmpro-woo-sync-logs" />
        <input type="text" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Buscar en logs...', 'pmpro-woo-sync' ); ?>" />
        <select name="level">
            <option value=""><?php esc_html_e( 'Todos los niveles', 'pmpro-woo-sync' ); ?></option>
            <?php foreach ( $log_levels as $level_key => $level_label ) : ?>
                <option value="<?php echo esc_attr( $level_key ); ?>" <?php selected( $level_filter, $level_key ); ?>>
                    <?php echo esc_html( $level_label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="button"><?php esc_html_e( 'Filtrar', 'pmpro-woo-sync' ); ?></button>
        <?php if ( $search_term || $level_filter ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-woo-sync-logs' ) ); ?>" class="button">
                <?php esc_html_e( 'Limpiar Filtros', 'pmpro-woo-sync' ); ?>
            </a>
        <?php endif; ?>
    </form>

    <table class="wp-list-table widefat fixed striped pmpro-woo-sync-logs-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Fecha', 'pmpro-woo-sync' ); ?></th>
                <th><?php esc_html_e( 'Nivel', 'pmpro-woo-sync' ); ?></th>
                <th><?php esc_html_e( 'Mensaje', 'pmpro-woo-sync' ); ?></th>
                <th><?php esc_html_e( 'Contexto', 'pmpro-woo-sync' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $logs_page ) ) : ?>
                <tr>
                    <td colspan="4"><?php esc_html_e( 'No se encontraron logs para los filtros seleccionados.', 'pmpro-woo-sync' ); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ( $logs_page as $log ) : ?>
                    <?php include __DIR__ . '/log-row.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php
                printf(
                    /* translators: 1: total logs, 2: current page, 3: total pages */
                    esc_html__( '%1$s registros. Página %2$s de %3$s', 'pmpro-woo-sync' ),
                    number_format_i18n( $total_logs ),
                    number_format_i18n( $current_page ),
                    number_format_i18n( $total_pages )
                );
                ?>
            </span>
            <span class="pagination-links">
                <?php 
                $base_url = add_query_arg( array(
                    'page' => 'pmpro-woo-sync-logs',
                    's' => $search_term,
                    'level' => $level_filter
                ), admin_url( 'admin.php' ) );
                ?>
                <?php if ( $current_page > 1 ) : ?>
                    <a class="first-page button" href="<?php echo esc_url( add_query_arg( 'paged', 1, $base_url ) ); ?>">&laquo;</a>
                    <a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1, $base_url ) ); ?>">&lsaquo;</a>
                <?php endif; ?>
                <span class="paging-input">
                    <input class="current-page" type="text" name="paged" value="<?php echo esc_attr( $current_page ); ?>" size="2" />
                    <?php esc_html_e( 'de', 'pmpro-woo-sync' ); ?> <span class="total-pages"><?php echo esc_html( $total_pages ); ?></span>
                </span>
                <?php if ( $current_page < $total_pages ) : ?>
                    <a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1, $base_url ) ); ?>">&rsaquo;</a>
                    <a class="last-page button" href="<?php echo esc_url( add_query_arg( 'paged', $total_pages, $base_url ) ); ?>">&raquo;</a>
                <?php endif; ?>
            </span>
        </div>
    <?php endif; ?>
</div>

<style>
.pmpro-woo-sync-logs-filter {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}
.pmpro-woo-sync-logs-table th,
.pmpro-woo-sync-logs-table td {
    vertical-align: top;
    font-size: 14px;
}
.pmpro-woo-sync-logs-table .log-level {
    font-weight: bold;
    padding: 2px 8px;
    border-radius: 3px;
    text-transform: uppercase;
    font-size: 12px;
}
.pmpro-woo-sync-logs-table .log-level.error { background: #f8d7da; color: #721c24; }
.pmpro-woo-sync-logs-table .log-level.warning { background: #fff3cd; color: #856404; }
.pmpro-woo-sync-logs-table .log-level.info { background: #d1ecf1; color: #0c5460; }
.pmpro-woo-sync-logs-table .log-level.debug { background: #e2e3e5; color: #383d41; }
.pmpro-woo-sync-logs-table pre {
    margin: 0;
    font-size: 12px;
    background: #f8f9fa;
    border-radius: 3px;
    padding: 5px;
    white-space: pre-wrap;
    word-break: break-all;
}
.tablenav-pages {
    margin-top: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.tablenav-pages .pagination-links a.button {
    min-width: 28px;
    text-align: center;
    padding: 2px 6px;
}
.tablenav-pages .paging-input {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.tablenav-pages .current-page {
    width: 40px;
    text-align: center;
}
</style>
