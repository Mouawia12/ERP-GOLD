<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('stone_type_1')->nullable()->after('no_metal_type');
            $table->string('stone_type_2')->nullable()->after('stone_type_1');
            $table->string('stone_size_1')->nullable()->after('stone_type_2');
            $table->string('stone_size_2')->nullable()->after('stone_size_1');
            $table->string('stone_clarity')->nullable()->after('stone_size_2');
            $table->string('stone_color')->nullable()->after('stone_clarity');
            $table->double('gold_weight_18k')->default(0)->after('stone_color');
            $table->text('metal_notes')->nullable()->after('gold_weight_18k');
            $table->string('brand')->nullable()->after('metal_notes');
            $table->string('model_number')->nullable()->after('brand');
            $table->string('country_of_origin')->nullable()->after('model_number');
            $table->double('impurity_percentage')->default(0)->after('country_of_origin');
            $table->string('certificate_file')->nullable()->after('impurity_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'stone_type_1',
                'stone_type_2',
                'stone_size_1',
                'stone_size_2',
                'stone_clarity',
                'stone_color',
                'gold_weight_18k',
                'metal_notes',
                'brand',
                'model_number',
                'country_of_origin',
                'impurity_percentage',
                'certificate_file',
            ]);
        });
    }
};
