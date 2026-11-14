<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'type',
        'full_name',
        'phone',
        'alternate_phone',
        'address_line1',
        'landmark',
        'city',
        'district',
        'state',
        'pincode',
    ];

    protected static function booted(): void
    {
        static::saving(function (OrderAddress $address) {
            foreach ([
                'full_name',
                'phone',
                'alternate_phone',
                'address_line1',
                'landmark',
                'city',
                'district',
                'state',
                'pincode',
            ] as $field) {
                if (isset($address->attributes[$field]) && is_string($address->attributes[$field])) {
                    $address->attributes[$field] = trim(strip_tags((string) $address->attributes[$field]));
                }
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->landmark ? "Landmark: {$this->landmark}" : null,
            $this->city,
            $this->district,
            $this->state . ' - ' . $this->pincode,
        ]);

        return implode(', ', $parts);
    }

    public function getMaskedPhoneAttribute(): string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $this->phone);
        $len = strlen($phone);
        if ($len <= 4) {
            return (string) $this->phone;
        }
        return substr($phone, 0, 2) . str_repeat('*', $len - 4) . substr($phone, -2);
    }

    public function getMaskedAlternatePhoneAttribute(): ?string
    {
        if (empty($this->alternate_phone)) {
            return null;
        }
        $phone = preg_replace('/[^0-9]/', '', (string) $this->alternate_phone);
        $len = strlen($phone);
        if ($len <= 4) {
            return (string) $this->alternate_phone;
        }
        return substr($phone, 0, 2) . str_repeat('*', $len - 4) . substr($phone, -2);
    }
}

