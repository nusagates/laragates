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
            $this->error('WABA business_id not provided.');
            return Command::FAILURE;
        }

        $this->info("Fetching templates from WABA business_id={$businessId} ...");

        try {
            $templates = $waba->getTemplates($businessId, 250);

            DB::transaction(function () use ($templates, $waba) {
                foreach ($templates as $t) {
                    $payload = $waba->normalizeTemplatePayload($t);

                    WhatsappTemplate::updateOrCreate(
                        ['remote_id' => $payload['remote_id']],
                        $payload
                    );
                }
            });

            $this->info('Sync completed successfully.');
            return Command::SUCCESS;

        } catch (Throwable $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
