<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class OrderSetting extends Model
{
    use HasFactory;

    protected $table = 'order_settings';

    protected $fillable = [
        'whatsapp_number',
        'contact_phone',
        'contact_email',
        'contact_person',
        'business_address',
        'enable_standard_checkout',
        'enable_whatsapp_checkout',
        'shipping_text',
        'notify_admin_on_order',
        'admin_notification_email',
        'whatsapp_order_prefix',
        'custom_order_message',
        'order_number_prefix',
    ];

    protected $casts = [
        'enable_standard_checkout' => 'boolean',
        'enable_whatsapp_checkout' => 'boolean',
        'notify_admin_on_order' => 'boolean',
    ];

    /**
     * Singleton instance helper for order settings.
     */
    public static function instance(): self
    {
        if (! Schema::hasTable('order_settings')) {
            return new static([
                'id' => 1,
                'enable_standard_checkout' => true,
                'enable_whatsapp_checkout' => true,
                'shipping_text' => 'Shipping charges extra',
                'notify_admin_on_order' => true,
                'whatsapp_order_prefix' => 'New Order',
                'order_number_prefix' => 'ORD',
            ]);
        }

        return static::firstOrCreate(['id' => 1], [
            'enable_standard_checkout' => true,
            'enable_whatsapp_checkout' => true,
            'shipping_text' => 'Shipping charges extra',
            'notify_admin_on_order' => true,
            'whatsapp_order_prefix' => 'New Order',
            'order_number_prefix' => 'ORD',
        ]);
    }

    /**
     * Get the configured checkout shipping text.
     */
    public static function getShippingText(): string
    {
        $text = static::instance()->shipping_text;

        return ! empty(trim((string) $text)) ? trim((string) $text) : 'Shipping charges extra';
    }

    /**
     * Determine if standard website checkout (COD) is enabled.
     */
    public static function isStandardCheckoutEnabled(): bool
    {
        return (bool) (static::instance()->enable_standard_checkout ?? true);
    }

    /**
     * Determine if WhatsApp checkout is enabled and properly configured with a phone number.
     */
    public static function isWhatsAppCheckoutEnabled(): bool
    {
        $instance = static::instance();
        if (! ($instance->enable_whatsapp_checkout ?? true)) {
            return false;
        }

        return ! empty(static::getWhatsAppNumber());
    }

    /**
     * Get cleaned WhatsApp number digits, with fallback to Frontend SiteSetting if available.
     */
    public static function getWhatsAppNumber(): ?string
    {
        $instance = static::instance();
        $phone = preg_replace('/[^0-9]/', '', (string) ($instance->whatsapp_number ?? ''));

        if (! empty($phone)) {
            return $phone;
        }

        // Fallback to Frontend SiteSetting if exists
        if (class_exists(\Modules\Frontend\Models\SiteSetting::class)) {
            $frontendSetting = \Modules\Frontend\Models\SiteSetting::instance();
            $fallback = preg_replace('/[^0-9]/', '', (string) ($frontendSetting->whatsapp_number ?? ''));
            if (! empty($fallback)) {
                return $fallback;
            }
        }

        return null;
    }

    /**
     * Get primary contact phone, with fallback to Frontend SiteSetting if available.
     */
    public static function getContactPhone(): ?string
    {
        $instance = static::instance();
        if (! empty($instance->contact_phone)) {
            return $instance->contact_phone;
        }

        if (class_exists(\Modules\Frontend\Models\SiteSetting::class)) {
            $frontendSetting = \Modules\Frontend\Models\SiteSetting::instance();
            return $frontendSetting->phone ?? null;
        }

        return null;
    }

    /**
     * Get primary contact email, with fallback to Frontend SiteSetting if available.
     */
    public static function getContactEmail(): ?string
    {
        $instance = static::instance();
        if (! empty($instance->contact_email)) {
            return $instance->contact_email;
        }

        if (class_exists(\Modules\Frontend\Models\SiteSetting::class)) {
            $frontendSetting = \Modules\Frontend\Models\SiteSetting::instance();
            return $frontendSetting->email ?? null;
        }

        return null;
    }
}
