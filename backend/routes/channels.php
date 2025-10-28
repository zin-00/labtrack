<?php

use App\Models\Computer;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('computers', function() {
    return true;
});

Broadcast::channel('computer-status.{ip_address}', function ($ip_address) {
    // Just return true if you want public access or add some IP validation if needed
    return true;
});
