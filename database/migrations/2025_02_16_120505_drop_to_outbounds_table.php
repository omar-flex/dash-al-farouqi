<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('outbounds', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropColumn('warehouse_id');
            $table->dropColumn('country_id');
            $table->dropColumn('cpm');
            $table->dropColumn('cpm_calculated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outbounds', function (Blueprint $table) {
            //
        });
    }
};
