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

        ob_start();
        ?>
        <div class="envara-ai-widget" data-chatbot="<?php echo esc_attr( wp_json_encode( [
            'id'              => $chatbot['id'],
            'name'            => $chatbot['name'],
            'welcomeMessage'  => $chatbot['welcome_message'],
            'systemPrompt'    => $chatbot['system_prompt'],
            'model'           => $chatbot['model'],
            'settings'        => $settings,
            'form'            => $form,
        ] ) ); ?>">
            <button class="envara-ai-launch" type="button" aria-expanded="false">
                <span class="envara-ai-launch-label"><?php echo esc_html( $chatbot['name'] ); ?></span>
            </button>
            <div class="envara-ai-window" hidden>
                <header class="envara-ai-header">
                    <strong><?php echo esc_html( $chatbot['name'] ); ?></strong>
                    <button type="button" class="envara-ai-close" aria-label="<?php esc_attr_e( 'Close chat', 'envara-ai-assistant' ); ?>">&times;</button>
                </header>
                <div class="envara-ai-body">
                    <div class="envara-ai-messages"></div>
                    <form class="envara-ai-form" hidden></form>
                    <form class="envara-ai-chat" hidden>
                        <div class="envara-ai-input">
                            <textarea name="message" rows="2" placeholder="<?php esc_attr_e( 'Type your message...', 'envara-ai-assistant' ); ?>" required></textarea>
                            <button type="submit" class="button button-primary"><?php esc_html_e( 'Send', 'envara-ai-assistant' ); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
