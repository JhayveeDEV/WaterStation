<?php

namespace App\Services;

use App\Events\UserNotificationCreated;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Sms\SmsGatewayContract;

class NotificationService
{
    public function __construct(private readonly SmsGatewayContract $sms) {}

    /**
     * Always creates the in-app notification. Pass $smsMessage to also text the
     * customer — reserved for the handful of moments that actually warrant an
     * SMS (order confirmed, out for delivery, delivered, cancelled, paid), not
     * every minor status change, since SMS costs real money per message.
     */
    public function send(User $user, string $title, string $message, string $type = 'info', ?string $url = null, ?string $smsMessage = null): UserNotification
    {
        $notification = $user->appNotifications()->create([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'url' => $url,
        ]);

        event(new UserNotificationCreated($notification));

        if ($smsMessage && $user->phone) {
            $this->sms->send($user->phone, $smsMessage);
        }

        return $notification;
    }

    /** @param  iterable<User>  $users */
    public function sendToMany(iterable $users, string $title, string $message, string $type = 'info', ?string $url = null): void
    {
        foreach ($users as $user) {
            $this->send($user, $title, $message, $type, $url);
        }
    }
}
