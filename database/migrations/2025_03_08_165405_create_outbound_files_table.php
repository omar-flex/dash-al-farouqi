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
        Schema::create('outbound_files', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->nullable();
            $table->string('path')->nullable();
            $table->string('extension')->nullable();
            $table->foreignId('outbound_id')->nullable()->constrained('outbounds')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbound_files');
    }
};
