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
        Schema::table('enter_requests', function (Blueprint $table) {
            $table->date('date')->nullable()->after('manifest_date');
            $table->text('notes')->nullable()->after('general_description_goods');
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('status_id');
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
