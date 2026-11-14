<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'access_token',
        'status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'subtotal',
        'shipping_fee',
        'tax_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'customer_notes',
        'admin_notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->access_token)) {
                $order->access_token = Str::random(64);
            }
        });

        static::saving(function (Order $order) {
            foreach ([
                'customer_name',
                'customer_phone',
                'customer_email',
                'customer_notes',
                'admin_notes',
            ] as $field) {
                if (isset($order->attributes[$field]) && is_string($order->attributes[$field])) {
                    $order->attributes[$field] = trim(strip_tags((string) $order->attributes[$field]));
                }
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function shippingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type', 'shipping');
    }

    public static function generateOrderNumber(): string
    {
        $prefixStr = 'ORD';
        if (class_exists(OrderSetting::class)) {
            $configured = trim((string) OrderSetting::instance()->order_number_prefix);
            if (! empty($configured)) {
                $prefixStr = strtoupper($configured);
            }
        }

        $prefix = $prefixStr . '-' . date('Ymd');
        do {
            $random = strtoupper(Str::random(5));
            $number = "{$prefix}-{$random}";
        } while (static::where('order_number', $number)->exists());

        return $number;
    }
}

