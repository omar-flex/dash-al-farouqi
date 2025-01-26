<?php

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
        Schema::table('enter_request_cars', function (Blueprint $table) {
            $table->boolean('is_tracking_device')->default(false)->after('is_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enter_request_cars', function (Blueprint $table) {
            //
        });
    }
};
