<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class WabaService
{
    protected string $token;
    protected string $phoneId;
    protected string $baseUrl;
    protected string $version;

    public function __construct()
    {
        $this->token   = config('services.waba.token') ?? '';
        $this->phoneId = config('services.waba.phone_id') ?? '';
        $this->baseUrl = rtrim(config('services.waba.base_url', 'https://graph.facebook.com'), '/');
        $this->version = trim(config('services.waba.version', 'v21.0'), '/');
    }

    protected function headers()
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept'        => 'application/json',
        ];
    }

    /**
     * Get all templates for the given WhatsApp Business Account ID (WABA)
     *
     * @param string|null $businessId  If null, will read config('services.waba.business_id')
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    public function getTemplates(?string $businessId = null, int $limit = 100): array
    {
        $businessId = $businessId ?: config('services.waba.business_id');

        if (empty($businessId)) {
            throw new \Exception('WABA business id not configured. Set WABA_BUSINESS_ID in .env or pass businessId.');
        }

        $url = "{$this->baseUrl}/{$this->version}/{$businessId}/message_templates";

        $params = [
            'limit' => $limit
        ];

        $resp = Http::withToken($this->token)
            ->get($url, $params);

        if ($resp->failed()) {
            // return raw body for debugging upstream
            throw new \Exception('WABA getTemplates error: ' . $resp->body());
        }

        $json = $resp->json();

        // the API returns data array
        return Arr::get($json, 'data', []);
    }

    /**
     * Get single template details by template id (remote id)
     * If the API does not offer a single-get by id for templates, we fallback to fetching all and filtering.
     *
     * @param string $remoteId
     * @return array|null
     * @throws \Exception
     */
    public function getTemplate(string $remoteId): ?array
    {
        // Some Graph API endpoints don't support GET /{id} for message_templates.
        // We'll try to fetch via business_id list and match by id (remote_id).
        $businessId = config('services.waba.business_id');

        if (empty($businessId)) {
            throw new \Exception('WABA business id not configured. Set WABA_BUSINESS_ID in .env.');
        }

        $templates = $this->getTemplates($businessId, 250);

        foreach ($templates as $t) {
            if ((string)($t['id'] ?? '') === (string)$remoteId
                || (string)($t['name'] ?? '') === (string)$remoteId
            ) {
                return $t;
            }
        }

        return null;
    }

    /**
     * Normalize API template payload (best-effort) to DB fields expected by whatsapp_templates table
     *
     * @param array $api
     * @return array
     */
    public function normalizeTemplatePayload(array $api): array
    {
        // Typical fields: id, name, category, language, components, status, etc.
        $remoteId = $api['id'] ?? ($api['name'] ?? null);
        $name     = $api['name'] ?? null;
        $language = $api['language'] ?? ($api['lang'] ?? null); // API can use 'language'
        $category = $api['category'] ?? null;
        $status   = $api['status'] ?? null;

        // Gather body text if possible
        $bodyText = null;
        $components = $api['components'] ?? $api['body'] ?? [];
        // API components: array of { type: 'BODY'|'HEADER', parameters: [...] }
        if (is_array($components)) {
            $parts = [];
            foreach ($components as $comp) {
                $type = strtolower($comp['type'] ?? '');
                if ($type === 'body') {
                    // parameters may be an array with text entries
                    if (!empty($comp['text'])) {
                        $parts[] = $comp['text'];
                    } else if (!empty($comp['parameters']) && is_array($comp['parameters'])) {
                        // try to combine text params
                        foreach ($comp['parameters'] as $p) {
                            if (isset($p['text'])) $parts[] = $p['text'];
                        }
                    }
                }
            }
            if (!empty($parts)) {
                $bodyText = implode(' ', $parts);
            }
        }

        // fallback: maybe API returns 'body' string directly
        if (!$bodyText) {
            $bodyText = $api['body'] ?? null;
        }

        // count body params by scanning for {{1}} placeholders OR count variables in components
        $bodyParamsCount = 0;
        if ($bodyText) {
            preg_match_all('/\{\{\d+\}\}/', $bodyText, $m);
            $bodyParamsCount = count($m[0]);
        } elseif (!empty($components) && is_array($components)) {
            // try count occurrences of 'text' parameters that contain placeholders
            $count = 0;
            foreach ($components as $comp) {
                if (!empty($comp['parameters']) && is_array($comp['parameters'])) {
                    foreach ($comp['parameters'] as $p) {
                        if (!empty($p['text']) && preg_match('/\{\{\d+\}\}/', $p['text'])) {
                            preg_match_all('/\{\{\d+\}\}/', $p['text'], $m2);
                            $count = max($count, count($m2[0]));
                        }
                    }
                }
            }
            $bodyParamsCount = $count;
        }

        return [
            'name' => $name,
            'remote_id' => $remoteId,
            'category' => $category,
            'language' => $language,
            'status' => $status,
            'header' => $api['header'] ?? null,
            'body' => $bodyText,
            'footer' => $api['footer'] ?? null,
            'buttons' => $api['buttons'] ?? null,
            'body_params_count' => (int)$bodyParamsCount,
            'meta' => $api,
            'last_synced_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * sendTemplateMessage already implemented in previous version.
     * Keep it here (not repeated) if needed by other parts.
     */
}
