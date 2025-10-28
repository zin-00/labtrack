<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComputerActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'computer_id',
        'activity_type',
        'reason',
        'details',
        'ip_address',
        'logged_at'
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    /**
     * Get the computer that owns the activity log.
     */
    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }
}
