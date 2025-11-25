<?php

use App\Events\ComputerWentOffline;
use App\Events\MainEvent;
use App\Models\Computer;
use App\Models\ComputerActivityLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;




Artisan::command('inspire', function () {
    $this->comment(Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $offlineThreshold = now()->subMinutes(5);

    $computersToMarkOffline = Computer::with('laboratory')
        ->where('is_online', true) // only online computers
        ->where(function ($query) use ($offlineThreshold) {
            $query->whereNull('last_seen') // never reported
                  ->orWhere('last_seen', '<', $offlineThreshold); // heartbeat too old
        })
        ->get();

    $affected = 0;

    foreach ($computersToMarkOffline as $computer) {
        try {
            DB::beginTransaction();

            $computer->update(['is_online' => false]);
            $computer->update(['is_lock' => true]);
            $affected++;

            broadcast(new MainEvent('Computer', 'updated', $computer));

            ComputerActivityLog::create([
                'computer_id'   => $computer->id,
                'activity_type' => 'offline',
                'reason'        => $computer->last_seen ? 'missed_heartbeats' : 'no_heartbeat',
                'details'       => $computer->last_seen
                                    ? 'Automatically marked offline due to missed heartbeats'
                                    : 'Marked offline because no heartbeat was ever received',
                'ip_address'    => $computer->ip_address,
                'logged_at'     => now()
            ]);

            // broadcast(new ComputerWentOffline($computer, $computer->last_seen ? 'missed_heartbeats' : 'no_heartbeat'));
            broadcast(new MainEvent('computer', 'offline', $computer));
            DB::commit();

            logger()->info("Computer marked offline: {$computer->ip_address}", [
                'computer_id' => $computer->id,
                'pc_number'   => $computer->pc_number ?? 'N/A',
                'reason'      => $computer->last_seen ? 'missed_heartbeats' : 'no_heartbeat',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            logger()->error("Failed to mark computer offline: {$computer->ip_address}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    if ($affected > 0) {
        logger()->info("Marked {$affected} computers as offline");
    } else {
        logger()->info("ℹNo computers to mark offline this round");
    }
})->everyMinute()->name('check-computer-status');
