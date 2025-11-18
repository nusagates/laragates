<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

it('verifies webhook handshake with correct verify token', function () {
    putenv('VERIFY_TOKEN=rahasia-verify-token');
    $_ENV['VERIFY_TOKEN'] = 'rahasia-verify-token';
    $_SERVER['VERIFY_TOKEN'] = 'rahasia-verify-token';
    $response = $this->get('/api/webhook?hub.mode=subscribe&hub.verify_token=rahasia-verify-token&hub.challenge=12345');

    $response->assertStatus(200)->assertSee('12345');
});

it('rejects webhook handshake with incorrect verify token', function () {
    putenv('VERIFY_TOKEN=rahasia-verify-token');
    $_ENV['VERIFY_TOKEN'] = 'rahasia-verify-token';
    $_SERVER['VERIFY_TOKEN'] = 'rahasia-verify-token';
    $response = $this->get('/api/webhook?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=12345');

    $response->assertStatus(403);
});

it('accepts POST webhook payload without signature and processes it', function () {
    $payload = [
        'object' => 'whatsapp_business_account',
        'entry' => [
            [
                'id' => env('WABA_ID'),
                'changes' => [
                    [
                        'value' => [
                            'messages' => [
                                [
                                    'from' => '6281xxxxxxx',
                                    'id' => 'wamid.HBgL',
                                    'timestamp' => now()->timestamp,
                                    'type' => 'text',
                                    'text' => ['body' => 'hello']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    $response = $this->postJson('/api/webhook', $payload);

    $response->assertStatus(200)->assertSee('EVENT_RECEIVED');
});
