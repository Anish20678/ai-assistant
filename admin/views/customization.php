<?php
/**
 * Customization view.
 *
 * @package Envara_Ai_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'envara_ai_assistant_save_customization' ) ) {
    $data = wp_unslash( $_POST['customization'] ?? [] );
    update_option( 'envara_ai_assistant_customization', array_map( 'sanitize_text_field', $data ) );
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Customization saved.', 'envara-ai-assistant' ) . '</p></div>';
}

$options = wp_parse_args( $options, [
    'theme'      => 'light',
    'custom_css' => '',
] );
?>
<div class="wrap envara-ai-assistant">
    <h1><?php esc_html_e( 'Customization', 'envara-ai-assistant' ); ?></h1>

    <form method="post">
        <?php wp_nonce_field( 'envara_ai_assistant_save_customization' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="customization-theme"><?php esc_html_e( 'Theme', 'envara-ai-assistant' ); ?></label></th>
                <td>
                    <select id="customization-theme" name="customization[theme]">
                        <option value="light" <?php selected( $options['theme'], 'light' ); ?>><?php esc_html_e( 'Light', 'envara-ai-assistant' ); ?></option>
                        <option value="dark" <?php selected( $options['theme'], 'dark' ); ?>><?php esc_html_e( 'Dark', 'envara-ai-assistant' ); ?></option>
                        <option value="auto" <?php selected( $options['theme'], 'auto' ); ?>><?php esc_html_e( 'Auto', 'envara-ai-assistant' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="customization-css"><?php esc_html_e( 'Custom CSS', 'envara-ai-assistant' ); ?></label></th>
                <td><textarea id="customization-css" name="customization[custom_css]" rows="6" class="large-text"><?php echo esc_textarea( $options['custom_css'] ); ?></textarea></td>
            </tr>
        </table>

        <?php submit_button( __( 'Save Customization', 'envara-ai-assistant' ) ); ?>
    </form>
</div>
