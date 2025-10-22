<?php
/**
 * Chatbots list view.
 *
 * @package Envara_Ai_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap envara-ai-assistant">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Chatbots', 'envara-ai-assistant' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=envara-ai-assistant-chatbots&action=edit' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'envara-ai-assistant' ); ?></a>
    <hr class="wp-header-end" />

    <?php if ( isset( $_GET['updated'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Chatbot saved successfully.', 'envara-ai-assistant' ); ?></p></div>
    <?php endif; ?>
    <?php if ( isset( $_GET['deleted'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Chatbot deleted.', 'envara-ai-assistant' ); ?></p></div>
    <?php endif; ?>

    <form method="get">
        <input type="hidden" name="page" value="envara-ai-assistant-chatbots" />
        <?php $table->display(); ?>
    </form>
</div>
