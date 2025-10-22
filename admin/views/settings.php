<?php
/**
 * Settings view wrapper.
 *
 * @package Envara_Ai_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap envara-ai-assistant">
    <h1><?php esc_html_e( 'AI Assistant Settings', 'envara-ai-assistant' ); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields( 'envara_ai_assistant_settings' );
        do_settings_sections( 'envara_ai_assistant_settings' );
        submit_button();
        ?>
    </form>
</div>
