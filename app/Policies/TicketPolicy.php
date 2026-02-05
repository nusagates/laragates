<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Ticket;

class TicketPolicy
{
    /**
     * Reply ticket
     */
    public function reply(User $user, Ticket $ticket): bool
    {
        // Ticket closed → hanya admin
        if ($ticket->status === 'closed') {
            return $user->role === 'admin';
        }

        return true;
    }

    /**
     * Assign agent
     */
    public function assign(User $user): bool
    {
        // CS tidak boleh assign
        return $user->role === 'admin';
    }

    /**
     * Update status
     */
    public function updateStatus(User $user, Ticket $ticket, string $newStatus): bool
    {
        // Closed → hanya admin boleh reopen
        if ($ticket->status === 'closed' && $newStatus !== 'closed') {
            return $user->role === 'admin';
        }

        return true;
    }
}
