<?php
/**
 * REST controller.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant\Rest;

use Envara\AI_Assistant\Emailer;
use Envara\AI_Assistant\Session;
use Envara\AI_Assistant\Training;
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
            'messages'    => $this->build_request_messages( $chatbot, $message, $session_id, (int) $max_tokens ),
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
     * Builds the messages payload for the OpenAI request.
     *
     * @param array  $chatbot    Chatbot settings.
     * @param string $message    Current user message.
     * @param string $session_id Session id.
     * @param int    $max_tokens Max completion tokens.
     *
     * @return array
     */
    private function build_request_messages( array $chatbot, string $message, string $session_id, int $max_tokens ): array {
        $messages      = [];
        $system_prompt = $chatbot['system_prompt'] ?? '';
        $context       = $this->build_context_block( $chatbot, $message, $max_tokens );

        if ( $context ) {
            $system_prompt = trim( $system_prompt . "\n\n" . $context );
        }

        if ( $system_prompt ) {
            $messages[] = [
                'role'    => 'system',
                'content' => $this->truncate_to_length( $system_prompt, $this->max_context_length( $max_tokens ) ),
            ];
        }

        $history = Session::get_messages( $session_id );

        if ( ! empty( $history ) ) {
            foreach ( $history as $entry ) {
                $role = in_array( $entry['sender'], [ 'user', 'assistant', 'system' ], true ) ? $entry['sender'] : 'user';
                $messages[] = [
                    'role'    => $role,
                    'content' => $this->truncate_to_length( wp_kses_post( $entry['message'] ?? '' ), $this->max_context_length( $max_tokens ) ),
                ];
            }
        } else {
            $messages[] = [
                'role'    => 'user',
                'content' => $this->truncate_to_length( $message, $this->max_context_length( $max_tokens ) ),
            ];
        }

        return $messages;
    }

    /**
     * Builds additional context block derived from training sources.
     *
     * @param array  $chatbot    Chatbot configuration.
     * @param string $message    User message.
     * @param int    $max_tokens Max completion tokens.
     *
     * @return string
     */
    private function build_context_block( array $chatbot, string $message, int $max_tokens ): string {
        if ( empty( $chatbot['id'] ) ) {
            return '';
        }

        $sources = Training::get_sources( (int) $chatbot['id'] );
        if ( empty( $sources ) ) {
            return '';
        }

        $keywords          = $this->extract_keywords( $message );
        $max_context_chars = $this->max_context_length( $max_tokens );
        $accumulated       = '';

        foreach ( $sources as $source ) {
            $content = $this->retrieve_source_content( $source );

            if ( '' === $content ) {
                continue;
            }

            $snippet = $this->summarize_content( $content, $keywords, (int) max( 256, $max_context_chars / 4 ) );

            if ( '' === $snippet ) {
                continue;
            }

            $label    = ucfirst( $source['source_type'] ?? 'context' );
            $labeled  = '[' . $label . '] ' . $snippet;
            $combined = '' === $accumulated ? $labeled : $accumulated . "\n\n" . $labeled;

            if ( $this->safe_strlen( $combined ) > $max_context_chars ) {
                $available = $max_context_chars - $this->safe_strlen( $accumulated );
                if ( $available <= 0 ) {
                    break;
                }

                $labeled  = $this->truncate_to_length( $labeled, $available );
                $combined = '' === $accumulated ? $labeled : $accumulated . "\n\n" . $labeled;
            }

            $accumulated = $combined;

            if ( $this->safe_strlen( $accumulated ) >= $max_context_chars ) {
                break;
            }
        }

        if ( '' === $accumulated ) {
            return '';
        }

        return 'Relevant context for this conversation:' . "\n" . $accumulated;
    }

    /**
     * Retrieves raw content from a training source.
     *
     * @param array $source Training source row.
     *
     * @return string
     */
    private function retrieve_source_content( array $source ): string {
        $type = $source['source_type'] ?? '';
        $path = (string) ( $source['source_path'] ?? '' );

        switch ( $type ) {
            case 'text':
                return wp_strip_all_tags( $path );
            case 'url':
                $response = wp_remote_get( $path );
                if ( is_wp_error( $response ) ) {
                    return '';
                }

                return wp_strip_all_tags( wp_remote_retrieve_body( $response ) );
            case 'file':
                if ( ! empty( $path ) && is_readable( $path ) ) {
                    $content = file_get_contents( $path );
                    if ( false !== $content ) {
                        return wp_strip_all_tags( $content );
                    }
                }

                return '';
            default:
                return wp_strip_all_tags( $path );
        }
    }

    /**
     * Extracts distinct lowercase keywords from a message.
     *
     * @param string $message User message.
     *
     * @return array
     */
    private function extract_keywords( string $message ): array {
        $words = preg_split( '/[^\p{L}\p{N}]+/u', strtolower( $message ), -1, PREG_SPLIT_NO_EMPTY );

        $filtered = array_filter(
            array_unique( $words ),
            static function ( string $word ): bool {
                return strlen( $word ) > 2;
            }
        );

        return array_values( $filtered );
    }

    /**
     * Summarizes content into a snippet respecting basic length guards.
     *
     * @param string $content  Source content.
     * @param array  $keywords Keywords to emphasize.
     * @param int    $limit    Character limit per snippet.
     *
     * @return string
     */
    private function summarize_content( string $content, array $keywords, int $limit ): string {
        $normalized = preg_replace( '/\s+/u', ' ', trim( $content ) );
        if ( '' === $normalized ) {
            return '';
        }

        $sentences = preg_split( '/(?<=[\.\?!])\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY );
        if ( empty( $sentences ) ) {
            $sentences = [ $normalized ];
        }

        $matches = [];
        foreach ( $sentences as $sentence ) {
            if ( empty( $keywords ) ) {
                $matches[] = $sentence;
                continue;
            }

            foreach ( $keywords as $keyword ) {
                if ( false !== stripos( $sentence, $keyword ) ) {
                    $matches[] = $sentence;
                    break;
                }
            }
        }

        if ( empty( $matches ) ) {
            $matches = array_slice( $sentences, 0, 3 );
        }

        $snippet = '';
        foreach ( $matches as $sentence ) {
            $candidate = '' === $snippet ? $sentence : $snippet . ' ' . $sentence;
            if ( $this->safe_strlen( $candidate ) > $limit ) {
                $available = $limit - $this->safe_strlen( $snippet );
                if ( $available <= 0 ) {
                    break;
                }

                $snippet = $snippet . ' ' . $this->truncate_to_length( $sentence, $available );
                break;
            }

            $snippet = $candidate;
        }

        return trim( $snippet );
    }

    /**
     * Returns an estimated character budget for prompts.
     *
     * @param int $max_tokens Response max tokens.
     *
     * @return int
     */
    private function max_context_length( int $max_tokens ): int {
        $tokens = max( 256, $max_tokens );

        return max( 1000, $tokens * 4 );
    }

    /**
     * Safely truncates content without breaking multibyte characters.
     *
     * @param string $content Content to trim.
     * @param int    $limit   Character limit.
     *
     * @return string
     */
    private function truncate_to_length( string $content, int $limit ): string {
        if ( $this->safe_strlen( $content ) <= $limit ) {
            return $content;
        }

        $truncated = $this->safe_substr( $content, 0, max( 0, $limit - 3 ) );

        return rtrim( $truncated ) . '...';
    }

    /**
     * Multibyte safe string length helper.
     *
     * @param string $string String to measure.
     *
     * @return int
     */
    private function safe_strlen( string $string ): int {
        return function_exists( 'mb_strlen' ) ? mb_strlen( $string ) : strlen( $string );
    }

    /**
     * Multibyte safe substring helper.
     *
     * @param string $string String to slice.
     * @param int    $start  Start offset.
     * @param int    $length Length.
     *
     * @return string
     */
    private function safe_substr( string $string, int $start, int $length ): string {
        return function_exists( 'mb_substr' ) ? mb_substr( $string, $start, $length ) : substr( $string, $start, $length );
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
