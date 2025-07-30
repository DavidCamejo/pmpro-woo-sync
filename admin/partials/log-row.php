<?php
/**
 * Plantilla para una fila individual de log.
 * Variables disponibles: $log_entry
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$context_json = '';
if ( ! empty( $log_entry->context ) ) {
    $context_decoded = json_decode( $log_entry->context, true );
    $context_json = wp_json_encode( $context_decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
}
?>

<tr class="log-row log-level-<?php echo esc_attr( $log_entry->level ); ?>">
    <td class="column-timestamp">
        <?php echo esc_html( mysql2date( 'Y-m-d H:i:s', $log_entry->timestamp ) ); ?>
    </td>
    
    <td class="column-level">
        <span class="pmpro-woo-sync-log-level pmpro-woo-sync-log-level-<?php echo esc_attr( $log_entry->level ); ?>">
            <?php echo esc_html( ucfirst( $log_entry->level ) ); ?>
        </span>
    </td>
    
    <td class="column-message">
        <div class="log-message">
            <?php echo esc_html( wp_trim_words( $log_entry->message, 15 ) ); ?>
            <?php if ( str_word_count( $log_entry->message ) > 15 ) : ?>
                <span class="log-message-full" style="display: none;">
                    <?php echo esc_html( $log_entry->message ); ?>
                </span>
                <a href="#" class="toggle-full-message"><?php esc_html_e( 'ver más', 'pmpro-woo-sync' ); ?></a>
            <?php endif; ?>
        </div>
    </td>
    
    <td class="column-actions">
        <?php if ( ! empty( $context_json ) && $context_json !== '[]' ) : ?>
            <a href="#" class="view-log-details button button-small" 
               data-context="<?php echo esc_attr( $context_json ); ?>"
               data-message="<?php echo esc_attr( $log_entry->message ); ?>"
               data-timestamp="<?php echo esc_attr( $log_entry->timestamp ); ?>">
                <?php esc_html_e( 'Detalles', 'pmpro-woo-sync' ); ?>
            </a>
        <?php endif; ?>
    </td>
</tr>
