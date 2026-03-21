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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('street_name')->nullable();
            $table->string('building_number')->nullable();
            $table->string('plot_identification')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('tax_number')->nullable()->comment('company tax number');
            $table->string('crn_number')->nullable()->comment('company registration number');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('cascade');
            $table->enum('type', ['customer', 'supplier'])->default('customer');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
