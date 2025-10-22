<?php
/**
 * Main loader for AI Assistant plugin.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant;

use Envara\AI_Assistant\Admin\Admin;
use Envara\AI_Assistant\Frontend\Frontend;
use Envara\AI_Assistant\Rest\Rest_Controller;
use Envara\AI_Assistant\Setup\Installer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Loader class responsible for bootstrapping the plugin.
 */
final class Loader {
    /**
     * Singleton instance.
     *
     * @var Loader|null
     */
    private static $instance = null;

    /**
     * Constructor.
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Retrieves singleton instance.
     *
     * @return Loader
     */
    public static function instance(): Loader {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Loads required files.
     */
    private function includes(): void {
        require_once ENVARA_AI_ASSISTANT_PATH . 'includes/helpers.php';
        require_once ENVARA_AI_ASSISTANT_PATH . 'includes/class-ai-assistant-assets.php';
        require_once ENVARA_AI_ASSISTANT_PATH . 'includes/class-ai-assistant-settings.php';
        require_once ENVARA_AI_ASSISTANT_PATH . 'includes/class-ai-assistant-emailer.php';
        require_once ENVARA_AI_ASSISTANT_PATH . 'includes/class-ai-assistant-chatbot.php';
        require_once ENVARA_AI_ASSISTANT_PATH . 'includes/class-ai-assistant-session.php';
        require_once ENVARA_AI_ASSISTANT_PATH . 'includes/class-ai-assistant-training.php';
        require_once ENVARA_AI_ASSISTANT_PATH . 'includes/setup/class-ai-assistant-installer.php';
        require_once ENVARA_AI_ASSISTANT_PATH . 'admin/class-ai-assistant-admin.php';
        require_once ENVARA_AI_ASSISTANT_PATH . 'admin/class-ai-assistant-list-table.php';
        require_once ENVARA_AI_ASSISTANT_PATH . 'public/class-ai-assistant-frontend.php';
        require_once ENVARA_AI_ASSISTANT_PATH . 'public/class-ai-assistant-shortcode.php';
        require_once ENVARA_AI_ASSISTANT_PATH . 'public/class-ai-assistant-widget.php';
        require_once ENVARA_AI_ASSISTANT_PATH . 'includes/rest/class-ai-assistant-rest-controller.php';
    }

    /**
     * Initializes plugin hooks.
     */
    private function init_hooks(): void {
        register_activation_hook( ENVARA_AI_ASSISTANT_PATH . 'envara-ai-assistant.php', [ Installer::class, 'install' ] );

        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        add_action( 'init', [ $this, 'init_modules' ] );
    }

    /**
     * Loads localization files.
     */
    public function load_textdomain(): void {
        load_plugin_textdomain( 'envara-ai-assistant', false, dirname( plugin_basename( ENVARA_AI_ASSISTANT_PATH . 'envara-ai-assistant.php' ) ) . '/languages' );
    }

    /**
     * Boots the different plugin modules.
     */
    public function init_modules(): void {
        Assets::instance();
        Settings::instance();
        Admin::instance();
        Frontend::instance();
        Rest_Controller::instance();
    }
}
