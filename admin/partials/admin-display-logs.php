<?php
/**
 * Plantilla para la página de logs del plugin PMPro-Woo-Sync.
 * Variables disponibles: $logs, $total_logs, $logs_per_page, $current_page, $total_pages, $filter_level, $search_query, $log_stats
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Logs del Sistema - PMPro-Woo-Sync', 'pmpro-woo-sync' ); ?></h1>

    <div id="pmpro-woo-sync-admin-notices"></div>

    <!-- Estadísticas de Logs -->
    <div class="pmpro-woo-sync-stats-container">
        <div class="pmpro-woo-sync-stat-box">
            <h3><?php echo esc_html( number_format_i18n( $log_stats['total'] ?? 0 ) ); ?></h3>
            <p><?php esc_html_e( 'Total de Logs', 'pmpro-woo-sync' ); ?></p>
        </div>
        
        <div class="pmpro-woo-sync-stat-box">
            <h3><?php echo esc_html( number_format_i18n( $log_stats['last_24h'] ?? 0 ) ); ?></h3>
            <p><?php esc_html_e( 'Últimas 24h', 'pmpro-woo-sync' ); ?></p>
        </div>
        
        <div class="pmpro-woo-sync-stat-box error-stat">
            <h3><?php echo esc_html( number_format_i18n( $log_stats['by_level']['error'] ?? 0 ) ); ?></h3>
            <p><?php esc_html_e( 'Errores', 'pmpro-woo-sync' ); ?></p>
        </div>
        
        <div class="pmpro-woo-sync-stat-box warning-stat">
            <h3><?php echo esc_html( number_format_i18n( $log_stats['by_level']['warning'] ?? 0 ) ); ?></h3>
            <p><?php esc_html_e( 'Advertencias', 'pmpro-woo-sync' ); ?></p>
        </div>
    </div>

    <!-- Controles y Filtros -->
    <div class="pmpro-woo-sync-logs-controls">
        <div class="pmpro-woo-sync-filters">
            <form method="get" id="logs-filter-form">
                <input type="hidden" name="page" value="pmpro-woo-sync-logs">
                
                <select name="log_level_filter" id="log-level-filter">
                    <option value=""><?php esc_html_e( 'Todos los niveles', 'pmpro-woo-sync' ); ?></option>
                    <option value="info" <?php selected( $filter_level, 'info' ); ?>><?php esc_html_e( 'Info', 'pmpro-woo-sync' ); ?></option>
                    <option value="success" <?php selected( $filter_level, 'success' ); ?>><?php esc_html_e( 'Success', 'pmpro-woo-sync' ); ?></option>
                    <option value="warning" <?php selected( $filter_level, 'warning' ); ?>><?php esc_html_e( 'Advertencia', 'pmpro-woo-sync' ); ?></option>
                    <option value="error" <?php selected( $filter_level, 'error' ); ?>><?php esc_html_e( 'Error', 'pmpro-woo-sync' ); ?></option>
                    <option value="debug" <?php selected( $filter_level, 'debug' ); ?>><?php esc_html_e( 'Debug', 'pmpro-woo-sync' ); ?></option>
                </select>
                
                <input type="search" name="search" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Buscar en mensajes...', 'pmpro-woo-sync' ); ?>" id="log-search">
                
                <button type="submit" class="button"><?php esc_html_e( 'Filtrar', 'pmpro-woo-sync' ); ?></button>
                
                <?php if ( ! empty( $filter_level ) || ! empty( $search_query ) ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-woo-sync-logs' ) ); ?>" class="button">
                        <?php esc_html_e( 'Limpiar Filtros', 'pmpro-woo-sync' ); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="pmpro-woo-sync-actions">
            <button type="button" id="refresh-logs" class="button">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e( 'Refrescar', 'pmpro-woo-sync' ); ?>
            </button>
            
            <button type="button" id="export-logs" class="button">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e( 'Exportar', 'pmpro-woo-sync' ); ?>
            </button>
            
            <button type="button" id="clear-logs" class="button button-secondary">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e( 'Limpiar Logs', 'pmpro-woo-sync' ); ?>
            </button>
        </div>
    </div>

    <!-- Tabla de Logs -->
    <div class="pmpro-woo-sync-logs-table-container">
        <?php if ( empty( $logs ) ) : ?>
            <div class="pmpro-woo-sync-no-logs">
                <div class="dashicons dashicons-admin-page"></div>
                <h3><?php esc_html_e( 'No hay logs disponibles', 'pmpro-woo-sync' ); ?></h3>
                <p><?php esc_html_e( 'Los logs aparecerán aquí cuando el plugin realice operaciones.', 'pmpro-woo-sync' ); ?></p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped" id="logs-table">
                <thead>
                    <tr>
                        <th scope="col" class="column-timestamp"><?php esc_html_e( 'Fecha/Hora', 'pmpro-woo-sync' ); ?></th>
                        <th scope="col" class="column-level"><?php esc_html_e( 'Nivel', 'pmpro-woo-sync' ); ?></th>
                        <th scope="col" class="column-message"><?php esc_html_e( 'Mensaje', 'pmpro-woo-sync' ); ?></th>
                        <th scope="col" class="column-actions"><?php esc_html_e( 'Acciones', 'pmpro-woo-sync' ); ?></th>
                    </tr>
                </thead>
                <tbody id="logs-table-body">
                    <?php foreach ( $logs as $log_entry ) : ?>
                        <?php include PMPRO_WOO_SYNC_PATH . 'admin/partials/log-row.php'; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <?php if ( $total_pages > 1 ) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        $page_links = paginate_links( array(
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'add_args'  => array(
                                'log_level_filter' => $filter_level,
                                'search' => $search_query,
                            ),
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_pages,
                            'current'   => $current_page,
                        ));
                        
                        if ( $page_links ) {
                            echo '<span class="displaying-num">' . sprintf( 
                                _n( '%s log', '%s logs', $total_logs, 'pmpro-woo-sync' ), 
                                number_format_i18n( $total_logs ) 
                            ) . '</span>';
                            echo $page_links;
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Herramientas Adicionales -->
    <div class="pmpro-woo-sync-logs-tools">
        <h3><?php esc_html_e( 'Herramientas de Logs', 'pmpro-woo-sync' ); ?></h3>
        
        <form method="post" class="pmpro-woo-sync-inline-form">
            <?php wp_nonce_field( 'pmpro_woo_sync_logs_action' ); ?>
            <input type="hidden" name="pmpro_woo_sync_logs_action" value="cleanup_old_logs">
            <button type="submit" class="button">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php esc_html_e( 'Limpiar Logs Antiguos', 'pmpro-woo-sync' ); ?>
            </button>
            <span class="description">
                <?php 
                printf( 
                    esc_html__( 'Elimina logs más antiguos que %d días según configuración.', 'pmpro-woo-sync' ),
                    $this->settings->get_setting( 'log_retention_days', 30 )
                );
                ?>
            </span>
        </form>
    </div>
</div>

<!-- Modal de Detalles del Log -->
<div id="log-details-modal" style="display: none;">
    <div class="log-details-content">
        <h3><?php esc_html_e( 'Detalles del Log', 'pmpro-woo-sync' ); ?></h3>
        <div id="log-details-body"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Auto-filtrado en tiempo real
    $('#log-level-filter, #log-search').on('change keyup', function() {
        if ($(this).is('#log-search')) {
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(function() {
                $('#logs-filter-form').submit();
            }, 500);
        } else {
            $('#logs-filter-form').submit();
        }
    });

    // Modal de detalles
    $('.view-log-details').on('click', function(e) {
        e.preventDefault();
        var context = $(this).data('context');
        var message = $(this).data('message');
        var timestamp = $(this).data('timestamp');
        
        var modalContent = '<div class="log-detail-item"><strong><?php esc_js( __( 'Mensaje:', 'pmpro-woo-sync' ) ); ?></strong><br>' + message + '</div>';
        modalContent += '<div class="log-detail-item"><strong><?php esc_js( __( 'Fecha:', 'pmpro-woo-sync' ) ); ?></strong><br>' + timestamp + '</div>';
        
        if (context && context !== 'null' && context !== '[]') {
            modalContent += '<div class="log-detail-item"><strong><?php esc_js( __( 'Contexto:', 'pmpro-woo-sync' ) ); ?></strong><br><pre>' + context + '</pre></div>';
        }
        
        $('#log-details-body').html(modalContent);
        $('#log-details-modal').dialog({
            modal: true,
            width: 600,
            height: 400,
            resizable: true,
            title: '<?php esc_js( __( 'Detalles del Log', 'pmpro-woo-sync' ) ); ?>'
        });
    });
});
</script>
