<?php

namespace App\Services;

use App\Events\MainEvent;
use App\Models\Notification;

class NotificationService
{
    /**
     * Send notification to a specific user
     */
    public static function notifyUser($userId, $type, $title, $message, $options = [])
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => $options['icon'] ?? null,
            'link' => $options['link'] ?? null,
            'data' => $options['data'] ?? null,
        ]);

        // Broadcast the notification
        broadcast(new MainEvent('Notification', 'created', $notification));

        return $notification;
    }

    /**
     * Broadcast notification to all users
     */
    public static function broadcast($type, $title, $message, $options = [])
    {
        $notification = Notification::create([
            'user_id' => null,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => $options['icon'] ?? null,
            'link' => $options['link'] ?? null,
            'data' => $options['data'] ?? null,
        ]);

        // Broadcast the notification
        broadcast(new MainEvent('Notification', 'created', $notification));

        return $notification;
    }

    /**
     * Send info notification
     */
    public static function info($userId, $title, $message, $options = [])
    {
        return self::notifyUser($userId, 'info', $title, $message, $options);
    }

    /**
     * Send success notification
     */
    public static function success($userId, $title, $message, $options = [])
    {
        return self::notifyUser($userId, 'success', $title, $message, $options);
    }

    /**
     * Send warning notification
     */
    public static function warning($userId, $title, $message, $options = [])
    {
        return self::notifyUser($userId, 'warning', $title, $message, $options);
    }

    /**
     * Send error notification
     */
    public static function error($userId, $title, $message, $options = [])
    {
        return self::notifyUser($userId, 'error', $title, $message, $options);
    }

    /**
     * Send report notification
     */
    public static function report($userId, $title, $message, $options = [])
    {
        $options['link'] = $options['link'] ?? '/reports';
        return self::notifyUser($userId, 'report', $title, $message, $options);
    }

    /**
     * Send computer status notification
     */
    public static function computerStatus($userId, $title, $message, $options = [])
    {
        $options['link'] = $options['link'] ?? '/computers';
        return self::notifyUser($userId, 'computer', $title, $message, $options);
    }

    /**
     * Send access request notification
     */
    public static function accessRequest($userId, $title, $message, $options = [])
    {
        $options['link'] = $options['link'] ?? '/request-access';
        return self::notifyUser($userId, 'access_request', $title, $message, $options);
    }
}
