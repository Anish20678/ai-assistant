<?php
/**
 * Email notifications helper.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Emailer class responsible for sending notifications.
 */
class Emailer {
    /**
     * Sends notification email when a new chat session starts.
     *
     * @param array $session Session data.
     * @param array $chatbot Chatbot data.
     * @param array $form_data Submitted form data.
     */
    public static function send_new_chat_notification( array $session, array $chatbot, array $form_data ): void {
        $settings = \get_option( 'envara_ai_assistant_settings', [] );
        $to       = $settings['notification_email'] ?? \get_option( 'admin_email' );

        if ( empty( $to ) ) {
            return;
        }

        $subject = sprintf( __( 'New chat started on %s', 'envara-ai-assistant' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );

        $message  = '<h2>' . esc_html( $chatbot['name'] ?? __( 'Chatbot', 'envara-ai-assistant' ) ) . '</h2>';
        $message .= '<p>' . esc_html__( 'A new chat session has started on your website.', 'envara-ai-assistant' ) . '</p>';
        $message .= '<p><strong>' . esc_html__( 'Visitor:', 'envara-ai-assistant' ) . '</strong> ' . esc_html( $session['visitor_id'] ?? __( 'Unknown', 'envara-ai-assistant' ) ) . '</p>';
        $message .= '<p><strong>' . esc_html__( 'Page:', 'envara-ai-assistant' ) . '</strong> ' . esc_html( $session['page_url'] ?? '' ) . '</p>';
        $message .= '<p><strong>' . esc_html__( 'Start Time:', 'envara-ai-assistant' ) . '</strong> ' . esc_html( format_datetime( $session['start_time'] ?? '' ) ) . '</p>';

        if ( ! empty( $form_data ) ) {
            $message .= '<h3>' . esc_html__( 'Pre-chat Form Data', 'envara-ai-assistant' ) . '</h3><ul>';
            foreach ( $form_data as $label => $value ) {
                $message .= '<li><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( is_array( $value ) ? implode( ', ', $value ) : $value ) . '</li>';
            }
            $message .= '</ul>';
        }

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        wp_mail( $to, $subject, $message, $headers );
    }
}
