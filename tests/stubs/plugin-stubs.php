<?php
/**
 * Plugin-specific stubs for tests.
 */

declare(strict_types=1);

namespace Envara\AI_Assistant;

use function \current_time;
use function \wp_kses_post;

class Session {
    /** @var array<string, array<int, array<string, string>>> */
    private static $messages = [];

    public static function create( array $data ): string {
        $session_id = $data['session_id'] ?? uniqid( 'session-', true );
        self::$messages[ $session_id ] = [];

        return $session_id;
    }

    public static function update( string $session_id, array $data ): void {}

    public static function get( string $session_id ): ?array {
        return null;
    }

    public static function log_message( string $session_id, string $sender, string $message ): void {
        if ( ! isset( self::$messages[ $session_id ] ) ) {
            self::$messages[ $session_id ] = [];
        }

        self::$messages[ $session_id ][] = [
            'sender'    => $sender,
            'message'   => wp_kses_post( $message ),
            'timestamp' => current_time( 'mysql' ),
        ];
    }

    public static function get_messages( string $session_id ): array {
        return self::$messages[ $session_id ] ?? [];
    }

    public static function reset(): void {
        self::$messages = [];
    }

    public static function seed_messages( string $session_id, array $messages ): void {
        self::$messages[ $session_id ] = $messages;
    }
}

class Training {
    /** @var array<int, array<int, array<string, mixed>>> */
    private static $sources = [];

    public static function add_source( int $chatbot_id, string $type, string $path ): void {}

    public static function get_sources( int $chatbot_id ): array {
        return self::$sources[ $chatbot_id ] ?? [];
    }

    public static function set_sources( int $chatbot_id, array $sources ): void {
        self::$sources[ $chatbot_id ] = $sources;
    }

    public static function reset(): void {
        self::$sources = [];
    }
}

class Emailer {
    public static function send_new_chat_notification( ...$args ): void {}
}

function table_name( string $table ): string {
    return 'wp_ai_assistant_' . $table;
}

function get_chatbot( int $id ): ?array {
    return $GLOBALS['wp_stub_chatbots'][ $id ] ?? null;
}

function safe_json_decode( string $json ): array {
    $decoded = json_decode( $json, true );

    return is_array( $decoded ) ? $decoded : [];
}
