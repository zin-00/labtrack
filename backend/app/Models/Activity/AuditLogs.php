<?php

namespace App\Models\Activity;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogs extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_data',
        'new_data',
        'description',
        'ip_address'
    ];

    protected $casts = [
        'new_data' => 'array',
        'old_data' => 'array',
        'entity_id' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
