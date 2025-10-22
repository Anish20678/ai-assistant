<?php
/**
 * Chatbot data handler.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant;

use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles chatbot CRUD operations.
 */
class Chatbot {
    /**
     * Retrieves a list of chatbots with optional status filter.
     *
     * @param string|null $status Status filter.
     *
     * @return array
     */
    public static function all( ?string $status = null ): array {
        global $wpdb;
        $table = table_name( 'chatbots' );

        if ( $status ) {
            return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s", $status ), ARRAY_A );
        }

        return (array) $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A );
    }

    /**
     * Inserts or updates a chatbot.
     *
     * @param array $data Chatbot data.
     *
     * @return int Chatbot ID.
     */
    public static function upsert( array $data ): int {
        global $wpdb;
        $table = table_name( 'chatbots' );

        $defaults = [
            'name'            => '',
            'model'           => 'gpt-3.5-turbo',
            'system_prompt'   => '',
            'welcome_message' => '',
            'visibility'      => 'sitewide',
            'visibility_data' => '',
            'status'          => 'active',
            'settings'        => '{}',
            'created_at'      => current_time( 'mysql' ),
            'updated_at'      => current_time( 'mysql' ),
        ];

        $data = wp_parse_args( $data, $defaults );

        $chatbot_id = isset( $data['id'] ) ? (int) $data['id'] : 0;
        unset( $data['id'] );

        if ( $chatbot_id ) {
            $data['updated_at'] = current_time( 'mysql' );
            $wpdb->update( $table, $data, [ 'id' => $chatbot_id ] );

            return $chatbot_id;
        }

        $wpdb->insert( $table, $data );

        return (int) $wpdb->insert_id;
    }

    /**
     * Deletes a chatbot and related data.
     *
     * @param int $chatbot_id Chatbot id.
     */
    public static function delete( int $chatbot_id ): void {
        global $wpdb;

        $wpdb->delete( table_name( 'chatbots' ), [ 'id' => $chatbot_id ] );
        $wpdb->delete( table_name( 'forms' ), [ 'chatbot_id' => $chatbot_id ] );
        $wpdb->delete( table_name( 'training' ), [ 'chatbot_id' => $chatbot_id ] );
    }

    /**
     * Retrieves chatbot form schema.
     *
     * @param int $chatbot_id Chatbot id.
     *
     * @return array
     */
    public static function get_form_schema( int $chatbot_id ): array {
        global $wpdb;
        $table = table_name( 'forms' );

        $schema = $wpdb->get_var( $wpdb->prepare( "SELECT form_json FROM {$table} WHERE chatbot_id = %d", $chatbot_id ) );

        return $schema ? safe_json_decode( $schema ) : [];
    }

    /**
     * Updates chatbot form schema.
     *
     * @param int   $chatbot_id Chatbot id.
     * @param array $schema     Schema data.
     */
    public static function update_form_schema( int $chatbot_id, array $schema ): void {
        global $wpdb;
        $table = table_name( 'forms' );

        $sanitized = [];
        foreach ( $schema as $field ) {
            $sanitized[] = [
                'label'    => sanitize_text_field( $field['label'] ?? '' ),
                'type'     => sanitize_text_field( $field['type'] ?? 'text' ),
                'required' => ! empty( $field['required'] ),
                'options'  => sanitize_text_field( $field['options'] ?? '' ),
            ];
        }

        $data = [
            'chatbot_id' => $chatbot_id,
            'form_json'  => wp_json_encode( $sanitized ),
        ];

        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT chatbot_id FROM {$table} WHERE chatbot_id = %d", $chatbot_id ) );

        if ( $exists ) {
            $wpdb->update( $table, $data, [ 'chatbot_id' => $chatbot_id ] );
        } else {
            $wpdb->insert( $table, $data );
        }
    }
}
