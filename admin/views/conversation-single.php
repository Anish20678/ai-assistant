<?php
/**
 * Single conversation view.
 *
 * @package Envara_Ai_Assistant
 */

use function Envara\AI_Assistant\format_datetime;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$conversation = $conversation ?? null;
$messages     = $messages ?? [];
$chatbot      = $chatbot ?? null;
$session_id   = $conversation['session_id'] ?? ( isset( $_GET['session_id'] ) ? sanitize_text_field( wp_unslash( $_GET['session_id'] ) ) : '' );
?>
<div class="wrap envara-ai-assistant envara-conversation-single">
    <a class="envara-conversation-back" href="<?php echo esc_url( admin_url( 'admin.php?page=envara-ai-assistant-conversations' ) ); ?>">
        &larr; <?php esc_html_e( 'Back to conversations', 'envara-ai-assistant' ); ?>
    </a>

    <?php if ( ! $conversation ) : ?>
        <div class="notice notice-error"><p><?php esc_html_e( 'Conversation not found or no longer available.', 'envara-ai-assistant' ); ?></p></div>
        <?php return; ?>
    <?php endif; ?>

    <div class="envara-conversation-summary">
        <div class="envara-conversation-summary__heading">
            <h1><?php echo esc_html( $chatbot['name'] ?? __( 'Chatbot', 'envara-ai-assistant' ) ); ?></h1>
            <span class="envara-conversation-status status-<?php echo esc_attr( $conversation['status'] ); ?>"><?php echo esc_html( ucfirst( $conversation['status'] ) ); ?></span>
        </div>
        <p class="envara-conversation-slug">/<?php esc_html_e( 'conversation', 'envara-ai-assistant' ); ?>/<?php echo esc_html( $session_id ); ?></p>
        <div class="envara-conversation-summary__meta">
            <div>
                <span class="label"><?php esc_html_e( 'Visitor', 'envara-ai-assistant' ); ?></span>
                <span class="value"><?php echo esc_html( $conversation['visitor_id'] ?: __( 'Guest', 'envara-ai-assistant' ) ); ?></span>
            </div>
            <div>
                <span class="label"><?php esc_html_e( 'IP Address', 'envara-ai-assistant' ); ?></span>
                <span class="value"><?php echo esc_html( $conversation['visitor_ip'] ?: __( 'Unknown', 'envara-ai-assistant' ) ); ?></span>
            </div>
            <div>
                <span class="label"><?php esc_html_e( 'Started', 'envara-ai-assistant' ); ?></span>
                <span class="value"><?php echo esc_html( format_datetime( $conversation['start_time'] ?? '' ) ); ?></span>
            </div>
            <div>
                <span class="label"><?php esc_html_e( 'Ended', 'envara-ai-assistant' ); ?></span>
                <span class="value"><?php echo esc_html( format_datetime( $conversation['end_time'] ?? '' ) ); ?></span>
            </div>
            <div>
                <span class="label"><?php esc_html_e( 'Page', 'envara-ai-assistant' ); ?></span>
                <?php if ( ! empty( $conversation['page_url'] ) ) : ?>
                    <a class="value" href="<?php echo esc_url( $conversation['page_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $conversation['page_url'] ); ?></a>
                <?php else : ?>
                    <span class="value"><?php esc_html_e( 'N/A', 'envara-ai-assistant' ); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="envara-conversation-summary__actions">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="envara-export-form">
                <?php wp_nonce_field( 'envara_ai_assistant_export_conversation' ); ?>
                <input type="hidden" name="action" value="envara_ai_assistant_export_conversation" />
                <input type="hidden" name="session_id" value="<?php echo esc_attr( $conversation['session_id'] ); ?>" />
                <input type="hidden" name="format" value="txt" />
                <?php submit_button( __( 'Export Transcript', 'envara-ai-assistant' ), 'secondary', '', false ); ?>
            </form>
        </div>
    </div>

    <section class="envara-conversation-transcript">
        <header class="envara-conversation-transcript__header">
            <h2><?php esc_html_e( 'Conversation Transcript', 'envara-ai-assistant' ); ?></h2>
            <span class="badge"><?php echo esc_html( sprintf( _n( '%d message', '%d messages', count( $messages ), 'envara-ai-assistant' ), count( $messages ) ) ); ?></span>
        </header>
        <?php if ( empty( $messages ) ) : ?>
            <p class="envara-conversation-empty"><?php esc_html_e( 'No messages logged for this conversation yet.', 'envara-ai-assistant' ); ?></p>
        <?php else : ?>
            <div class="envara-conversation-message-list">
                <?php foreach ( $messages as $message ) : ?>
                    <article class="envara-conversation-message message-<?php echo esc_attr( $message['sender'] ); ?>">
                        <header class="envara-conversation-message__meta">
                            <span class="author"><?php echo esc_html( ucfirst( $message['sender'] ) ); ?></span>
                            <time datetime="<?php echo esc_attr( gmdate( 'c', strtotime( $message['timestamp'] ) ) ); ?>"><?php echo esc_html( format_datetime( $message['timestamp'] ) ); ?></time>
                        </header>
                        <div class="envara-conversation-message__body">
                            <?php echo wp_kses_post( wpautop( $message['message'] ) ); ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
