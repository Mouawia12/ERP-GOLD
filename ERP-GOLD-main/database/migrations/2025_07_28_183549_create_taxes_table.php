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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('rate')->nullable()->comment('tax rate percentage');
            $table->enum('zatca_code', ['E', 'S', 'Z', 'O'])->nullable();
            $table->string('zatca_exemption_code')->nullable();
            $table->longText('zatca_exemption_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
