<?php
/**
 * Shortcode handler.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant\Frontend;

use Envara\AI_Assistant\Chatbot;
use function Envara\AI_Assistant\get_chatbot;
use function Envara\AI_Assistant\safe_json_decode;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode class.
 */
class Shortcode {
    /**
     * Registers shortcode.
     */
    public static function register(): void {
        add_shortcode( 'ai_assistant', [ self::class, 'render' ] );
    }

    /**
     * Renders shortcode output.
     *
     * @param array $atts Shortcode attributes.
     *
     * @return string
     */
    public static function render( array $atts ): string {
        $atts = shortcode_atts( [ 'id' => 0 ], $atts, 'ai_assistant' );
        $id   = absint( $atts['id'] );

        if ( ! $id ) {
            return '';
        }

        $chatbot = get_chatbot( $id );

        if ( ! $chatbot || 'inactive' === $chatbot['status'] ) {
            return '';
        }

        $settings = safe_json_decode( $chatbot['settings'] ?? '{}' );
        $form     = Chatbot::get_form_schema( $id );

        $theme_color  = ! empty( $settings['theme_color'] ) ? $settings['theme_color'] : '#7b3fff';
        $accent_color = self::tint_color( $theme_color, 32 );
        $initial      = self::initial( $chatbot['name'] ?? '' );

        $welcome_heading   = __( 'Hello there 👋', 'envara-ai-assistant' );
        $welcome_subtitle  = $chatbot['welcome_message'] ? wp_strip_all_tags( $chatbot['welcome_message'] ) : __( 'How can we help?', 'envara-ai-assistant' );
        $conversation_hint = __( 'Typically replies instantly', 'envara-ai-assistant' );

        ob_start();
        ?>
        <div class="envara-ai-widget" style="--envara-ai-theme: <?php echo esc_attr( $theme_color ); ?>; --envara-ai-theme-accent: <?php echo esc_attr( $accent_color ); ?>;" data-chatbot="<?php echo esc_attr( wp_json_encode( [
            'id'              => $chatbot['id'],
            'name'            => $chatbot['name'],
            'welcomeMessage'  => $chatbot['welcome_message'],
            'systemPrompt'    => $chatbot['system_prompt'],
            'model'           => $chatbot['model'],
            'settings'        => $settings,
            'form'            => $form,
        ] ) ); ?>">
            <button class="envara-ai-launch" type="button" aria-expanded="false">
                <span class="envara-ai-launch-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false" role="img"><path d="M12 3C7.03 3 3 6.58 3 11c0 2.24 1.03 4.27 2.73 5.74-.21.82-.74 2.79-.75 2.87a.5.5 0 0 0 .74.54c.03-.02 2.7-1.49 3.93-2.18.74.21 1.52.33 2.35.33 4.97 0 9-3.58 9-8s-4.03-8-9-8Z" fill="currentColor"/></svg>
                </span>
                <span class="envara-ai-launch-label"><?php echo esc_html( $chatbot['name'] ); ?></span>
            </button>
            <div class="envara-ai-window" hidden>
                <div class="envara-ai-surface">
                    <div class="envara-ai-hero">
                        <div class="envara-ai-hero-top">
                            <div class="envara-ai-brand">
                                <span class="envara-ai-brand-name"><?php echo esc_html( $chatbot['name'] ); ?></span>
                                <div class="envara-ai-avatars" aria-hidden="true">
                                    <span class="envara-ai-avatar"><?php echo esc_html( $initial ); ?></span>
                                    <span class="envara-ai-avatar envara-ai-avatar-secondary"></span>
                                </div>
                            </div>
                            <button type="button" class="envara-ai-close" aria-label="<?php esc_attr_e( 'Close chat', 'envara-ai-assistant' ); ?>">
                                <svg viewBox="0 0 24 24" focusable="false" role="img"><path d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 1 0 5.7 7.11L10.59 12l-4.9 4.89a1 1 0 1 0 1.41 1.42L12 13.41l4.89 4.9a1 1 0 0 0 1.42-1.41L13.41 12l4.9-4.89a1 1 0 0 0-.01-1.4Z" fill="currentColor"/></svg>
                            </button>
                        </div>
                        <div class="envara-ai-greeting">
                            <h2><?php echo esc_html( $welcome_heading ); ?></h2>
                            <p><?php echo esc_html( $welcome_subtitle ); ?></p>
                        </div>
                    </div>
                    <div class="envara-ai-views">
                        <section class="envara-ai-view envara-ai-view-home is-active" data-view="home" aria-label="<?php esc_attr_e( 'Chat home', 'envara-ai-assistant' ); ?>">
                            <div class="envara-ai-home-actions">
                                <button type="button" class="envara-ai-start-chat">
                                    <span><?php esc_html_e( 'Chat with us', 'envara-ai-assistant' ); ?></span>
                                </button>
                            </div>
                            <div class="envara-ai-search" hidden>
                                <label class="screen-reader-text" for="envara-ai-search-<?php echo esc_attr( $chatbot['id'] ); ?>"><?php esc_html_e( 'Search for help', 'envara-ai-assistant' ); ?></label>
                                <input type="search" id="envara-ai-search-<?php echo esc_attr( $chatbot['id'] ); ?>" placeholder="<?php esc_attr_e( 'Search for help', 'envara-ai-assistant' ); ?>" autocomplete="off" />
                            </div>
                            <ul class="envara-ai-suggestion-list" role="list"></ul>
                            <form class="envara-ai-form" hidden></form>
                        </section>
                        <section class="envara-ai-view envara-ai-view-messages" data-view="messages" aria-label="<?php esc_attr_e( 'Messages', 'envara-ai-assistant' ); ?>">
                            <div class="envara-ai-empty-state">
                                <div class="envara-ai-empty-illustration" aria-hidden="true">
                                    <svg viewBox="0 0 64 64" role="img" focusable="false"><path d="M12 12h40a4 4 0 0 1 4 4v28a4 4 0 0 1-4 4H27.41l-8.7 7.25A2 2 0 0 1 16 53V48h-4a4 4 0 0 1-4-4V16a4 4 0 0 1 4-4Z" fill="currentColor" opacity="0.12"/><path d="M20 26h24M20 34h16" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" opacity="0.3"/></svg>
                                </div>
                                <h3><?php esc_html_e( 'No messages', 'envara-ai-assistant' ); ?></h3>
                                <p><?php esc_html_e( 'Messages from the team will be shown here.', 'envara-ai-assistant' ); ?></p>
                                <button type="button" class="envara-ai-start-chat"><?php esc_html_e( 'Chat with us', 'envara-ai-assistant' ); ?></button>
                            </div>
                        </section>
                        <section class="envara-ai-view envara-ai-view-help" data-view="help" aria-label="<?php esc_attr_e( 'Help', 'envara-ai-assistant' ); ?>">
                            <div class="envara-ai-help-content">
                                <h3><?php esc_html_e( 'Need help?', 'envara-ai-assistant' ); ?></h3>
                                <div class="envara-ai-help-text"></div>
                            </div>
                        </section>
                        <section class="envara-ai-view envara-ai-view-conversation" data-view="conversation" aria-label="<?php esc_attr_e( 'Conversation', 'envara-ai-assistant' ); ?>">
                            <header class="envara-ai-conversation-header">
                                <button type="button" class="envara-ai-back" data-target="messages" aria-label="<?php esc_attr_e( 'Back', 'envara-ai-assistant' ); ?>">
                                    <svg viewBox="0 0 24 24" focusable="false" role="img"><path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12l4.58-4.59Z" fill="currentColor"/></svg>
                                </button>
                                <div class="envara-ai-conversation-meta">
                                    <span class="envara-ai-conversation-title"><?php echo esc_html( $chatbot['name'] ); ?></span>
                                    <span class="envara-ai-conversation-status"><?php echo esc_html( $conversation_hint ); ?></span>
                                </div>
                            </header>
                            <div class="envara-ai-messages" role="log" aria-live="polite"></div>
                            <form class="envara-ai-chat" hidden>
                                <div class="envara-ai-input">
                                    <label class="screen-reader-text" for="envara-ai-message-<?php echo esc_attr( $chatbot['id'] ); ?>"><?php esc_html_e( 'Type your message', 'envara-ai-assistant' ); ?></label>
                                    <textarea id="envara-ai-message-<?php echo esc_attr( $chatbot['id'] ); ?>" name="message" rows="2" placeholder="<?php esc_attr_e( 'Type your message...', 'envara-ai-assistant' ); ?>" required></textarea>
                                    <button type="submit" class="envara-ai-send">
                                        <span class="screen-reader-text"><?php esc_html_e( 'Send message', 'envara-ai-assistant' ); ?></span>
                                        <svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M3.4 20.4 21 12 3.4 3.6 3 10l11 2-11 2z" fill="currentColor"/></svg>
                                    </button>
                                </div>
                            </form>
                        </section>
                    </div>
                    <nav class="envara-ai-nav" aria-label="<?php esc_attr_e( 'Chat navigation', 'envara-ai-assistant' ); ?>">
                        <button type="button" class="is-active" data-target="home">
                            <span class="envara-ai-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false" role="img"><path d="M11.29 2.71a1 1 0 0 1 1.42 0l8 8a1 1 0 0 1-1.42 1.42L19 11.83V19a3 3 0 0 1-3 3h-8a3 3 0 0 1-3-3v-7.17l-.29.3A1 1 0 0 1 3.3 10.7l8-8Z" fill="currentColor"/></svg></span>
                            <span><?php esc_html_e( 'Home', 'envara-ai-assistant' ); ?></span>
                        </button>
                        <button type="button" data-target="messages">
                            <span class="envara-ai-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false" role="img"><path d="M4 5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3H9.41l-2.7 2.7A1 1 0 0 1 5 19.59V17a3 3 0 0 1-3-3Z" fill="currentColor"/></svg></span>
                            <span><?php esc_html_e( 'Messages', 'envara-ai-assistant' ); ?></span>
                        </button>
                        <button type="button" data-target="help">
                            <span class="envara-ai-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false" role="img"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm.25 15.92a1.33 1.33 0 1 1 1.33-1.33 1.33 1.33 0 0 1-1.33 1.33Zm2.11-6.76a3 3 0 0 1-1.3 1.58 4.42 4.42 0 0 0-.81.63 1.1 1.1 0 0 0-.23.73v.34a1 1 0 0 1-2 0v-.39a2.88 2.88 0 0 1 .62-1.81 3.85 3.85 0 0 1 1.05-.82 1.33 1.33 0 0 0 .6-.76 1.13 1.13 0 0 0-.34-1.13 1.62 1.62 0 0 0-1.12-.34 2.06 2.06 0 0 0-1.52.57 1 1 0 1 1-1.38-1.45 4 4 0 0 1 2.72-1 3.54 3.54 0 0 1 2.41.82 3 3 0 0 1 .9 3.31Z" fill="currentColor"/></svg></span>
                            <span><?php esc_html_e( 'Help', 'envara-ai-assistant' ); ?></span>
                        </button>
                    </nav>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Generates a readable initial from a string.
     *
     * @param string $value Raw string.
     *
     * @return string
     */
    private static function initial( string $value ): string {
        $trimmed = trim( wp_strip_all_tags( $value ) );
        if ( '' === $trimmed ) {
            return 'A';
        }

        $character = function_exists( 'mb_substr' ) ? mb_substr( $trimmed, 0, 1 ) : substr( $trimmed, 0, 1 );

        return strtoupper( $character );
    }

    /**
     * Returns a lighter tint for a hex color.
     *
     * @param string $hex      Base hex color.
     * @param float  $percent  Percentage to lighten.
     *
     * @return string
     */
    private static function tint_color( string $hex, float $percent ): string {
        $hex = ltrim( $hex, '#' );

        if ( 3 === strlen( $hex ) ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if ( ! preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
            return '#ad8cff';
        }

        $percent = max( -100, min( 100, $percent ) );

        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        $mix = function ( int $component ) use ( $percent ): int {
            if ( $percent >= 0 ) {
                return (int) round( $component + ( 255 - $component ) * ( $percent / 100 ) );
            }

            return (int) round( $component * ( 1 + $percent / 100 ) );
        };

        $r = max( 0, min( 255, $mix( $r ) ) );
        $g = max( 0, min( 255, $mix( $g ) ) );
        $b = max( 0, min( 255, $mix( $b ) ) );

        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }
}
