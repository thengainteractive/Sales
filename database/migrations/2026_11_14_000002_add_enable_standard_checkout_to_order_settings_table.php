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
        if (Schema::hasTable('order_settings')) {
            Schema::table('order_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('order_settings', 'enable_standard_checkout')) {
                    $table->boolean('enable_standard_checkout')->default(true)->after('business_address');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('order_settings')) {
            Schema::table('order_settings', function (Blueprint $table) {
                if (Schema::hasColumn('order_settings', 'enable_standard_checkout')) {
                    $table->dropColumn('enable_standard_checkout');
                }
            });
        }
    }
};
