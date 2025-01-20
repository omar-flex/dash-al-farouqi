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
        Schema::create('enter_requests', function (Blueprint $table) {
            $table->id();
            $table->string('bound_number')->nullable();
            $table->string('manifest_bound_number')->nullable();
            $table->string('manifest_type_number')->nullable();
            $table->string('customs_entry_center')->nullable();
            $table->integer('manifest_year')->nullable();
            $table->date('manifest_date')->nullable();
            $table->string('organize_center')->nullable();
            $table->string('quantity_car')->nullable();
            $table->integer('quantity_packages')->nullable();
            $table->text('general_description_goods')->nullable();
            $table->float('gross_weight')->nullable();
            $table->float('net_weight')->nullable();
            $table->float('cpm')->nullable();
            $table->float('cpm_calculated')->nullable();
            $table->float('cpm_result')->nullable();
            $table->float('total_cost')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('enter_request_statuses')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enter_requests');
    }
};
