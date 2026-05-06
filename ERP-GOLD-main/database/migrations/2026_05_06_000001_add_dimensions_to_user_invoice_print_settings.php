<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_invoice_print_settings', function (Blueprint $table) {
            $table->decimal('a4_margin_top', 5, 2)->nullable()->after('orientation');
            $table->decimal('a4_margin_right', 5, 2)->nullable()->after('a4_margin_top');
            $table->decimal('a4_margin_bottom', 5, 2)->nullable()->after('a4_margin_right');
            $table->decimal('a4_margin_left', 5, 2)->nullable()->after('a4_margin_bottom');
            $table->decimal('a4_header_height', 5, 2)->nullable()->after('a4_margin_left');
            $table->decimal('a4_footer_height', 5, 2)->nullable()->after('a4_header_height');
            $table->decimal('a4_content_offset_top', 5, 2)->nullable()->after('a4_footer_height');

            $table->decimal('a5_margin_top', 5, 2)->nullable()->after('a4_content_offset_top');
            $table->decimal('a5_margin_right', 5, 2)->nullable()->after('a5_margin_top');
            $table->decimal('a5_margin_bottom', 5, 2)->nullable()->after('a5_margin_right');
            $table->decimal('a5_margin_left', 5, 2)->nullable()->after('a5_margin_bottom');
            $table->decimal('a5_header_height', 5, 2)->nullable()->after('a5_margin_left');
            $table->decimal('a5_footer_height', 5, 2)->nullable()->after('a5_header_height');
            $table->decimal('a5_content_offset_top', 5, 2)->nullable()->after('a5_footer_height');

            $table->decimal('font_scale', 4, 2)->nullable()->after('a5_content_offset_top');
        });
    }

    public function down(): void
    {
        Schema::table('user_invoice_print_settings', function (Blueprint $table) {
            $table->dropColumn([
                'a4_margin_top', 'a4_margin_right', 'a4_margin_bottom', 'a4_margin_left',
                'a4_header_height', 'a4_footer_height', 'a4_content_offset_top',
                'a5_margin_top', 'a5_margin_right', 'a5_margin_bottom', 'a5_margin_left',
                'a5_header_height', 'a5_footer_height', 'a5_content_offset_top',
                'font_scale',
            ]);
        });
    }
};
