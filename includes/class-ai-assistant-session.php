<?php
/**
 * Session data handler.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant;

use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Session manager class.
 */
class Session {
    /**
     * Creates a new session record.
     *
     * @param array $data Session data.
     *
     * @return string Session ID.
     */
    public static function create( array $data ): string {
        global $wpdb;
        $table = table_name( 'sessions' );

        $defaults = [
            'session_id' => wp_generate_uuid4(),
            'chatbot_id' => 0,
            'visitor_id' => '',
            'visitor_ip' => '',
            'page_url'   => '',
            'start_time' => current_time( 'mysql' ),
            'status'     => 'active',
        ];

        $data = wp_parse_args( $data, $defaults );

        if ( empty( $data['session_id'] ) ) {
            $data['session_id'] = wp_generate_uuid4();
        }

        $wpdb->insert( $table, $data );

        return $data['session_id'];
    }

    /**
     * Updates session.
     *
     * @param string $session_id Session id.
     * @param array  $data       Data to update.
     */
    public static function update( string $session_id, array $data ): void {
        global $wpdb;
        $table = table_name( 'sessions' );

        $wpdb->update( $table, $data, [ 'session_id' => $session_id ] );
    }

    /**
     * Retrieves session by id.
     *
     * @param string $session_id Session id.
     *
     * @return array|null
     */
    public static function get( string $session_id ): ?array {
        global $wpdb;
        $table = table_name( 'sessions' );

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE session_id = %s", $session_id ), ARRAY_A );

        return $row ?: null;
    }

    /**
     * Logs a message.
     *
     * @param string $session_id Session id.
     * @param string $sender     Sender identifier.
     * @param string $message    Message text.
     */
    public static function log_message( string $session_id, string $sender, string $message ): void {
        $public_settings = Settings::instance()->get_public_settings();
        if ( empty( $public_settings['logging'] ) ) {
            return;
        }

        global $wpdb;
        $table = table_name( 'messages' );

        $wpdb->insert(
            $table,
            [
                'session_id' => $session_id,
                'sender'     => $sender,
                'message'    => wp_kses_post( $message ),
                'timestamp'  => current_time( 'mysql' ),
            ]
        );
    }

    /**
     * Retrieves conversation transcripts.
     *
     * @param string $session_id Session id.
     *
     * @return array
     */
    public static function get_messages( string $session_id ): array {
        global $wpdb;
        $table = table_name( 'messages' );

        return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE session_id = %s ORDER BY timestamp ASC", $session_id ), ARRAY_A );
    }
}
