<?php
/**
 * Helper functions for AI Assistant plugin.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant;

use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns plugin database table name with prefix.
 *
 * @param string $table Table slug.
 *
 * @return string
 */
function table_name( string $table ): string {
    global $wpdb;

    return $wpdb->prefix . 'ai_assistant_' . $table;
}

/**
 * Retrieves all chatbots.
 *
 * @return array
 */
function get_chatbots(): array {
    global $wpdb;

    $table = table_name( 'chatbots' );

    return (array) $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A );
}

/**
 * Retrieves chat bot by id.
 *
 * @param int $id Chatbot id.
 *
 * @return array|null
 */
function get_chatbot( int $id ): ?array {
    global $wpdb;
    $table = table_name( 'chatbots' );

    $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );

    return $row ?: null;
}

/**
 * Sanitizes recursive array data.
 *
 * @param mixed $data Data to sanitize.
 *
 * @return mixed
 */
function deep_sanitize( $data ) {
    if ( is_array( $data ) ) {
        foreach ( $data as $key => $value ) {
            $data[ sanitize_key( $key ) ] = deep_sanitize( $value );
        }

        return $data;
    }

    if ( is_string( $data ) ) {
        return wp_kses_post( wp_unslash( $data ) );
    }

    return $data;
}

/**
 * Converts JSON string to array safely.
 *
 * @param string $json JSON string.
 *
 * @return array
 */
function safe_json_decode( string $json ): array {
    $decoded = json_decode( $json, true );

    return is_array( $decoded ) ? $decoded : [];
}

/**
 * Formats datetime for display.
 *
 * @param string|null $time Time string.
 *
 * @return string
 */
function format_datetime( ?string $time ): string {
    if ( empty( $time ) ) {
        return __( 'N/A', 'envara-ai-assistant' );
    }

    return wp_date( \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' ), strtotime( $time ) );
}

/**
 * Returns human readable duration.
 *
 * @param int $seconds Seconds.
 *
 * @return string
 */
function human_duration( int $seconds ): string {
    $minutes = floor( $seconds / 60 );
    $seconds = $seconds % 60;

    return sprintf( _n( '%s minute', '%s minutes', $minutes, 'envara-ai-assistant' ), $minutes ) . ' ' . sprintf( _n( '%s second', '%s seconds', $seconds, 'envara-ai-assistant' ), $seconds );
}
