<?php
/**
 * Minimal WordPress stubs for tests.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ );
}

$GLOBALS['wp_stub_options']     = [];
$GLOBALS['wp_stub_remote_post'] = [
    'requests' => [],
    'response' => null,
];
$GLOBALS['wp_stub_remote_get']  = [];
$GLOBALS['wp_stub_chatbots']    = [];

function add_action( $hook, $callback, $priority = 10, $args = 1 ): void {}

function register_rest_route( $namespace, $route, $args = [] ): void {}

function rest_ensure_response( $response ) {
    return $response;
}

function sanitize_text_field( $value ) {
    if ( is_string( $value ) ) {
        return trim( $value );
    }

    return '';
}

function wp_kses_post( $value ) {
    return is_string( $value ) ? strip_tags( $value ) : '';
}

function esc_url_raw( $url ) {
    return is_string( $url ) ? $url : '';
}

function __( $text, $domain = null ) {
    return $text;
}

function wp_json_encode( $data ) {
    return json_encode( $data );
}

function wp_remote_post( $url, $args = [] ) {
    $GLOBALS['wp_stub_remote_post']['requests'][] = [
        'url'  => $url,
        'args' => $args,
    ];

    $response = $GLOBALS['wp_stub_remote_post']['response'];
    if ( is_callable( $response ) ) {
        return $response( $url, $args );
    }

    return $response ?? [ 'body' => '{}' ];
}

function wp_remote_get( $url, $args = [] ) {
    if ( isset( $GLOBALS['wp_stub_remote_get'][ $url ] ) ) {
        return $GLOBALS['wp_stub_remote_get'][ $url ];
    }

    return [ 'body' => '' ];
}

function wp_remote_retrieve_body( $response ) {
    return $response['body'] ?? '';
}

function is_wp_error( $thing ): bool {
    return $thing instanceof WP_Error;
}

class WP_Error extends \Exception {}

class WP_REST_Request {
    private $params;

    public function __construct( array $params = [] ) {
        $this->params = $params;
    }

    public function get_param( string $key ) {
        return $this->params[ $key ] ?? null;
    }
}

function current_time( $type ) {
    return '2024-01-01 00:00:00';
}

function wp_strip_all_tags( $text ) {
    return is_string( $text ) ? strip_tags( $text ) : '';
}

function wp_unslash( $value ) {
    return $value;
}

function get_option( $name, $default = false ) {
    return $GLOBALS['wp_stub_options'][ $name ] ?? $default;
}
