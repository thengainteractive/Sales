<?php

declare(strict_types=1);

namespace Modules\Sales\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Sales\Filament\Resources\OrderResource;
use Modules\Sales\Models\Order;

class AdminNewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database storage.
     * Compatible with Filament's DatabaseNotifications component.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $amount = number_format((float) $this->order->total_amount, 2);
        $orderUrl = OrderResource::getUrl('view', ['record' => $this->order]);

        $filamentNotification = FilamentNotification::make()
            ->title('New Order Received! 🛍️')
            ->icon('heroicon-o-shopping-bag')
            ->iconColor('success')
            ->body("Order #{$this->order->order_number} received from {$this->order->customer_name} for ₹{$amount}")
            ->actions([
                Action::make('view')
                    ->button()
                    ->url($orderUrl),
            ]);

        $data = $filamentNotification->getDatabaseMessage();
        $data['order_id'] = $this->order->id;
        $data['order_number'] = $this->order->order_number;

        return $data;
    }
}
