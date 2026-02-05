<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiRequestLog;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiReportController extends Controller
{
    /**
     * Export AI audit trail ke CSV
     */
    public function exportCsv(): StreamedResponse
    {
        $fileName = 'ai_audit_report_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $columns = [
            'ID',
            'User ID',
            'Chat Session ID',
            'Action',
            'Model',
            'Response Status',
            'Latency (ms)',
            'Created At',
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');

            // header
            fputcsv($file, $columns);

            AiRequestLog::orderBy('created_at', 'desc')
                ->chunk(500, function ($logs) use ($file) {
                    foreach ($logs as $log) {
                        fputcsv($file, [
                            $log->id,
                            $log->user_id,
                            $log->chat_session_id,
                            $log->action,
                            $log->model,
                            $log->response_status,
                            $log->latency_ms,
                            $log->created_at,
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
