<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WabaService;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\DB;
use Throwable;

class WabaSyncTemplates extends Command
{
    protected $signature = 'waba:sync-templates {business_id?} {--force}';
    protected $description = 'Sync WhatsApp templates from Meta (WABA) into whatsapp_templates table';

    public function handle(WabaService $waba)
    {
        $businessId = $this->argument('business_id') ?: config('services.waba.business_id');

        if (!$businessId) {
            $this->error('WABA business_id not provided. Provide via argument or set WABA_BUSINESS_ID in .env');
            return 1;
        }

        $this->info("Fetching templates from WABA business_id={$businessId} ...");

        try {
            $templates = $waba->getTemplates($businessId, 250);

            $this->info('Found ' . count($templates) . ' templates. Starting upsert...');

            DB::transaction(function () use ($templates) {
                foreach ($templates as $apiTpl) {
                    $payload = app(\App\Services\WabaService::class)->normalizeTemplatePayload($apiTpl);

                    // upsert by remote_id or name
                    $where = ['remote_id' => $payload['remote_id'] ?? null];
                    if (empty($where['remote_id'])) {
                        $where = ['name' => $payload['name']];
                    }

                    if (empty($where['remote_id']) && empty($where['name'])) {
                        // skip malformed
                        continue;
                    }

                    $existing = WhatsappTemplate::where(function($q) use ($where) {
                        if (!empty($where['remote_id'])) $q->where('remote_id', $where['remote_id']);
                        if (!empty($where['name'])) $q->orWhere('name', $where['name']);
                    })->first();

                    if ($existing) {
                        $existing->update($payload);
                        $this->line("Updated template: " . ($existing->name ?? $existing->remote_id));
                    } else {
                        WhatsappTemplate::create($payload);
                        $this->line("Inserted template: " . ($payload['name'] ?? $payload['remote_id']));
                    }
                }
            });

            $this->info('Sync completed successfully.');
            return 0;

        } catch (Throwable $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return 1;
        }
    }
}
