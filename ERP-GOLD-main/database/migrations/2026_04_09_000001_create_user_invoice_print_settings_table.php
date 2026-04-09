<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_invoice_print_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('format')->default('a4');
            $table->boolean('show_header')->default(true);
            $table->boolean('show_footer')->default(true);
            $table->string('template')->default('classic');
            $table->string('orientation')->default('portrait');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_invoice_print_settings');
    }
};
