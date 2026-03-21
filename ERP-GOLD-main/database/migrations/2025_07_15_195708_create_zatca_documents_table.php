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
        Schema::create('zatca_documents', function (Blueprint $table) {
            $table->id();
            $table->string('icv');
            $table->string('uuid');
            $table->string('hash')->nullable();
            $table->longText('xml')->nullable();
            $table->boolean('sent_to_zatca')->default(false);
            $table->string('sent_to_zatca_status')->default('NEW');
            $table->datetime('signing_time')->nullable();
            $table->longText('qr_value')->nullable();
            $table->longText('response')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->nullableMorphs('invoiceable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zatca_documents');
    }
};
