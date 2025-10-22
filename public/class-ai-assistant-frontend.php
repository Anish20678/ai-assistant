<?php
/**
 * Frontend functionality.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant\Frontend;

use Envara\AI_Assistant\Chatbot;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles frontend widget rendering.
 */
class Frontend {
    /**
     * Singleton instance.
     *
     * @var Frontend|null
     */
    private static $instance = null;

    /**
     * Private constructor.
     */
    private function __construct() {
        add_action( 'wp_footer', [ $this, 'render_chatbots' ] );
        Shortcode::register();
        Widget::register();
    }

    /**
     * Returns singleton instance.
     *
     * @return Frontend
     */
    public static function instance(): Frontend {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Renders chatbots configured for sitewide display.
     */
    public function render_chatbots(): void {
        $chatbots = Chatbot::all( 'active' );
        if ( empty( $chatbots ) ) {
            return;
        }

        foreach ( $chatbots as $chatbot ) {
            if ( 'sitewide' !== $chatbot['visibility'] ) {
                if ( 'specific' === $chatbot['visibility'] ) {
                    $ids = array_filter( array_map( 'absint', array_map( 'trim', explode( ',', $chatbot['visibility_data'] ?? '' ) ) ) );
                    if ( empty( $ids ) || ! is_page( $ids ) ) {
                        continue;
                    }
                } else {
                    continue;
                }
            }

            echo Shortcode::render( [ 'id' => $chatbot['id'] ] );
        }
    }
}
