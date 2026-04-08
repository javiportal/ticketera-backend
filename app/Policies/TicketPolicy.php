<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function purchase(User $user): bool
    {
        return $user->hasRole('client');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id || $user->hasRole('admin');
    }
}