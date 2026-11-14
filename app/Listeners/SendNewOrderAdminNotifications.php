<?php

declare(strict_types=1);

namespace Modules\Sales\Listeners;

use App\Models\User;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Sales\Events\OrderPlaced;
use Modules\Sales\Models\OrderSetting;
use Modules\Sales\Notifications\AdminNewOrderNotification;

class SendNewOrderAdminNotifications
{
    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        if (class_exists(OrderSetting::class)) {
            $settings = OrderSetting::instance();
            if (! $settings->notify_admin_on_order) {
                return;
            }
        }

        $admins = $this->getAdminRecipients();

        if ($admins->isEmpty()) {
            Log::info("Order #{$order->order_number} placed, but no admin users found to notify.");
            return;
        }

        try {
            Notification::send($admins, new AdminNewOrderNotification($order));

            foreach ($admins as $admin) {
                DatabaseNotificationsSent::dispatch($admin);
            }
        } catch (\Throwable $e) {
            Log::error("Failed to send admin order notification for order #{$order->order_number}: {$e->getMessage()}", [
                'order_id' => $order->id,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Get the list of users eligible for order notifications.
     *
     * @return Collection<int, User>
     */
    protected function getAdminRecipients(): Collection
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'Super Admin');
        })
        ->orWhereHas('permissions', function ($query) {
            $query->where('name', 'view_orders');
        })
        ->get();
    }
}
