<?php
/**
 * Plugin settings handler.
 *
 * @package Envara_Ai_Assistant
 */

namespace Envara\AI_Assistant;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings handler class.
 */
class Settings {
    /**
     * Singleton instance.
     *
     * @var Settings|null
     */
    private static $instance = null;

    /**
     * Option key name.
     */
    private const OPTION_KEY = 'envara_ai_assistant_settings';

    /**
     * Private constructor.
     */
    private function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Returns singleton instance.
     *
     * @return Settings
     */
    public static function instance(): Settings {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registers settings.
     */
    public function register_settings(): void {
        register_setting( 'envara_ai_assistant_settings', self::OPTION_KEY, [ $this, 'sanitize_settings' ] );

        add_settings_section(
            'envara_ai_assistant_general',
            __( 'General Settings', 'envara-ai-assistant' ),
            '__return_false',
            'envara_ai_assistant_settings'
        );

        add_settings_field(
            'openai_api_key',
            __( 'OpenAI API Key', 'envara-ai-assistant' ),
            [ $this, 'render_text_field' ],
            'envara_ai_assistant_settings',
            'envara_ai_assistant_general',
            [
                'label_for'   => 'openai_api_key',
                'type'        => 'password',
                'description' => __( 'Enter your OpenAI API key. Stored securely.', 'envara-ai-assistant' ),
            ]
        );

        add_settings_field(
            'default_model',
            __( 'Default Model', 'envara-ai-assistant' ),
            [ $this, 'render_select_field' ],
            'envara_ai_assistant_settings',
            'envara_ai_assistant_general',
            [
                'label_for' => 'default_model',
                'options'   => [
                    'gpt-3.5-turbo' => __( 'GPT-3.5 Turbo', 'envara-ai-assistant' ),
                    'gpt-4'         => __( 'GPT-4', 'envara-ai-assistant' ),
                ],
            ]
        );

        add_settings_field(
            'temperature',
            __( 'Temperature', 'envara-ai-assistant' ),
            [ $this, 'render_range_field' ],
            'envara_ai_assistant_settings',
            'envara_ai_assistant_general',
            [
                'label_for' => 'temperature',
                'min'       => 0,
                'max'       => 2,
                'step'      => 0.1,
            ]
        );

        add_settings_field(
            'max_tokens',
            __( 'Max Tokens', 'envara-ai-assistant' ),
            [ $this, 'render_number_field' ],
            'envara_ai_assistant_settings',
            'envara_ai_assistant_general',
            [
                'label_for' => 'max_tokens',
                'min'       => 1,
                'max'       => 4096,
            ]
        );

        add_settings_field(
            'logging',
            __( 'Conversation Logging', 'envara-ai-assistant' ),
            [ $this, 'render_checkbox_field' ],
            'envara_ai_assistant_settings',
            'envara_ai_assistant_general',
            [
                'label_for'   => 'logging',
                'description' => __( 'Enable or disable storing chat transcripts.', 'envara-ai-assistant' ),
            ]
        );

        add_settings_field(
            'ip_tracking',
            __( 'IP Tracking', 'envara-ai-assistant' ),
            [ $this, 'render_checkbox_field' ],
            'envara_ai_assistant_settings',
            'envara_ai_assistant_general',
            [
                'label_for'   => 'ip_tracking',
                'description' => __( 'Collect visitor IP addresses for sessions.', 'envara-ai-assistant' ),
            ]
        );

        add_settings_field(
            'notification_email',
            __( 'Notification Email', 'envara-ai-assistant' ),
            [ $this, 'render_text_field' ],
            'envara_ai_assistant_settings',
            'envara_ai_assistant_general',
            [
                'label_for' => 'notification_email',
                'type'      => 'email',
            ]
        );

        add_settings_field(
            'privacy_message',
            __( 'Privacy Message', 'envara-ai-assistant' ),
            [ $this, 'render_textarea_field' ],
            'envara_ai_assistant_settings',
            'envara_ai_assistant_general',
            [
                'label_for' => 'privacy_message',
            ]
        );
    }

    /**
     * Sanitizes settings input.
     *
     * @param array $input Raw input.
     *
     * @return array
     */
    public function sanitize_settings( array $input ): array {
        $sanitized = [];

        $sanitized['openai_api_key']    = sanitize_text_field( $input['openai_api_key'] ?? '' );
        $sanitized['default_model']     = sanitize_text_field( $input['default_model'] ?? 'gpt-3.5-turbo' );
        $sanitized['temperature']       = isset( $input['temperature'] ) ? (float) $input['temperature'] : 1.0;
        $sanitized['max_tokens']        = isset( $input['max_tokens'] ) ? (int) $input['max_tokens'] : 1024;
        $sanitized['logging']           = ! empty( $input['logging'] );
        $sanitized['ip_tracking']       = ! empty( $input['ip_tracking'] );
        $sanitized['notification_email'] = sanitize_email( $input['notification_email'] ?? '' );
        $sanitized['privacy_message']    = wp_kses_post( $input['privacy_message'] ?? '' );

        return $sanitized;
    }

    /**
     * Renders text field.
     *
     * @param array $args Field args.
     */
    public function render_text_field( array $args ): void {
        $options = \get_option( self::OPTION_KEY, [] );
        $value   = $options[ $args['label_for'] ] ?? '';
        $type    = $args['type'] ?? 'text';

        printf(
            '<input type="%1$s" id="%2$s" name="%3$s[%2$s]" value="%4$s" class="regular-text" />',
            esc_attr( $type ),
            esc_attr( $args['label_for'] ),
            esc_attr( self::OPTION_KEY ),
            esc_attr( $value )
        );

        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    /**
     * Renders textarea.
     *
     * @param array $args Field args.
     */
    public function render_textarea_field( array $args ): void {
        $options = \get_option( self::OPTION_KEY, [] );
        $value   = $options[ $args['label_for'] ] ?? '';

        printf(
            '<textarea id="%1$s" name="%2$s[%1$s]" class="large-text" rows="4">%3$s</textarea>',
            esc_attr( $args['label_for'] ),
            esc_attr( self::OPTION_KEY ),
            esc_textarea( $value )
        );
    }

    /**
     * Renders checkbox field.
     *
     * @param array $args Field args.
     */
    public function render_checkbox_field( array $args ): void {
        $options = \get_option( self::OPTION_KEY, [] );
        $value   = ! empty( $options[ $args['label_for'] ] );

        printf(
            '<label><input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="1" %3$s /> %4$s</label>',
            esc_attr( $args['label_for'] ),
            esc_attr( self::OPTION_KEY ),
            checked( $value, true, false ),
            esc_html( $args['description'] ?? '' )
        );
    }

    /**
     * Renders range field.
     *
     * @param array $args Field args.
     */
    public function render_range_field( array $args ): void {
        $options = \get_option( self::OPTION_KEY, [] );
        $value   = $options[ $args['label_for'] ] ?? 1.0;

        printf(
            '<input type="range" id="%1$s" name="%2$s[%1$s]" value="%3$s" min="%4$s" max="%5$s" step="%6$s" /> <span class="slider-value">%3$s</span>',
            esc_attr( $args['label_for'] ),
            esc_attr( self::OPTION_KEY ),
            esc_attr( $value ),
            esc_attr( $args['min'] ?? 0 ),
            esc_attr( $args['max'] ?? 1 ),
            esc_attr( $args['step'] ?? 0.1 )
        );
    }

    /**
     * Renders number field.
     *
     * @param array $args Field args.
     */
    public function render_number_field( array $args ): void {
        $options = \get_option( self::OPTION_KEY, [] );
        $value   = $options[ $args['label_for'] ] ?? 1024;

        printf(
            '<input type="number" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="small-text" min="%4$s" max="%5$s" />',
            esc_attr( $args['label_for'] ),
            esc_attr( self::OPTION_KEY ),
            esc_attr( $value ),
            esc_attr( $args['min'] ?? 0 ),
            esc_attr( $args['max'] ?? 10000 )
        );
    }

    /**
     * Renders select field.
     *
     * @param array $args Field args.
     */
    public function render_select_field( array $args ): void {
        $options = \get_option( self::OPTION_KEY, [] );
        $value   = $options[ $args['label_for'] ] ?? 'gpt-3.5-turbo';
        $options_list = $args['options'] ?? [];

        printf( '<select id="%1$s" name="%2$s[%1$s]">', esc_attr( $args['label_for'] ), esc_attr( self::OPTION_KEY ) );

        foreach ( $options_list as $option_value => $label ) {
            printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $option_value ), selected( $value, $option_value, false ), esc_html( $label ) );
        }

        echo '</select>';
    }

    /**
     * Retrieves public settings for localization.
     *
     * @return array
     */
    public function get_public_settings(): array {
        $options = \get_option( self::OPTION_KEY, [] );

        return [
            'logging'         => ! empty( $options['logging'] ),
            'privacy_message' => $options['privacy_message'] ?? '',
        ];
    }
}
