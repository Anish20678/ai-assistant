<?php
/**
 * Widget registration.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant\Frontend;

use Envara\AI_Assistant\Chatbot;
use WP_Widget;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Widget wrapper.
 */
class Widget extends WP_Widget {
    /**
     * Registers widget with WordPress.
     */
    public function __construct() {
        parent::__construct( 'envara_ai_assistant_widget', __( 'AI Assistant Chatbot', 'envara-ai-assistant' ) );
    }

    /**
     * Registers widget type.
     */
    public static function register(): void {
        add_action(
            'widgets_init',
            static function () {
                register_widget( self::class );
            }
        );
    }

    /**
     * Outputs widget content.
     *
     * @param array $args     Display arguments.
     * @param array $instance Saved values.
     */
    public function widget( $args, $instance ) {
        $chatbot_id = ! empty( $instance['chatbot_id'] ) ? absint( $instance['chatbot_id'] ) : 0;
        echo $args['before_widget'];
        echo Shortcode::render( [ 'id' => $chatbot_id ] );
        echo $args['after_widget'];
    }

    /**
     * Outputs widget settings form.
     *
     * @param array $instance Saved values.
     */
    public function form( $instance ) {
        $chatbots   = Chatbot::all();
        if ( empty( $chatbots ) ) {
            echo '<p>' . esc_html__( 'No chatbots available. Create one first.', 'envara-ai-assistant' ) . '</p>';
            return;
        }

        $chatbot_id = $instance['chatbot_id'] ?? 0;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'chatbot_id' ) ); ?>"><?php esc_html_e( 'Select Chatbot:', 'envara-ai-assistant' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'chatbot_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'chatbot_id' ) ); ?>">
                <?php foreach ( $chatbots as $chatbot ) : ?>
                    <option value="<?php echo esc_attr( $chatbot['id'] ); ?>" <?php selected( $chatbot_id, $chatbot['id'] ); ?>><?php echo esc_html( $chatbot['name'] ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    /**
     * Updates widget options.
     *
     * @param array $new_instance New values.
     * @param array $old_instance Old values.
     *
     * @return array
     */
    public function update( $new_instance, $old_instance ) {
        $instance                 = [];
        $instance['chatbot_id'] = absint( $new_instance['chatbot_id'] ?? 0 );

        return $instance;
    }
}
