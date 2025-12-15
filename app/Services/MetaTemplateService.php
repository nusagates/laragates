<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaTemplateService
{
    public function fetchTemplates()
    {
        try {
            $token = config('services.waba.access_token');
            $bizId = config('services.waba.business_id');
            $base  = config('services.waba.base_url');
            $ver   = config('services.waba.version', 'v21.0');

            if (!$token || !$bizId || !$base) {
                throw new \Exception("Missing WABA credentials (token/business_id/base_url)");
            }

            $url = "{$base}/{$ver}/{$bizId}/message_templates";

            $response = Http::withToken($token)->get($url);

            if (!$response->ok()) {
                throw new \Exception("Meta API error: " . $response->body());
            }

            $data = $response->json()['data'] ?? [];

            return collect($data)->map(function ($t) {
                return [
                    'remote_id'  => $t['id'],
                    'name'       => $t['name'],
                    'category'   => $t['category'] ?? '-',
                    'language'   => $t['language'] ?? 'id',
                    'status'     => $t['status'] ?? 'UNKNOWN',
                    'components' => $t['components'] ?? [],
                ];
            })->toArray();

        } catch (\Throwable $e) {
            Log::error("MetaTemplateService fetchTemplates error: ".$e->getMessage());
            throw $e;
        }
    }
}
