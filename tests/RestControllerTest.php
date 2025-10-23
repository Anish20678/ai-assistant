<?php

declare(strict_types=1);

use Envara\AI_Assistant\Rest\Rest_Controller;
use Envara\AI_Assistant\Session;
use Envara\AI_Assistant\Training;
use PHPUnit\Framework\TestCase;

final class RestControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['wp_stub_remote_post']['requests'] = [];
        $GLOBALS['wp_stub_remote_post']['response'] = [
            'body' => json_encode(
                [
                    'choices' => [
                        [
                            'message' => [
                                'content' => 'Contextual reply',
                            ],
                        ],
                    ],
                ]
            ),
        ];
        $GLOBALS['wp_stub_remote_get'] = [];
        $GLOBALS['wp_stub_options']   = [];
        $GLOBALS['wp_stub_chatbots']  = [];

        Session::reset();
        Training::reset();
    }

    public function test_handle_chat_enriches_context_and_logs_messages(): void
    {
        $controller = Rest_Controller::instance();

        $chatbot_id = 42;
        $GLOBALS['wp_stub_chatbots'][ $chatbot_id ] = [
            'id'            => $chatbot_id,
            'model'         => 'gpt-test',
            'system_prompt' => 'You are a helpful assistant.',
        ];

        $GLOBALS['wp_stub_options']['envara_ai_assistant_settings'] = [
            'openai_api_key' => 'key',
            'temperature'    => 0.3,
            'max_tokens'     => 128,
            'default_model'  => 'gpt-default',
        ];

        Training::set_sources(
            $chatbot_id,
            [
                [
                    'source_type' => 'text',
                    'source_path' => 'Shipping typically takes three to five business days.',
                ],
                [
                    'source_type' => 'url',
                    'source_path' => 'https://example.com/returns',
                ],
                [
                    'source_type' => 'file',
                    'source_path' => __DIR__ . '/fixtures/policies.txt',
                ],
            ]
        );

        $GLOBALS['wp_stub_remote_get']['https://example.com/returns'] = [
            'body' => '<p>Return policy allows returns within thirty days of purchase.</p>',
        ];

        Session::seed_messages(
            'session-1',
            [
                [
                    'sender'    => 'assistant',
                    'message'   => 'Hello! What can I help you with today?',
                    'timestamp' => '2024-01-01 00:00:00',
                ],
            ]
        );

        $request = new WP_REST_Request(
            [
                'session_id' => 'session-1',
                'message'    => 'Can you explain the return policy and shipping speed?',
                'chatbot_id' => $chatbot_id,
            ]
        );

        $response = $controller->handle_chat( $request );

        $this->assertSame( 'Contextual reply', $response['response'] );

        $history = Session::get_messages( 'session-1' );
        $this->assertCount( 3, $history );
        $this->assertSame( 'user', $history[1]['sender'] );
        $this->assertSame( 'assistant', $history[2]['sender'] );

        $this->assertCount( 1, $GLOBALS['wp_stub_remote_post']['requests'] );
        $payload = json_decode( $GLOBALS['wp_stub_remote_post']['requests'][0]['args']['body'], true );

        $this->assertArrayHasKey( 'messages', $payload );
        $this->assertGreaterThanOrEqual( 3, count( $payload['messages'] ) );
        $this->assertSame( 'system', $payload['messages'][0]['role'] );
        $this->assertStringContainsString( 'relevant context', strtolower( $payload['messages'][0]['content'] ) );
        $this->assertStringContainsString( 'shipping', strtolower( $payload['messages'][0]['content'] ) );
        $this->assertStringContainsString( 'return', strtolower( $payload['messages'][0]['content'] ) );

        $this->assertSame( 'assistant', $payload['messages'][1]['role'] );
        $this->assertSame( 'user', $payload['messages'][2]['role'] );
    }
}
