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
        Schema::create('branch_zatca_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('egs_serial_number')->nullable();
            $table->string('business_category')->nullable();
            $table->string('otp')->nullable();
            $table->enum('invoice_type', config('settings.invoices_issuing_types'))->default(config('settings.invoices_issuing_types')[0]);
            $table->boolean('is_production')->default(true);
            $table->longText('cnf')->nullable();
            $table->longText('private_key')->nullable();
            $table->longText('public_key')->nullable();
            $table->longText('csr_request')->nullable();
            $table->longText('certificate')->nullable();
            $table->string('secret')->nullable();
            $table->string('csid')->nullable();
            $table->longText('production_certificate')->nullable();
            $table->string('production_secret')->nullable();
            $table->string('production_csid')->nullable();
            $table->enum('zatca_stage', ['developer-portal', 'simulation', 'core'])->default('developer-portal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_zatca_settings');
    }
};
