<?php
/**
 * Training view.
 *
 * @package Envara_Ai_Assistant
 */

use Envara\AI_Assistant\Chatbot;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$chatbots = Chatbot::all();
?>
<div class="wrap envara-ai-assistant">
    <h1><?php esc_html_e( 'Train Chatbots', 'envara-ai-assistant' ); ?></h1>

    <form method="get">
        <input type="hidden" name="page" value="envara-ai-assistant-training" />
        <select name="chatbot_id">
            <option value="0"><?php esc_html_e( 'Select chatbot', 'envara-ai-assistant' ); ?></option>
            <?php foreach ( $chatbots as $bot ) : ?>
                <option value="<?php echo esc_attr( $bot['id'] ); ?>" <?php selected( $chatbot_id, $bot['id'] ); ?>><?php echo esc_html( $bot['name'] ); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button" type="submit"><?php esc_html_e( 'Load', 'envara-ai-assistant' ); ?></button>
    </form>

    <?php if ( $chatbot ) : ?>
        <h2><?php echo esc_html( sprintf( __( 'Training Data for %s', 'envara-ai-assistant' ), $chatbot['name'] ) ); ?></h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="envara-training-form">
            <?php wp_nonce_field( 'envara_ai_assistant_save_training' ); ?>
            <input type="hidden" name="action" value="envara_ai_assistant_save_training" />
            <input type="hidden" name="chatbot_id" value="<?php echo esc_attr( $chatbot_id ); ?>" />

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Upload Files', 'envara-ai-assistant' ); ?></th>
                    <td><input type="file" name="training_file" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Manual Text / FAQs', 'envara-ai-assistant' ); ?></th>
                    <td><textarea name="training_text" rows="5" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Website URL', 'envara-ai-assistant' ); ?></th>
                    <td><input type="url" name="training_url" class="regular-text" /></td>
                </tr>
            </table>

            <?php submit_button( __( 'Train Now', 'envara-ai-assistant' ) ); ?>
        </form>

        <h3><?php esc_html_e( 'Training Sources', 'envara-ai-assistant' ); ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Type', 'envara-ai-assistant' ); ?></th>
                    <th><?php esc_html_e( 'Source', 'envara-ai-assistant' ); ?></th>
                    <th><?php esc_html_e( 'Added On', 'envara-ai-assistant' ); ?></th>
                    <th><?php esc_html_e( 'Action', 'envara-ai-assistant' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $sources ) ) : ?>
                    <tr>
                        <td colspan="4"><?php esc_html_e( 'No training sources yet.', 'envara-ai-assistant' ); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $sources as $source ) : ?>
                        <tr>
                            <td><?php echo esc_html( ucfirst( $source['source_type'] ) ); ?></td>
                            <td><?php echo 'file' === $source['source_type'] ? esc_html( basename( $source['source_path'] ) ) : esc_html( $source['source_path'] ); ?></td>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $source['created_at'] ) ) ); ?></td>
                            <td>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                    <?php wp_nonce_field( 'envara_ai_assistant_delete_source' ); ?>
                                    <input type="hidden" name="action" value="envara_ai_assistant_delete_source" />
                                    <input type="hidden" name="source_id" value="<?php echo esc_attr( $source['id'] ); ?>" />
                                    <input type="hidden" name="chatbot_id" value="<?php echo esc_attr( $chatbot_id ); ?>" />
                                    <button type="submit" class="button-link delete-link" onclick="return confirm('<?php echo esc_js( __( 'Delete this source?', 'envara-ai-assistant' ) ); ?>');"><?php esc_html_e( 'Delete', 'envara-ai-assistant' ); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
