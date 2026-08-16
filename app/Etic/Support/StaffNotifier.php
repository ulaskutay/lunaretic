<?php

namespace App\Etic\Support;

use Filament\Notifications\Notification;
use Lunar\Admin\Models\Staff;

class StaffNotifier
{
    public function send(?int $staffId, string $title, string $body, bool $success = true): void
    {
        if (! $staffId) {
            return;
        }

        $staff = Staff::query()->find($staffId);

        if (! $staff) {
            return;
        }

        $notification = Notification::make()
            ->title($title)
            ->body($body);

        ($success ? $notification->success() : $notification->danger())
            ->sendToDatabase($staff, isEventDispatched: true);
    }
}
