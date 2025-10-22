<?php
/**
 * Chatbot form.
 *
 * @package Envara_Ai_Assistant
 */

use Envara\AI_Assistant\Chatbot;
use function Envara\AI_Assistant\safe_json_decode;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$chatbot     = $chatbot ?? [];
$settings    = isset( $chatbot['settings'] ) ? safe_json_decode( $chatbot['settings'] ) : [];
$form_schema = ! empty( $chatbot['id'] ) ? Chatbot::get_form_schema( (int) $chatbot['id'] ) : [];
?>
<div class="wrap envara-ai-assistant">
    <h1><?php echo esc_html( empty( $chatbot['id'] ) ? __( 'Create Chatbot', 'envara-ai-assistant' ) : __( 'Edit Chatbot', 'envara-ai-assistant' ) ); ?></h1>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'envara_ai_assistant_save_chatbot' ); ?>
        <input type="hidden" name="action" value="envara_ai_assistant_save_chatbot" />
        <input type="hidden" name="chatbot_id" value="<?php echo esc_attr( $chatbot['id'] ?? 0 ); ?>" />

        <h2><?php esc_html_e( 'Basic Setup', 'envara-ai-assistant' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="chatbot-name"><?php esc_html_e( 'Chatbot Name', 'envara-ai-assistant' ); ?></label></th>
                <td><input type="text" id="chatbot-name" name="chatbot[name]" value="<?php echo esc_attr( $chatbot['name'] ?? '' ); ?>" class="regular-text" required /></td>
            </tr>
            <tr>
                <th scope="row"><label for="chatbot-model"><?php esc_html_e( 'Model', 'envara-ai-assistant' ); ?></label></th>
                <td>
                    <select name="chatbot[model]" id="chatbot-model">
                        <option value="gpt-3.5-turbo" <?php selected( $chatbot['model'] ?? '', 'gpt-3.5-turbo' ); ?>><?php esc_html_e( 'GPT-3.5 Turbo', 'envara-ai-assistant' ); ?></option>
                        <option value="gpt-4" <?php selected( $chatbot['model'] ?? '', 'gpt-4' ); ?>><?php esc_html_e( 'GPT-4', 'envara-ai-assistant' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="chatbot-system-prompt"><?php esc_html_e( 'System Prompt', 'envara-ai-assistant' ); ?></label></th>
                <td><textarea id="chatbot-system-prompt" name="chatbot[system_prompt]" rows="4" class="large-text"><?php echo esc_textarea( $chatbot['system_prompt'] ?? '' ); ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><label for="chatbot-welcome-message"><?php esc_html_e( 'Welcome Message', 'envara-ai-assistant' ); ?></label></th>
                <td><textarea id="chatbot-welcome-message" name="chatbot[welcome_message]" rows="3" class="large-text"><?php echo esc_textarea( $chatbot['welcome_message'] ?? '' ); ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Visibility', 'envara-ai-assistant' ); ?></th>
                <td>
                    <fieldset>
                        <label><input type="radio" name="chatbot[visibility]" value="sitewide" <?php checked( $chatbot['visibility'] ?? 'sitewide', 'sitewide' ); ?> /> <?php esc_html_e( 'Entire Site', 'envara-ai-assistant' ); ?></label><br />
                        <label><input type="radio" name="chatbot[visibility]" value="specific" <?php checked( $chatbot['visibility'] ?? '', 'specific' ); ?> /> <?php esc_html_e( 'Specific Pages (enter IDs comma separated)', 'envara-ai-assistant' ); ?></label>
                        <input type="text" name="chatbot[visibility_data]" value="<?php echo esc_attr( $chatbot['visibility_data'] ?? '' ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Use shortcode [ai_assistant id="X"] to embed manually.', 'envara-ai-assistant' ); ?></p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Status', 'envara-ai-assistant' ); ?></th>
                <td>
                    <select name="chatbot[status]">
                        <option value="active" <?php selected( $chatbot['status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'envara-ai-assistant' ); ?></option>
                        <option value="inactive" <?php selected( $chatbot['status'] ?? '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'envara-ai-assistant' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e( 'Pre-Chat Form', 'envara-ai-assistant' ); ?></h2>
        <div class="envara-form-builder" data-fields="<?php echo esc_attr( wp_json_encode( $form_schema ) ); ?>">
            <table class="widefat envara-form-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Label', 'envara-ai-assistant' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'envara-ai-assistant' ); ?></th>
                        <th><?php esc_html_e( 'Required', 'envara-ai-assistant' ); ?></th>
                        <th><?php esc_html_e( 'Options (comma separated)', 'envara-ai-assistant' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'envara-ai-assistant' ); ?></th>
                    </tr>
                </thead>
                <tbody class="envara-form-fields"></tbody>
            </table>
            <button type="button" class="button add-form-field"><?php esc_html_e( 'Add Field', 'envara-ai-assistant' ); ?></button>
            <input type="hidden" name="chatbot[form][fields]" value="<?php echo esc_attr( wp_json_encode( $form_schema ) ); ?>" class="form-fields-input" />
        </div>

        <h2><?php esc_html_e( 'Session Rules & UI', 'envara-ai-assistant' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="chatbot-settings-timeout"><?php esc_html_e( 'Inactivity Timeout (minutes)', 'envara-ai-assistant' ); ?></label></th>
                <td><input type="number" id="chatbot-settings-timeout" name="chatbot[settings][timeout]" value="<?php echo esc_attr( $settings['timeout'] ?? 10 ); ?>" min="1" class="small-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="chatbot-settings-auto-close"><?php esc_html_e( 'Auto Close Message', 'envara-ai-assistant' ); ?></label></th>
                <td><input type="text" id="chatbot-settings-auto-close" name="chatbot[settings][auto_close_message]" value="<?php echo esc_attr( $settings['auto_close_message'] ?? __( 'Chat closed due to inactivity.', 'envara-ai-assistant' ) ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="chatbot-settings-theme"><?php esc_html_e( 'Chat Theme Color', 'envara-ai-assistant' ); ?></label></th>
                <td><input type="text" id="chatbot-settings-theme" name="chatbot[settings][theme_color]" value="<?php echo esc_attr( $settings['theme_color'] ?? '#1e73be' ); ?>" class="regular-text" /></td>
            </tr>
        </table>

        <?php submit_button( __( 'Save Chatbot', 'envara-ai-assistant' ) ); ?>
    </form>
</div>
