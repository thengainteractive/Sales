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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique()->index();
            $table->string('status')->default('pending')->index(); // pending, confirmed, processing, shipped, delivered, cancelled
            $table->string('customer_name');
            $table->string('customer_phone')->index();
            $table->string('customer_email')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('shipping_fee', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->string('payment_method')->default('cod'); // cod, etc.
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('type')->default('shipping'); // shipping, billing
            $table->string('full_name');
            $table->string('phone');
            $table->string('alternate_phone')->nullable();
            $table->string('address_line1'); // House/Building/Street
            $table->string('landmark')->nullable(); // Landmark
            $table->string('city'); // City/Town
            $table->string('district'); // District
            $table->string('state'); // State
            $table->string('pincode'); // Pin
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('line_total', 10, 2);
            $table->string('image')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('orders');
    }
};

