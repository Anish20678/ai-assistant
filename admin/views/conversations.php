<?php
/**
 * Conversations view.
 *
 * @package Envara_Ai_Assistant
 */

use Envara\AI_Assistant\Chatbot;
use function Envara\AI_Assistant\format_datetime;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$chatbots = Chatbot::all();
$chatbot_map = [];
foreach ( $chatbots as $bot ) {
    $chatbot_map[ $bot['id'] ] = $bot['name'];
}
?>
<div class="wrap envara-ai-assistant">
    <h1><?php esc_html_e( 'Conversations', 'envara-ai-assistant' ); ?></h1>

    <form method="get" class="envara-filter-form">
        <input type="hidden" name="page" value="envara-ai-assistant-conversations" />
        <select name="chatbot_id">
            <option value="0"><?php esc_html_e( 'All Chatbots', 'envara-ai-assistant' ); ?></option>
            <?php foreach ( $chatbots as $bot ) : ?>
                <option value="<?php echo esc_attr( $bot['id'] ); ?>" <?php selected( $chatbot_filter, $bot['id'] ); ?>><?php echo esc_html( $bot['name'] ); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button" type="submit"><?php esc_html_e( 'Filter', 'envara-ai-assistant' ); ?></button>
    </form>

    <div class="envara-conversations">
        <div class="envara-conversations-list">
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Chatbot', 'envara-ai-assistant' ); ?></th>
                        <th><?php esc_html_e( 'Visitor', 'envara-ai-assistant' ); ?></th>
                        <th><?php esc_html_e( 'Page', 'envara-ai-assistant' ); ?></th>
                        <th><?php esc_html_e( 'Start Time', 'envara-ai-assistant' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'envara-ai-assistant' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $sessions ) ) : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e( 'No conversations yet.', 'envara-ai-assistant' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $sessions as $session ) : ?>
                            <tr class="<?php echo $session['session_id'] === $view_id ? 'active' : ''; ?>">
                                <td><a href="<?php echo esc_url( add_query_arg( [ 'session_id' => $session['session_id'] ], admin_url( 'admin.php?page=envara-ai-assistant-conversations' ) ) ); ?>"><?php echo esc_html( $chatbot_map[ $session['chatbot_id'] ] ?? __( 'Chatbot', 'envara-ai-assistant' ) ); ?></a></td>
                                <td><?php echo esc_html( $session['visitor_id'] ?: __( 'Guest', 'envara-ai-assistant' ) ); ?></td>
                                <td><?php echo esc_html( $session['page_url'] ); ?></td>
                                <td><?php echo esc_html( format_datetime( $session['start_time'] ) ); ?></td>
                                <td><?php echo esc_html( ucfirst( $session['status'] ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="envara-conversation-details">
            <?php if ( $view ) : ?>
                <h2><?php esc_html_e( 'Conversation Details', 'envara-ai-assistant' ); ?></h2>
                <p><strong><?php esc_html_e( 'Visitor ID:', 'envara-ai-assistant' ); ?></strong> <?php echo esc_html( $view['visitor_id'] ?: __( 'Guest', 'envara-ai-assistant' ) ); ?></p>
                <p><strong><?php esc_html_e( 'IP Address:', 'envara-ai-assistant' ); ?></strong> <?php echo esc_html( $view['visitor_ip'] ); ?></p>
                <p><strong><?php esc_html_e( 'Page:', 'envara-ai-assistant' ); ?></strong> <?php echo esc_html( $view['page_url'] ); ?></p>
                <p><strong><?php esc_html_e( 'Started:', 'envara-ai-assistant' ); ?></strong> <?php echo esc_html( format_datetime( $view['start_time'] ) ); ?></p>
                <p><strong><?php esc_html_e( 'Ended:', 'envara-ai-assistant' ); ?></strong> <?php echo esc_html( format_datetime( $view['end_time'] ?? '' ) ); ?></p>

                <div class="envara-transcript">
                    <?php foreach ( $messages as $message ) : ?>
                        <div class="message message-<?php echo esc_attr( $message['sender'] ); ?>">
                            <strong><?php echo esc_html( ucfirst( $message['sender'] ) ); ?>:</strong>
                            <p><?php echo wp_kses_post( nl2br( $message['message'] ) ); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="envara-export-form">
                    <?php wp_nonce_field( 'envara_ai_assistant_export_conversation' ); ?>
                    <input type="hidden" name="action" value="envara_ai_assistant_export_conversation" />
                    <input type="hidden" name="session_id" value="<?php echo esc_attr( $view['session_id'] ); ?>" />
                    <select name="format">
                        <option value="txt"><?php esc_html_e( 'Export as TXT', 'envara-ai-assistant' ); ?></option>
                    </select>
                    <?php submit_button( __( 'Export', 'envara-ai-assistant' ), 'secondary', '', false ); ?>
                </form>
            <?php else : ?>
                <p><?php esc_html_e( 'Select a conversation to view details.', 'envara-ai-assistant' ); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
