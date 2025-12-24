<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        /**
         * ===============================
         * REQUEST FILTERS
         * ===============================
         */
        $source = $request->get('source'); // system | iam | ticket | sla | behavior
        $q      = $request->get('q');      // global search

        /**
         * ===============================
         * AGGREGATED LOG QUERY (UNION)
         * ===============================
         */
        $logs = DB::query()->fromSub(function ($qbuilder) {

            /**
             * SYSTEM LOGS
             */
            $qbuilder
                ->selectRaw("
                    id,
                    'system' as source,
                    event,
                    entity_type as description,
                    user_id,
                    user_role as role,
                    ip_address as ip,
                    user_agent,
                    created_at,
                    meta,
                    'info' as severity
                ")
                ->from('system_logs')

            /**
             * IAM LOGS
             */
            ->unionAll(
                DB::table('iam_logs')->selectRaw("
                    id,
                    'iam' as source,
                    action as event,
                    CONCAT(
                        'actor:', actor_id,
                        ', target:', IFNULL(target_user_id, '-')
                    ) as description,
                    actor_id as user_id,
                    NULL as role,
                    ip_address as ip,
                    user_agent,
                    created_at,
                    NULL as meta,
                    CASE
                        WHEN action LIKE '%DELETE%' THEN 'critical'
                        ELSE 'warning'
                    END as severity
                ")
            )

            /**
             * TICKET AUDIT LOGS
             */
            ->unionAll(
                DB::table('ticket_audit_logs')->selectRaw("
                    id,
                    'ticket' as source,
                    action as event,
                    CONCAT('Ticket #', ticket_id) as description,
                    user_id,
                    NULL as role,
                    ip_address as ip,
                    user_agent,
                    created_at,
                    JSON_OBJECT(
                        'old', old_value,
                        'new', new_value
                    ) as meta,
                    'info' as severity
                ")
            )

            /**
             * TICKET SLA LOGS
             */
            ->unionAll(
                DB::table('ticket_sla_logs')->selectRaw("
                    id,
                    'sla' as source,
                    status as event,
                    rule as description,
                    NULL as user_id,
                    NULL as role,
                    NULL as ip,
                    NULL as user_agent,
                    triggered_at as created_at,
                    meta,
                    'critical' as severity
                ")
            )

            /**
             * USER BEHAVIOR LOGS
             */
            ->unionAll(
                DB::table('user_behavior_logs')->selectRaw("
                    id,
                    'behavior' as source,
                    action as event,
                    endpoint as description,
                    user_id,
                    role,
                    ip,
                    user_agent,
                    created_at,
                    meta,
                    'info' as severity
                ")
            );

        }, 'logs');

        /**
         * ===============================
         * FILTER BY SOURCE
         * ===============================
         */
        if ($source) {
            $logs->where('source', $source);
        }

        /**
         * ===============================
         * GLOBAL SEARCH
         * ===============================
         */
        if ($q) {
            $logs->where(function ($sub) use ($q) {
                $sub->where('event', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        /**
         * ===============================
         * FINAL RESPONSE
         * ===============================
         */
        return Inertia::render('SystemLogs/Index', [
            'logs' => $logs
                ->orderByDesc('created_at')
                ->paginate(30)
                ->withQueryString(),

            'filters' => [
                'source' => $source,
                'q'      => $q,
            ],
        ]);
    }

    public function export(Request $request)
{
    $source = $request->get('source');
    $q      = $request->get('q');

    $logs = DB::query()
        ->fromSub(function ($qbuilder) {

            $qbuilder
                ->selectRaw("
                    id,
                    'system' as source,
                    event,
                    entity_type as description,
                    user_id,
                    user_role as role,
                    ip_address as ip,
                    user_agent,
                    created_at,
                    meta,
                    'info' as severity
                ")
                ->from('system_logs')

                ->unionAll(
                    DB::table('iam_logs')->selectRaw("
                        id,
                        'iam' as source,
                        action as event,
                        CONCAT('actor:', actor_id, ', target:', IFNULL(target_user_id, '-')) as description,
                        actor_id as user_id,
                        NULL as role,
                        ip_address as ip,
                        user_agent,
                        created_at,
                        NULL as meta,
                        CASE
                            WHEN action LIKE '%DELETE%' THEN 'critical'
                            ELSE 'warning'
                        END as severity
                    ")
                )

                ->unionAll(
                    DB::table('ticket_audit_logs')->selectRaw("
                        id,
                        'ticket' as source,
                        action as event,
                        CONCAT('Ticket #', ticket_id) as description,
                        user_id,
                        NULL as role,
                        ip_address as ip,
                        user_agent,
                        created_at,
                        JSON_OBJECT('old', old_value, 'new', new_value) as meta,
                        'info' as severity
                    ")
                )

                ->unionAll(
                    DB::table('ticket_sla_logs')->selectRaw("
                        id,
                        'sla' as source,
                        status as event,
                        rule as description,
                        NULL as user_id,
                        NULL as role,
                        NULL as ip,
                        NULL as user_agent,
                        triggered_at as created_at,
                        meta,
                        'critical' as severity
                    ")
                )

                ->unionAll(
                    DB::table('user_behavior_logs')->selectRaw("
                        id,
                        'behavior' as source,
                        action as event,
                        endpoint as description,
                        user_id,
                        role,
                        ip,
                        user_agent,
                        created_at,
                        meta,
                        'info' as severity
                    ")
                );

        }, 'logs');

    if ($source) {
        $logs->where('source', $source);
    }

    if ($q) {
        $logs->where(function ($sub) use ($q) {
            $sub->where('event', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%");
        });
    }

    $filename = 'system_logs_' . now()->format('Ymd_His') . '.csv';

    return response()->streamDownload(function () use ($logs) {

        $handle = fopen('php://output', 'w');

        // ✅ UTF-8 BOM (WAJIB BIAR EXCEL BACA BENAR)
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        // ✅ HEADER (PAKAI ; BIAR EXCEL INDONESIA RAPI)
        fputcsv($handle, [
            'Time',
            'Source',
            'Severity',
            'Event',
            'Description',
            'User ID',
            'Role',
            'IP Address',
        ], ';');

        foreach ($logs->orderByDesc('created_at')->get() as $log) {

    $time = $log->created_at
        ? "'" . \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i:s')
        : '-';

    $event = ucwords(str_replace('_', ' ', $log->event));

    $description = $log->source === 'sla' && $log->description
        ? ucwords(str_replace('_', ' ', $log->description)) . ' SLA Breach'
        : ($log->description ?: '-');

    fputcsv($handle, [
        $time,
        strtoupper($log->source),
        strtoupper($log->severity),
        $event,
        $description,
        $log->user_id ?: '-',
        $log->role ?: '-',
        $log->ip ?: '-',
    ], ';');
}



        fclose($handle);

    }, $filename, [
        'Content-Type' => 'text/csv; charset=UTF-8',
    ]);
}

}
