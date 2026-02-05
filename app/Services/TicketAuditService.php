<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class TicketAuditService
{
    public static function log(
        Ticket $ticket,
        string $action,
        array|null $old = null,
        array|null $new = null,
        Request|null $request = null
    ): void {
        TicketAuditLog::create([
            'ticket_id'  => $ticket->id,
            'user_id'    => Auth::id(),
            'action'     => $action,
            'old_value'  => $old,
            'new_value'  => $new,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
