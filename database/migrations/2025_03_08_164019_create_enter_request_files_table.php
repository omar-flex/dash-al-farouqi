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
        Schema::create('enter_request_files', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->nullable();
            $table->string('path')->nullable();
            $table->string('extension')->nullable();
            $table->foreignId('enter_request_id')->nullable()->constrained('enter_requests')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enter_request_files');
    }
};
