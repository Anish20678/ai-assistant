<?php
/**
 * Plugin Name:       AI Assistant
 * Plugin URI:        https://www.envara.ae
 * Description:       A simple and intelligent AI Assistant plugin for WordPress that adds a customizable chatbot with OpenAI integration and conversation logging.
 * Version:           1.0.0
 * Author:            Envara Ventures
 * Author URI:        https://www.envara.ae
 * Text Domain:       envara-ai-assistant
 * Requires at least: 5.0
 * Requires PHP:      7.4
 *
 * @package Envara_Ai_Assistant
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ENVARA_AI_ASSISTANT_VERSION' ) ) {
    define( 'ENVARA_AI_ASSISTANT_VERSION', '1.0.0' );
}

if ( ! defined( 'ENVARA_AI_ASSISTANT_PATH' ) ) {
    define( 'ENVARA_AI_ASSISTANT_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ENVARA_AI_ASSISTANT_URL' ) ) {
    define( 'ENVARA_AI_ASSISTANT_URL', plugin_dir_url( __FILE__ ) );
}

require_once ENVARA_AI_ASSISTANT_PATH . 'includes/class-ai-assistant-loader.php';

\Envara\AI_Assistant\Loader::instance();
