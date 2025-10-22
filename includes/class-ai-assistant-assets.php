<?php
/**
 * Asset management for AI Assistant.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles enqueueing of plugin assets.
 */
class Assets {
    /**
     * Singleton instance.
     *
     * @var Assets|null
     */
    private static $instance = null;

    /**
     * Private constructor.
     */
    private function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );
    }

    /**
     * Returns singleton instance.
     *
     * @return Assets
     */
    public static function instance(): Assets {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Enqueues admin assets.
     */
    public function enqueue_admin_assets(): void {
        wp_enqueue_style( 'envara-ai-assistant-admin', ENVARA_AI_ASSISTANT_URL . 'assets/css/admin.css', [], ENVARA_AI_ASSISTANT_VERSION );
        wp_enqueue_script( 'envara-ai-assistant-admin', ENVARA_AI_ASSISTANT_URL . 'assets/js/admin.js', [ 'jquery', 'wp-util' ], ENVARA_AI_ASSISTANT_VERSION, true );

        wp_localize_script(
            'envara-ai-assistant-admin',
            'EnvaraAiAssistant',
            [
                'nonce'        => wp_create_nonce( 'envara_ai_assistant_admin' ),
                'addFieldText' => __( 'Add Field', 'envara-ai-assistant' ),
                'removeFieldText' => __( 'Remove', 'envara-ai-assistant' ),
                'emptyForm'   => __( 'No fields yet.', 'envara-ai-assistant' ),
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            ]
        );
    }

    /**
     * Enqueues public assets.
     */
    public function enqueue_public_assets(): void {
        wp_enqueue_style( 'envara-ai-assistant-public', ENVARA_AI_ASSISTANT_URL . 'assets/css/public.css', [], ENVARA_AI_ASSISTANT_VERSION );
        wp_enqueue_script( 'envara-ai-assistant-public', ENVARA_AI_ASSISTANT_URL . 'assets/js/public.js', [ 'jquery' ], ENVARA_AI_ASSISTANT_VERSION, true );

        wp_localize_script(
            'envara-ai-assistant-public',
            'EnvaraAiAssistantPublic',
            [
                'restUrl'         => esc_url_raw( untrailingslashit( rest_url( 'ai-assistant/v1' ) ) ),
                'nonce'           => wp_create_nonce( 'wp_rest' ),
                'defaultSettings' => Settings::instance()->get_public_settings(),
            ]
        );
    }
}
