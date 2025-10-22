<?php
/**
 * Plugin installer.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant\Setup;

use Envara\AI_Assistant;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Installer class responsible for database setup.
 */
class Installer {
    /**
     * Runs on plugin activation.
     */
    public static function install(): void {
        self::maybe_create_tables();
    }

    /**
     * Creates database tables if not exists.
     */
    private static function maybe_create_tables(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $tables = [];

        $tables[] = "CREATE TABLE " . AI_Assistant\table_name( 'chatbots' ) . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            model VARCHAR(100) NOT NULL,
            system_prompt LONGTEXT,
            welcome_message LONGTEXT,
            visibility VARCHAR(50) DEFAULT 'sitewide',
            visibility_data LONGTEXT,
            status VARCHAR(20) DEFAULT 'active',
            settings LONGTEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE " . AI_Assistant\table_name( 'forms' ) . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            chatbot_id BIGINT UNSIGNED NOT NULL,
            form_json LONGTEXT,
            PRIMARY KEY (id),
            KEY chatbot_id (chatbot_id)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE " . AI_Assistant\table_name( 'sessions' ) . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(64) NOT NULL,
            chatbot_id BIGINT UNSIGNED NOT NULL,
            visitor_id VARCHAR(191),
            visitor_ip VARCHAR(100),
            page_url TEXT,
            start_time DATETIME,
            end_time DATETIME,
            status VARCHAR(20) DEFAULT 'active',
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE " . AI_Assistant\table_name( 'messages' ) . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(64) NOT NULL,
            sender VARCHAR(20) NOT NULL,
            message LONGTEXT NOT NULL,
            timestamp DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE " . AI_Assistant\table_name( 'training' ) . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            chatbot_id BIGINT UNSIGNED NOT NULL,
            source_type VARCHAR(50) NOT NULL,
            source_path LONGTEXT,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY chatbot_id (chatbot_id)
        ) $charset_collate;";

        foreach ( $tables as $sql ) {
            dbDelta( $sql );
        }
    }
}
