<?php
/**
 * Dashboard view.
 *
 * @package Envara_Ai_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap envara-ai-assistant">
    <h1><?php esc_html_e( 'AI Assistant Dashboard', 'envara-ai-assistant' ); ?></h1>

    <div class="envara-cards">
        <div class="envara-card">
            <h2><?php esc_html_e( 'Total Chatbots', 'envara-ai-assistant' ); ?></h2>
            <p class="envara-card-number"><?php echo esc_html( count( $chatbots ) ); ?></p>
        </div>
        <div class="envara-card">
            <h2><?php esc_html_e( 'Total Sessions', 'envara-ai-assistant' ); ?></h2>
            <p class="envara-card-number"><?php echo esc_html( $total_sessions ); ?></p>
        </div>
        <div class="envara-card">
            <h2><?php esc_html_e( 'Active Sessions', 'envara-ai-assistant' ); ?></h2>
            <p class="envara-card-number"><?php echo esc_html( $active_sessions ); ?></p>
        </div>
        <div class="envara-card">
            <h2><?php esc_html_e( 'Messages Logged', 'envara-ai-assistant' ); ?></h2>
            <p class="envara-card-number"><?php echo esc_html( $total_messages ); ?></p>
        </div>
    </div>

    <div class="envara-shortcuts">
        <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=envara-ai-assistant-chatbots&action=edit' ) ); ?>"><?php esc_html_e( 'Create Chatbot', 'envara-ai-assistant' ); ?></a>
        <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=envara-ai-assistant-training' ) ); ?>"><?php esc_html_e( 'Train Chatbot', 'envara-ai-assistant' ); ?></a>
        <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=envara-ai-assistant-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'envara-ai-assistant' ); ?></a>
        <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=envara-ai-assistant-customization' ) ); ?>"><?php esc_html_e( 'Customize', 'envara-ai-assistant' ); ?></a>
    </div>

    <h2><?php esc_html_e( 'Latest Chatbots', 'envara-ai-assistant' ); ?></h2>
    <table class="widefat">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Name', 'envara-ai-assistant' ); ?></th>
                <th><?php esc_html_e( 'Model', 'envara-ai-assistant' ); ?></th>
                <th><?php esc_html_e( 'Status', 'envara-ai-assistant' ); ?></th>
                <th><?php esc_html_e( 'Shortcode', 'envara-ai-assistant' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $chatbots ) ) : ?>
                <tr>
                    <td colspan="4"><?php esc_html_e( 'No chatbots found. Create your first chatbot to get started.', 'envara-ai-assistant' ); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ( array_slice( $chatbots, 0, 5 ) as $bot ) : ?>
                    <tr>
                        <td><?php echo esc_html( $bot['name'] ); ?></td>
                        <td><?php echo esc_html( strtoupper( $bot['model'] ) ); ?></td>
                        <td><?php echo esc_html( ucfirst( $bot['status'] ) ); ?></td>
                        <td><code>[ai_assistant id="<?php echo esc_attr( $bot['id'] ); ?>"]</code></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
