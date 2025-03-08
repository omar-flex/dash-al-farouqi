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
        Schema::table('enter_requests', function (Blueprint $table) {
            $table->float('cpm_weight_ration_wh')->nullable()->after('total_cost');
            $table->float('cpm_weight_ration')->nullable()->after('total_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enter_requests', function (Blueprint $table) {
            //
        });
    }
};
