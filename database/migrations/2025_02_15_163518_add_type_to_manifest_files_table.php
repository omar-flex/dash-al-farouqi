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
        Schema::table('manifest_files', function (Blueprint $table) {
            $table->integer('type')->default(7)->after('user_id');
            $table->dropForeign(['manifest_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manifest_files', function (Blueprint $table) {
            //
        });
    }
};
