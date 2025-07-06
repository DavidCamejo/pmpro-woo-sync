<?php
/**
 * Plantilla para la página de logs del plugin PMPRO-WooCommerce Sync.
 *
 * Se espera que sea incluida por PMPro_Woo_Sync_Admin::display_logs_page().
 * Variables disponibles: $logs, $total_logs, $logs_per_page, $current_page, $total_pages, $filter_level
 */
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Logs del Plugin PMPRO-WooCommerce Sync', 'pmpro-woo-sync' ); ?></h1>

    <form method="get" class="alignright">
        <input type="hidden" name="page" value="pmpro-woo-sync-logs">
        <select name="log_level_filter">
            <option value=""><?php esc_html_e( 'Todos los niveles', 'pmpro-woo-sync' ); ?></option>
            <option value="info" <?php selected( $filter_level, 'info' ); ?>><?php esc_html_e( 'Info', 'pmpro-woo-sync' ); ?></option>
            <option value="warning" <?php selected( $filter_level, 'warning' ); ?>><?php esc_html_e( 'Advertencia', 'pmpro-woo-sync' ); ?></option>
            <option value="error" <?php selected( $filter_level, 'error' ); ?>><?php esc_html_e( 'Error', 'pmpro-woo-sync' ); ?></option>
            <option value="debug" <?php selected( $filter_level, 'debug' ); ?>><?php esc_html_e( 'Depuración', 'pmpro-woo-sync' ); ?></option>
        </select>
        <input type="submit" value="<?php esc_attr_e( 'Filtrar', 'pmpro-woo-sync' ); ?>" class="button">
    </form>

    <?php if ( empty( $logs ) ) : ?>
        <p><?php esc_html_e( 'No hay logs disponibles.', 'pmpro-woo-sync' ); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e( 'Fecha/Hora', 'pmpro-woo-sync' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Nivel', 'pmpro-woo-sync' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Mensaje', 'pmpro-woo-sync' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Contexto', 'pmpro-woo-sync' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $logs as $log_entry ) : ?>
                    <tr>
                        <td><?php echo esc_html( $log_entry->timestamp ); ?></td>
                        <td><span class="pmpro-woo-sync-log-level pmpro-woo-sync-log-level-<?php echo esc_attr( $log_entry->level ); ?>"><?php echo esc_html( ucfirst( $log_entry->level ) ); ?></span></td>
                        <td><?php echo esc_html( $log_entry->message ); ?></td>
                        <td><pre style="white-space: pre-wrap; word-break: break-all; font-size: 0.8em;"><?php echo esc_html( wp_json_encode( json_decode( $log_entry->context ), JSON_PRETTY_PRINT ) ); ?></pre></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                $page_links = paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'add_args'  => ['log_level_filter' => $filter_level],
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'     => $total_pages,
                    'current'   => $current_page,
                ) );
                if ( $page_links ) {
                    echo '<span class="displaying-num">' . sprintf( _n( '%s log', '%s logs', $total_logs, 'pmpro-woo-sync' ), number_format_i18n( $total_logs ) ) . '</span>';
                    echo $page_links;
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    /* Estilos básicos para los niveles de log */
    .pmpro-woo-sync-log-level {
        padding: 2px 5px;
        border-radius: 3px;
        font-size: 0.8em;
        font-weight: bold;
        color: #fff;
    }
    .pmpro-woo-sync-log-level-info { background-color: #28a745; } /* Green */
    .pmpro-woo-sync-log-level-warning { background-color: #ffc107; color: #333; } /* Yellow */
    .pmpro-woo-sync-log-level-error { background-color: #dc3545; } /* Red */
    .pmpro-woo-sync-log-level-debug { background-color: #007bff; } /* Blue */
</style>
