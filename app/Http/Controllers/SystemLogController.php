<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemLogController extends Controller
{
    /**
     * DASHBOARD LOG
     */
    public function index(Request $request)
    {
        $query = SystemLog::query()->orderByDesc('created_at');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('action', 'like', "%{$request->q}%")
                  ->orWhere('route', 'like', "%{$request->q}%");
            });
        }

        return Inertia::render('SystemLogs/Index', [
            'logs' => $query->paginate(25)->withQueryString(),
            'filters' => $request->only('category', 'user_id', 'q'),
        ]);
    }

    /**
     * EXPORT CSV
     */
    public function export(Request $request): StreamedResponse
    {
        $filename = 'system_logs_' . now()->format('Ymd_His') . '.csv';

        $logs = SystemLog::orderByDesc('created_at')->get();

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Time',
                'User ID',
                'Role',
                'Category',
                'Action',
                'Route',
                'Method',
                'IP',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at,
                    $log->user_id,
                    $log->role,
                    $log->category,
                    $log->action,
                    $log->route,
                    $log->method,
                    $log->ip,
                ]);
            }

            fclose($handle);
        }, $filename);
    }
}
