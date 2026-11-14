<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('order_settings')) {
            Schema::create('order_settings', function (Blueprint $table) {
                $table->id();
                $table->string('whatsapp_number')->nullable();
                $table->string('contact_phone')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_person')->nullable();
                $table->text('business_address')->nullable();
                $table->boolean('enable_standard_checkout')->default(true);
                $table->boolean('enable_whatsapp_checkout')->default(true);
                $table->boolean('notify_admin_on_order')->default(true);
                $table->string('admin_notification_email')->nullable();
                $table->string('whatsapp_order_prefix')->nullable()->default('New Order');
                $table->text('custom_order_message')->nullable();
                $table->string('order_number_prefix')->nullable()->default('ORD');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_settings');
    }
};
