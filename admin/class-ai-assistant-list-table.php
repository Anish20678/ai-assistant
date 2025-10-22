<?php
/**
 * Chatbot list table implementation.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant\Admin;

use Envara\AI_Assistant\Chatbot;
use WP_List_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( '\WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Displays chatbots in admin list.
 */
class Chatbots_List_Table extends WP_List_Table {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            [
                'plural'   => 'chatbots',
                'singular' => 'chatbot',
                'ajax'     => false,
            ]
        );
    }

    /**
     * Prepares items.
     */
    public function prepare_items(): void {
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = [];
        $this->_column_headers = [ $columns, $hidden, $sortable ];

        $items = Chatbot::all();

        $this->items = $items;
    }

    /**
     * Returns columns definition.
     *
     * @return array
     */
    public function get_columns(): array {
        return [
            'name'        => __( 'Chatbot Name', 'envara-ai-assistant' ),
            'visibility'  => __( 'Visibility', 'envara-ai-assistant' ),
            'shortcode'   => __( 'Shortcode', 'envara-ai-assistant' ),
            'status'      => __( 'Status', 'envara-ai-assistant' ),
            'actions'     => __( 'Actions', 'envara-ai-assistant' ),
        ];
    }

    /**
     * Default column display.
     *
     * @param array  $item        Current item.
     * @param string $column_name Column name.
     *
     * @return string
     */
    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'name':
                return esc_html( $item['name'] );
            case 'visibility':
                return esc_html( ucfirst( $item['visibility'] ) );
            case 'shortcode':
                return sprintf( '<code>[ai_assistant id="%d"]</code>', (int) $item['id'] );
            case 'status':
                return esc_html( ucfirst( $item['status'] ) );
            case 'actions':
                $edit_url   = add_query_arg( [ 'page' => 'envara-ai-assistant-chatbots', 'action' => 'edit', 'id' => $item['id'] ], admin_url( 'admin.php' ) );
                $train_url  = add_query_arg( [ 'page' => 'envara-ai-assistant-training', 'chatbot_id' => $item['id'] ], admin_url( 'admin.php' ) );
                $view_url   = add_query_arg( [ 'page' => 'envara-ai-assistant-conversations', 'chatbot_id' => $item['id'] ], admin_url( 'admin.php' ) );
                $delete_url = wp_nonce_url( add_query_arg( [ 'page' => 'envara-ai-assistant-chatbots', 'action' => 'delete', 'id' => $item['id'] ], admin_url( 'admin.php' ) ), 'envara_ai_assistant_delete_chatbot_' . $item['id'] );

                $actions = [
                    sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'envara-ai-assistant' ) ),
                    sprintf( '<a href="%s">%s</a>', esc_url( $train_url ), esc_html__( 'Train', 'envara-ai-assistant' ) ),
                    sprintf( '<a href="%s">%s</a>', esc_url( $view_url ), esc_html__( 'View Conversations', 'envara-ai-assistant' ) ),
                    sprintf( '<a href="%s" class="delete">%s</a>', esc_url( $delete_url ), esc_html__( 'Delete', 'envara-ai-assistant' ) ),
                ];

                return implode( ' | ', $actions );
        }

        return '';
    }
}
