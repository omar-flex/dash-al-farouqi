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
            $table->boolean('clearance_company_representative')->default(false)->after('warehouse_id');
            $table->boolean('scanning_archiving')->default(false)->after('warehouse_id');
            $table->boolean('customs_department_representative')->default(false)->after('warehouse_id');
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
