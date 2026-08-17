<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_items', function (Blueprint $table) {
            $table->decimal('quantity', 18, 3)->default(1)->change();
            $table->decimal('remaining_quantity', 18, 3)->nullable()->change();
            $table->decimal('other_quantity', 18, 3)->nullable()->change();
            $table->decimal('remaining_other_quantity', 18, 3)->nullable()->change();
        });

        Schema::table('outbound_warehouse_items', function (Blueprint $table) {
            $table->decimal('quantity', 18, 3)->nullable()->change();
            $table->decimal('other_quantity', 18, 3)->nullable()->change();
        });

        Schema::table('outbounds', function (Blueprint $table) {
            $table->decimal('quantity_packages', 18, 3)->nullable()->change();
        });

        Schema::table('enter_requests', function (Blueprint $table) {
            $table->decimal('quantity_packages', 18, 3)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_items', function (Blueprint $table) {
            $table->double('quantity')->default(1)->change();
            $table->double('remaining_quantity')->nullable()->change();
            $table->double('other_quantity')->nullable()->change();
            $table->double('remaining_other_quantity')->nullable()->change();
        });

        Schema::table('outbound_warehouse_items', function (Blueprint $table) {
            $table->double('quantity')->nullable()->change();
            $table->double('other_quantity')->nullable()->change();
        });

        Schema::table('outbounds', function (Blueprint $table) {
            $table->double('quantity_packages')->nullable()->change();
        });

        Schema::table('enter_requests', function (Blueprint $table) {
            $table->double('quantity_packages')->nullable()->change();
        });
    }
};
