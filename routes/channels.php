<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('stretcher-updates', function () {
    return true; // Public channel for now
});

// For private channels (if needed later):
// Broadcast::channel('stretcher-team.{teamId}', function ($user, $teamId) {
//     return $user->team_id === (int) $teamId;
// });