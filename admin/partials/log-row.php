<?php
/**
 * Fila de log para la tabla de logs de PMPro-Woo-Sync
 *
 * @package PMPro_Woo_Sync
 * @since 2.0.0
 */

// $log debe ser un array asociativo con: date, level, message, context

$level_class = 'log-level ' . esc_attr( strtolower( $log['level'] ?? 'info' ) );
$context = !empty( $log['context'] ) ? wp_json_encode( $log['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) : '';
?>
<tr>
    <td><?php echo esc_html( $log['date'] ?? '' ); ?></td>
    <td><span class="<?php echo $level_class; ?>"><?php echo esc_html( strtoupper( $log['level'] ?? 'INFO' ) ); ?></span></td>
    <td><?php echo esc_html( $log['message'] ?? '' ); ?></td>
    <td>
        <?php if ( $context ) : ?>
            <pre><?php echo esc_html( $context ); ?></pre>
        <?php else : ?>
            <span style="color:#aaa;"><?php esc_html_e( 'Sin contexto', 'pmpro-woo-sync' ); ?></span>
        <?php endif; ?>
    </td>
</tr>
