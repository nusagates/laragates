<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhook.
     */
    public function handle(Request $request)
    {
        // Support verification handshake used by Meta/WhatsApp (GET)
        if ($request->isMethod('get')) {
            // Accept forms: hub.mode (string key), hub[mode] (array), or fallback to $_GET or raw query string
            $mode = $request->query('hub.mode') ?? ($request->query('hub')['mode'] ?? null) ?? ($_GET['hub.mode'] ?? null) ?? ($_GET['hub']['mode'] ?? null);
            $token = $request->query('hub.verify_token') ?? ($request->query('hub')['verify_token'] ?? null) ?? ($_GET['hub.verify_token'] ?? null) ?? ($_GET['hub']['verify_token'] ?? null);
            $challenge = $request->query('hub.challenge') ?? ($request->query('hub')['challenge'] ?? null) ?? ($_GET['hub.challenge'] ?? null) ?? ($_GET['hub']['challenge'] ?? null);

            // If still not found, parse raw query string (e.g. when keys contain dots)
            $qs = $request->getQueryString() ?? '';
            if (empty($mode)) {
                if (preg_match('/hub\.mode=([^&]+)/', $qs, $m)) {
                    $mode = urldecode($m[1]);
                }
            }
            if (empty($token)) {
                if (preg_match('/hub\.verify_token=([^&]+)/', $qs, $m)) {
                    $token = urldecode($m[1]);
                }
            }
            if (empty($challenge)) {
                if (preg_match('/hub\.challenge=([^&]+)/', $qs, $m)) {
                    $challenge = urldecode($m[1]);
                }
            }

            // Use VERIFY_TOKEN from environment; fall back to getenv/$_ENV/$_SERVER so tests using putenv() work
            $expectedToken = env('VERIFY_TOKEN') ?: getenv('VERIFY_TOKEN') ?: ($_ENV['VERIFY_TOKEN'] ?? null) ?: ($_SERVER['VERIFY_TOKEN'] ?? null);

            // Normalize values
            $modeNorm = is_string($mode) ? strtolower(trim($mode)) : null;
            $tokenNorm = is_string($token) ? trim($token) : null;
            $expectedNorm = is_string($expectedToken) ? trim($expectedToken) : null;

            if ($modeNorm === 'subscribe' && !empty($tokenNorm) && !empty($expectedNorm) && hash_equals($expectedNorm, $tokenNorm)) {
                // Must return the plain challenge string
                return response($challenge, 200);
            }

            return response('Forbidden', 403);
        }

        // POST: handle webhook events without signature verification per user request
        $payload = $request->getContent();
        $data = json_decode($payload, true);

        // Basic check for WhatsApp object
        if (!is_array($data) || empty($data['object']) || $data['object'] !== 'whatsapp_business_account') {
            Log::info('Received non-whatsapp webhook', ['body' => $data]);
            return response('EVENT_RECEIVED', 200);
        }

        // Iterate entries and changes (per Meta's webhook structure)
        foreach ($data['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                // Example: messages arrived
                if (!empty($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        // Attach WABA ID context
                        $waba = env('WABA_ID');
                        Log::info('WhatsApp message received', [
                            'waba_id' => $waba,
                            'from' => $message['from'] ?? null,
                            'id' => $message['id'] ?? null,
                            'timestamp' => $message['timestamp'] ?? null,
                            'type' => $message['type'] ?? null,
                            'message' => $message,
                        ]);

                        // TODO: dispatch job to process message asynchronously
                    }
                }

                // Contacts or statuses
                if (!empty($value['statuses'])) {
                    Log::info('WhatsApp statuses', ['statuses' => $value['statuses']]);
                }

                if (!empty($value['contacts'])) {
                    Log::info('WhatsApp contacts', ['contacts' => $value['contacts']]);
                }
            }
        }

        // Respond quickly as required by Meta's webhook docs
        return response('EVENT_RECEIVED', 200);
    }
}
