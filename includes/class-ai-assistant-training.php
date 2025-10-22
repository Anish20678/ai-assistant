<?php
/**
 * Training data handler.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant;

use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages training data records.
 */
class Training {
    /**
     * Stores uploaded training source.
     *
     * @param int    $chatbot_id Chatbot id.
     * @param string $type       Source type.
     * @param string $path       Source path or content.
     */
    public static function add_source( int $chatbot_id, string $type, string $path ): void {
        global $wpdb;
        $table = table_name( 'training' );

        switch ( $type ) {
            case 'url':
                $path = esc_url_raw( $path );
                break;
            case 'text':
                $path = wp_kses_post( $path );
                break;
            default:
                $path = sanitize_text_field( $path );
                break;
        }

        $wpdb->insert(
            $table,
            [
                'chatbot_id'  => $chatbot_id,
                'source_type' => $type,
                'source_path' => $path,
                'created_at'  => current_time( 'mysql' ),
            ]
        );
    }

    /**
     * Retrieves training sources for chatbot.
     *
     * @param int $chatbot_id Chatbot id.
     *
     * @return array
     */
    public static function get_sources( int $chatbot_id ): array {
        global $wpdb;
        $table = table_name( 'training' );

        return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE chatbot_id = %d ORDER BY created_at DESC", $chatbot_id ), ARRAY_A );
    }

    /**
     * Deletes a source.
     *
     * @param int $id Source id.
     */
    public static function delete_source( int $id ): void {
        global $wpdb;
        $table = table_name( 'training' );

        $wpdb->delete( $table, [ 'id' => $id ] );
    }
}
