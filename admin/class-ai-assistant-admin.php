<?php
/**
 * Admin interface for AI Assistant plugin.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant\Admin;

use Envara\AI_Assistant\Chatbot;
use Envara\AI_Assistant\Session;
use Envara\AI_Assistant\Training;
use function Envara\AI_Assistant\deep_sanitize;
use function Envara\AI_Assistant\format_datetime;
use function Envara\AI_Assistant\get_chatbot;
use function Envara\AI_Assistant\get_chatbots;
use function Envara\AI_Assistant\table_name;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin class.
 */
class Admin {
    /**
     * Singleton instance.
     *
     * @var Admin|null
     */
    private static $instance = null;

    /**
     * Private constructor.
     */
    private function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_post_envara_ai_assistant_save_chatbot', [ $this, 'handle_save_chatbot' ] );
        add_action( 'admin_post_envara_ai_assistant_delete_chatbot', [ $this, 'handle_delete_chatbot' ] );
        add_action( 'admin_post_envara_ai_assistant_save_training', [ $this, 'handle_save_training' ] );
        add_action( 'admin_post_envara_ai_assistant_delete_source', [ $this, 'handle_delete_source' ] );
        add_action( 'admin_post_envara_ai_assistant_export_conversation', [ $this, 'handle_export_conversation' ] );
    }

    /**
     * Returns singleton instance.
     *
     * @return Admin
     */
    public static function instance(): Admin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registers admin menus.
     */
    public function register_menus(): void {
        add_menu_page(
            __( 'AI Assistant', 'envara-ai-assistant' ),
            __( 'AI Assistant', 'envara-ai-assistant' ),
            'manage_options',
            'envara-ai-assistant',
            [ $this, 'render_dashboard' ],
            'dashicons-format-chat'
        );

        add_submenu_page( 'envara-ai-assistant', __( 'Dashboard', 'envara-ai-assistant' ), __( 'Dashboard', 'envara-ai-assistant' ), 'manage_options', 'envara-ai-assistant', [ $this, 'render_dashboard' ] );
        add_submenu_page( 'envara-ai-assistant', __( 'Chatbots', 'envara-ai-assistant' ), __( 'Chatbots', 'envara-ai-assistant' ), 'manage_options', 'envara-ai-assistant-chatbots', [ $this, 'render_chatbots' ] );
        add_submenu_page( 'envara-ai-assistant', __( 'Training', 'envara-ai-assistant' ), __( 'Training', 'envara-ai-assistant' ), 'manage_options', 'envara-ai-assistant-training', [ $this, 'render_training' ] );
        add_submenu_page( 'envara-ai-assistant', __( 'Conversations', 'envara-ai-assistant' ), __( 'Conversations', 'envara-ai-assistant' ), 'manage_options', 'envara-ai-assistant-conversations', [ $this, 'render_conversations' ] );
        add_submenu_page( 'envara-ai-assistant', __( 'Customization', 'envara-ai-assistant' ), __( 'Customization', 'envara-ai-assistant' ), 'manage_options', 'envara-ai-assistant-customization', [ $this, 'render_customization' ] );
        add_submenu_page( 'envara-ai-assistant', __( 'Settings', 'envara-ai-assistant' ), __( 'Settings', 'envara-ai-assistant' ), 'manage_options', 'envara-ai-assistant-settings', [ $this, 'render_settings' ] );
        add_submenu_page( 'envara-ai-assistant', __( 'Logs & Analytics', 'envara-ai-assistant' ), __( 'Logs & Analytics', 'envara-ai-assistant' ), 'manage_options', 'envara-ai-assistant-logs', [ $this, 'render_logs' ] );
    }

    /**
     * Renders dashboard page.
     */
    public function render_dashboard(): void {
        $chatbots     = get_chatbots();
        $chatbot_ids  = wp_list_pluck( $chatbots, 'id' );
        $sessions_tbl = table_name( 'sessions' );
        $messages_tbl = table_name( 'messages' );

        global $wpdb;
        $total_sessions = $chatbot_ids ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sessions_tbl} WHERE chatbot_id IN (" . implode( ',', array_map( 'intval', $chatbot_ids ) ) . ')' ) : 0;
        $active_sessions = $chatbot_ids ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sessions_tbl} WHERE status = 'active'" ) : 0;
        $total_messages  = $chatbot_ids ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$messages_tbl}" ) : 0;

        include ENVARA_AI_ASSISTANT_PATH . 'admin/views/dashboard.php';
    }

    /**
     * Renders chatbots page.
     */
    public function render_chatbots(): void {
        $action = $_GET['action'] ?? '';
        if ( 'edit' === $action ) {
            $chatbot_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
            $chatbot    = $chatbot_id ? get_chatbot( $chatbot_id ) : null;
            include ENVARA_AI_ASSISTANT_PATH . 'admin/views/chatbot-form.php';
            return;
        }

        if ( 'delete' === $action && ! empty( $_GET['id'] ) && isset( $_GET['_wpnonce'] ) ) {
            $chatbot_id = absint( $_GET['id'] );
            if ( wp_verify_nonce( $_GET['_wpnonce'], 'envara_ai_assistant_delete_chatbot_' . $chatbot_id ) ) {
                Chatbot::delete( $chatbot_id );
                wp_safe_redirect( admin_url( 'admin.php?page=envara-ai-assistant-chatbots&deleted=1' ) );
                exit;
            }
        }

        $table = new Chatbots_List_Table();
        $table->prepare_items();

        include ENVARA_AI_ASSISTANT_PATH . 'admin/views/chatbots.php';
    }

    /**
     * Handles chatbot saving.
     */
    public function handle_save_chatbot(): void {
        check_admin_referer( 'envara_ai_assistant_save_chatbot' );

        $data = deep_sanitize( $_POST['chatbot'] ?? [] );
        $id   = isset( $_POST['chatbot_id'] ) ? absint( $_POST['chatbot_id'] ) : 0;

        $visibility_data = $data['visibility_data'] ?? '';
        if ( is_array( $visibility_data ) ) {
            $visibility_data = implode( ',', array_map( 'sanitize_text_field', $visibility_data ) );
        } else {
            $visibility_data = sanitize_text_field( $visibility_data );
        }

        $settings = $data['settings'] ?? [];
        if ( ! is_array( $settings ) ) {
            $settings = [];
        }

        $payload = [
            'id'              => $id,
            'name'            => sanitize_text_field( $data['name'] ?? '' ),
            'model'           => sanitize_text_field( $data['model'] ?? 'gpt-3.5-turbo' ),
            'system_prompt'   => wp_kses_post( $data['system_prompt'] ?? '' ),
            'welcome_message' => wp_kses_post( $data['welcome_message'] ?? '' ),
            'visibility'      => sanitize_text_field( $data['visibility'] ?? 'sitewide' ),
            'visibility_data' => $visibility_data,
            'status'          => sanitize_text_field( $data['status'] ?? 'active' ),
            'settings'        => wp_json_encode( array_map( 'wp_kses_post', $settings ) ),
        ];

        $chatbot_id = Chatbot::upsert( $payload );

        $form_schema = $data['form']['fields'] ?? [];
        if ( is_string( $form_schema ) ) {
            $form_schema = json_decode( wp_unslash( $form_schema ), true );
        }

        if ( is_array( $form_schema ) ) {
            Chatbot::update_form_schema( $chatbot_id, $form_schema );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=envara-ai-assistant-chatbots&updated=1' ) );
        exit;
    }

    /**
     * Handles chatbot deletion via form.
     */
    public function handle_delete_chatbot(): void {
        check_admin_referer( 'envara_ai_assistant_delete_chatbot' );

        $id = isset( $_POST['chatbot_id'] ) ? absint( $_POST['chatbot_id'] ) : 0;
        if ( $id ) {
            Chatbot::delete( $id );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=envara-ai-assistant-chatbots&deleted=1' ) );
        exit;
    }

    /**
     * Renders training page.
     */
    public function render_training(): void {
        $chatbot_id = isset( $_GET['chatbot_id'] ) ? absint( $_GET['chatbot_id'] ) : 0;
        $chatbot    = $chatbot_id ? get_chatbot( $chatbot_id ) : null;
        $sources    = $chatbot_id ? Training::get_sources( $chatbot_id ) : [];

        include ENVARA_AI_ASSISTANT_PATH . 'admin/views/training.php';
    }

    /**
     * Handles training file submissions.
     */
    public function handle_save_training(): void {
        check_admin_referer( 'envara_ai_assistant_save_training' );

        $chatbot_id = isset( $_POST['chatbot_id'] ) ? absint( $_POST['chatbot_id'] ) : 0;
        if ( ! $chatbot_id ) {
            wp_safe_redirect( admin_url( 'admin.php?page=envara-ai-assistant-training&error=1' ) );
            exit;
        }

        if ( ! empty( $_FILES['training_file']['name'] ) ) {
            $file = wp_handle_upload( $_FILES['training_file'], [ 'test_form' => false ] );
            if ( ! isset( $file['error'] ) ) {
                Training::add_source( $chatbot_id, 'file', $file['file'] );
            }
        }

        if ( ! empty( $_POST['training_text'] ) ) {
            Training::add_source( $chatbot_id, 'text', wp_kses_post( wp_unslash( $_POST['training_text'] ) ) );
        }

        if ( ! empty( $_POST['training_url'] ) ) {
            Training::add_source( $chatbot_id, 'url', esc_url_raw( wp_unslash( $_POST['training_url'] ) ) );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=envara-ai-assistant-training&chatbot_id=' . $chatbot_id . '&updated=1' ) );
        exit;
    }

    /**
     * Deletes training source.
     */
    public function handle_delete_source(): void {
        check_admin_referer( 'envara_ai_assistant_delete_source' );

        $source_id  = isset( $_POST['source_id'] ) ? absint( $_POST['source_id'] ) : 0;
        $chatbot_id = isset( $_POST['chatbot_id'] ) ? absint( $_POST['chatbot_id'] ) : 0;

        if ( $source_id ) {
            Training::delete_source( $source_id );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=envara-ai-assistant-training&chatbot_id=' . $chatbot_id . '&deleted=1' ) );
        exit;
    }

    /**
     * Renders conversations page.
     */
    public function render_conversations(): void {
        global $wpdb;
        $sessions_table = table_name( 'sessions' );

        $chatbot_filter = isset( $_GET['chatbot_id'] ) ? absint( $_GET['chatbot_id'] ) : 0;
        $where          = '1=1';
        if ( $chatbot_filter ) {
            $where .= $wpdb->prepare( ' AND chatbot_id = %d', $chatbot_filter );
        }

        $sessions = $wpdb->get_results( "SELECT * FROM {$sessions_table} WHERE {$where} ORDER BY start_time DESC LIMIT 100", ARRAY_A );
        $view_id  = isset( $_GET['session_id'] ) ? sanitize_text_field( wp_unslash( $_GET['session_id'] ) ) : '';
        $view     = $view_id ? Session::get( $view_id ) : null;
        $messages = $view ? Session::get_messages( $view_id ) : [];

        include ENVARA_AI_ASSISTANT_PATH . 'admin/views/conversations.php';
    }

    /**
     * Exports conversation to TXT or PDF (basic TXT support).
     */
    public function handle_export_conversation(): void {
        check_admin_referer( 'envara_ai_assistant_export_conversation' );

        $session_id = sanitize_text_field( wp_unslash( $_POST['session_id'] ?? '' ) );
        $format     = sanitize_text_field( wp_unslash( $_POST['format'] ?? 'txt' ) );

        if ( empty( $session_id ) ) {
            wp_die( esc_html__( 'Session not found.', 'envara-ai-assistant' ) );
        }

        $messages = Session::get_messages( $session_id );
        if ( 'txt' === $format ) {
            header( 'Content-Type: text/plain' );
            header( 'Content-Disposition: attachment; filename="conversation-' . $session_id . '.txt"' );
            foreach ( $messages as $message ) {
                echo strtoupper( $message['sender'] ) . ": " . wp_strip_all_tags( $message['message'] ) . "\n";
            }
            exit;
        }

        wp_die( esc_html__( 'Unsupported export format.', 'envara-ai-assistant' ) );
    }

    /**
     * Renders customization page.
     */
    public function render_customization(): void {
        $options = \get_option( 'envara_ai_assistant_customization', [] );

        include ENVARA_AI_ASSISTANT_PATH . 'admin/views/customization.php';
    }

    /**
     * Renders settings page wrapper.
     */
    public function render_settings(): void {
        include ENVARA_AI_ASSISTANT_PATH . 'admin/views/settings.php';
    }

    /**
     * Renders logs page.
     */
    public function render_logs(): void {
        global $wpdb;
        $sessions_table = table_name( 'sessions' );
        $messages_table = table_name( 'messages' );

        $stats = [
            'total_sessions' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sessions_table}" ),
            'active_sessions' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sessions_table} WHERE status = 'active'" ),
            'total_messages' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$messages_table}" ),
        ];

        include ENVARA_AI_ASSISTANT_PATH . 'admin/views/logs.php';
    }
}
