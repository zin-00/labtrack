<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'icon',
        'link',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId)->orWhereNull('user_id');
    }

    /**
     * Get the user that owns the notification
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Create a notification helper
     */
    public static function notify($userId, $type, $title, $message, $options = [])
    {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => $options['icon'] ?? null,
            'link' => $options['link'] ?? null,
            'data' => $options['data'] ?? null,
        ]);
    }

    /**
     * Create a broadcast notification (for all users)
     */
    public static function broadcast($type, $title, $message, $options = [])
    {
        return self::create([
            'user_id' => null, // null = broadcast to all
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => $options['icon'] ?? null,
            'link' => $options['link'] ?? null,
            'data' => $options['data'] ?? null,
        ]);
    }
}
