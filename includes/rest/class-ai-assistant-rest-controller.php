<?php
/**
 * REST controller.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant\Rest;

use Envara\AI_Assistant\Emailer;
use Envara\AI_Assistant\Session;
use function Envara\AI_Assistant\get_chatbot;
use function Envara\AI_Assistant\safe_json_decode;
use function Envara\AI_Assistant\table_name;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST controller class.
 */
class Rest_Controller {
    /**
     * Singleton instance.
     *
     * @var Rest_Controller|null
     */
    private static $instance = null;

    /**
     * Private constructor.
     */
    private function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Returns singleton instance.
     *
     * @return Rest_Controller
     */
    public static function instance(): Rest_Controller {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registers REST routes.
     */
    public function register_routes(): void {
        register_rest_route(
            'ai-assistant/v1',
            '/chat',
            [
                'methods'             => 'POST',
                'permission_callback' => '__return_true',
                'callback'            => [ $this, 'handle_chat' ],
            ]
        );

        register_rest_route(
            'ai-assistant/v1',
            '/session',
            [
                'methods'             => 'POST',
                'permission_callback' => '__return_true',
                'callback'            => [ $this, 'create_session' ],
            ]
        );
    }

    /**
     * Creates a chat session.
     *
     * @param \WP_REST_Request $request Request.
     *
     * @return \WP_REST_Response
     */
    public function create_session( \WP_REST_Request $request ) {
        $chatbot_id = (int) $request->get_param( 'chatbot_id' );
        $form       = $request->get_param( 'form' ) ?: [];
        if ( is_array( $form ) ) {
            $form = array_map( 'sanitize_text_field', $form );
        } else {
            $form = [];
        }
        $page       = esc_url_raw( $request->get_param( 'page' ) );

        $session_id = Session::create(
            [
                'chatbot_id' => $chatbot_id,
                'visitor_id' => sanitize_text_field( $request->get_param( 'visitor_id' ) ),
                'visitor_ip' => $this->get_visitor_ip(),
                'page_url'   => $page,
            ]
        );

        $chatbot = get_chatbot( $chatbot_id );

        if ( $chatbot ) {
            Emailer::send_new_chat_notification(
                Session::get( $session_id ),
                $chatbot,
                is_array( $form ) ? $form : []
            );
        }

        return rest_ensure_response(
            [
                'session_id' => $session_id,
                'welcome'    => $chatbot['welcome_message'] ?? '',
            ]
        );
    }

    /**
     * Handles chat messages.
     *
     * @param \WP_REST_Request $request Request.
     *
     * @return \WP_REST_Response
     */
    public function handle_chat( \WP_REST_Request $request ) {
        $session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
        $message    = wp_kses_post( $request->get_param( 'message' ) );
        $chatbot_id = (int) $request->get_param( 'chatbot_id' );

        if ( empty( $session_id ) || empty( $message ) ) {
            return new \WP_Error( 'invalid_request', __( 'Session or message missing.', 'envara-ai-assistant' ), [ 'status' => 400 ] );
        }

        Session::log_message( $session_id, 'user', $message );

        $chatbot = get_chatbot( $chatbot_id );

        if ( ! $chatbot ) {
            return new \WP_Error( 'chatbot_not_found', __( 'Chatbot not found.', 'envara-ai-assistant' ), [ 'status' => 404 ] );
        }

        $response = $this->query_openai( $chatbot, $message, $session_id );

        Session::log_message( $session_id, 'assistant', $response );

        return rest_ensure_response(
            [
                'response' => $response,
            ]
        );
    }

    /**
     * Queries OpenAI API.
     *
     * @param array  $chatbot    Chatbot settings.
     * @param string $message    User message.
     * @param string $session_id Session id.
     *
     * @return string
     */
    private function query_openai( array $chatbot, string $message, string $session_id ): string {
        $settings   = \get_option( 'envara_ai_assistant_settings', [] );
        $api_key    = $settings['openai_api_key'] ?? '';
        $temperature = $settings['temperature'] ?? 1.0;
        $max_tokens  = $settings['max_tokens'] ?? 512;

        if ( empty( $api_key ) ) {
            return __( 'OpenAI API key is not configured.', 'envara-ai-assistant' );
        }

        $endpoint = 'https://api.openai.com/v1/chat/completions';

        $body = [
            'model'       => $chatbot['model'] ?: ( $settings['default_model'] ?? 'gpt-3.5-turbo' ),
            'messages'    => [
                [ 'role' => 'system', 'content' => $chatbot['system_prompt'] ],
                [ 'role' => 'user', 'content' => $message ],
            ],
            'temperature' => (float) $temperature,
            'max_tokens'  => (int) $max_tokens,
        ];

        $response = wp_remote_post(
            $endpoint,
            [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
                ],
                'timeout' => 20,
                'body'    => wp_json_encode( $body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            return __( 'Unable to contact OpenAI API.', 'envara-ai-assistant' );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            return wp_kses_post( $data['choices'][0]['message']['content'] );
        }

        return __( 'No response from AI.', 'envara-ai-assistant' );
    }

    /**
     * Retrieves visitor IP address.
     *
     * @return string
     */
    private function get_visitor_ip(): string {
        $options = \get_option( 'envara_ai_assistant_settings', [] );
        if ( empty( $options['ip_tracking'] ) ) {
            return '';
        }

        foreach ( [ 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ] as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip_list = explode( ',', wp_unslash( $_SERVER[ $key ] ) );
                return trim( $ip_list[0] );
            }
        }

        return '';
    }
}
