<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Every signed-in user has one private channel for their own notifications and order updates.
Broadcast::channel('users.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

// Staff and admins share a channel that carries every order change (live order board).
// Deliberately based on role, not on the "process orders" permission — that
// permission is independently toggleable from Admin > Roles, and viewing the
// live board shouldn't disappear just because someone can't act on orders.
Broadcast::channel('staff', function (User $user) {
    return $user->status === 'active' && $user->hasAnyRole(['staff', 'admin']);
});
