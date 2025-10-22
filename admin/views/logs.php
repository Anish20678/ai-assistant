<?php
/**
 * Logs view.
 *
 * @package Envara_Ai_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap envara-ai-assistant">
    <h1><?php esc_html_e( 'Logs & Analytics', 'envara-ai-assistant' ); ?></h1>

    <div class="envara-cards">
        <div class="envara-card">
            <h2><?php esc_html_e( 'Total Sessions', 'envara-ai-assistant' ); ?></h2>
            <p class="envara-card-number"><?php echo esc_html( $stats['total_sessions'] ); ?></p>
        </div>
        <div class="envara-card">
            <h2><?php esc_html_e( 'Active Sessions', 'envara-ai-assistant' ); ?></h2>
            <p class="envara-card-number"><?php echo esc_html( $stats['active_sessions'] ); ?></p>
        </div>
        <div class="envara-card">
            <h2><?php esc_html_e( 'Messages Logged', 'envara-ai-assistant' ); ?></h2>
            <p class="envara-card-number"><?php echo esc_html( $stats['total_messages'] ); ?></p>
        </div>
    </div>

    <form method="post">
        <?php submit_button( __( 'Export CSV', 'envara-ai-assistant' ), 'secondary', 'envara_ai_assistant_export_csv', false ); ?>
        <p class="description"><?php esc_html_e( 'CSV export will be available in a future update.', 'envara-ai-assistant' ); ?></p>
    </form>
</div>
