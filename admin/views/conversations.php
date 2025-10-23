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
<div class="wrap envara-ai-assistant envara-conversations-page">
    <h1><?php esc_html_e( 'Conversations', 'envara-ai-assistant' ); ?></h1>

    <form method="get" class="envara-filter-form">
        <input type="hidden" name="page" value="envara-ai-assistant-conversations" />
        <label for="envara-chatbot-filter" class="screen-reader-text"><?php esc_html_e( 'Filter by chatbot', 'envara-ai-assistant' ); ?></label>
        <select id="envara-chatbot-filter" name="chatbot_id">
            <option value="0"><?php esc_html_e( 'All Chatbots', 'envara-ai-assistant' ); ?></option>
            <?php foreach ( $chatbots as $bot ) : ?>
                <option value="<?php echo esc_attr( $bot['id'] ); ?>" <?php selected( $chatbot_filter, $bot['id'] ); ?>><?php echo esc_html( $bot['name'] ); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button" type="submit"><?php esc_html_e( 'Apply Filter', 'envara-ai-assistant' ); ?></button>
    </form>

    <?php if ( empty( $sessions ) ) : ?>
        <div class="envara-conversation-empty">
            <h2><?php esc_html_e( 'No conversations yet.', 'envara-ai-assistant' ); ?></h2>
            <p><?php esc_html_e( 'When visitors start chatting, their conversations will appear here.', 'envara-ai-assistant' ); ?></p>
        </div>
    <?php else : ?>
        <div class="envara-conversation-collection">
            <?php foreach ( $sessions as $session ) :
                $session_id     = $session['session_id'];
                $chatbot_name   = $chatbot_map[ $session['chatbot_id'] ] ?? __( 'Chatbot', 'envara-ai-assistant' );
                $count          = $message_counts[ $session_id ] ?? 0;
                $latest         = $latest_messages[ $session_id ] ?? null;
                $preview        = $latest ? wp_trim_words( wp_strip_all_tags( $latest['message'] ), 24, '&hellip;' ) : __( 'No messages recorded yet.', 'envara-ai-assistant' );
                $last_activity  = $latest ? format_datetime( $latest['timestamp'] ) : format_datetime( $session['start_time'] );
                $sender_label   = $latest ? ucfirst( $latest['sender'] ) : '';
                $view_url       = add_query_arg(
                    [
                        'page'       => 'envara-ai-assistant-conversation',
                        'session_id' => $session_id,
                    ],
                    admin_url( 'admin.php' )
                );
                ?>
                <article class="envara-conversation-card">
                    <header class="envara-conversation-card__header">
                        <span class="envara-conversation-card__chatbot"><?php echo esc_html( $chatbot_name ); ?></span>
                        <span class="envara-conversation-card__status status-<?php echo esc_attr( $session['status'] ); ?>"><?php echo esc_html( ucfirst( $session['status'] ) ); ?></span>
                    </header>
                    <div class="envara-conversation-card__meta">
                        <div>
                            <span class="label"><?php esc_html_e( 'Visitor', 'envara-ai-assistant' ); ?></span>
                            <span class="value"><?php echo esc_html( $session['visitor_id'] ?: __( 'Guest', 'envara-ai-assistant' ) ); ?></span>
                        </div>
                        <div>
                            <span class="label"><?php esc_html_e( 'Started', 'envara-ai-assistant' ); ?></span>
                            <span class="value"><?php echo esc_html( format_datetime( $session['start_time'] ) ); ?></span>
                        </div>
                        <?php if ( ! empty( $session['page_url'] ) ) : ?>
                            <div>
                                <span class="label"><?php esc_html_e( 'Page', 'envara-ai-assistant' ); ?></span>
                                <a class="value" href="<?php echo esc_url( $session['page_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $session['page_url'] ); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="envara-conversation-card__preview">
                        <p class="snippet"><?php echo esc_html( $preview ); ?></p>
                        <p class="timestamp">
                            <?php if ( $sender_label ) : ?>
                                <strong><?php echo esc_html( $sender_label ); ?>:</strong>
                            <?php endif; ?>
                            <?php echo esc_html( sprintf( __( 'Last activity %s', 'envara-ai-assistant' ), $last_activity ) ); ?>
                        </p>
                        <p class="count"><?php echo esc_html( sprintf( _n( '%d message', '%d messages', $count, 'envara-ai-assistant' ), $count ) ); ?></p>
                    </div>
                    <div class="envara-conversation-card__actions">
                        <a class="button button-primary" href="<?php echo esc_url( $view_url ); ?>"><?php esc_html_e( 'View conversation', 'envara-ai-assistant' ); ?></a>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'envara_ai_assistant_export_conversation' ); ?>
                            <input type="hidden" name="action" value="envara_ai_assistant_export_conversation" />
                            <input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>" />
                            <input type="hidden" name="format" value="txt" />
                            <?php submit_button( __( 'Export', 'envara-ai-assistant' ), 'secondary', '', false ); ?>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
