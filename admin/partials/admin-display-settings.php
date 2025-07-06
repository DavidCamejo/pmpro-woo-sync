<?php
/**
 * Plantilla para la página de ajustes del plugin PMPRO-WooCommerce Sync.
 *
 * Se espera que sea incluida por PMPro_Woo_Sync_Admin::display_settings_page().
 */
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Ajustes del Plugin PMPRO-WooCommerce Sync', 'pmpro-woo-sync' ); ?></h1>

    <form method="post" action="options.php">
        <?php
        // Imprime los campos ocultos de seguridad necesarios para la Settings API.
        //settings_fields( 'pmpro_woo_sync_option_group' );
        settings_fields( PMPro_Woo_Sync_Settings::SETTINGS_GROUP_NAME ); // <- USA LA CONSTANTE AQUÍ

        // Imprime todas las secciones y campos registrados para la página 'pmpro-woo-sync'.
        do_settings_sections( 'pmpro-woo-sync' );

        // Imprime el botón de guardar.
        submit_button( __( 'Guardar Cambios', 'pmpro-woo-sync' ) );
        ?>
    </form>
</div>
