<?php

namespace App\Etic\Store\Notifications;

use App\Etic\Store\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StoreInvitation extends Notification
{
    use Queueable;

    public function __construct(private Store $store, private string $temporaryPassword) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('etic.tenancy.invite.subject', ['store' => $this->store->name]))
            ->line(__('etic.tenancy.invite.intro', ['store' => $this->store->name]))
            ->line(__('etic.tenancy.invite.password', ['password' => $this->temporaryPassword]))
            ->action(__('etic.tenancy.invite.action'), $this->store->adminUrl())
            ->line(__('etic.tenancy.invite.outro'));
    }
}
