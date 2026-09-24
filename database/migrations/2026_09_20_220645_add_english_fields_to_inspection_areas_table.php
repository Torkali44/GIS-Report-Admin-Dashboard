<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_areas', function (Blueprint $table) {
            if (! Schema::hasColumn('inspection_areas', 'name_en')) {
                $table->string('name_en')->nullable()->after('name');
            }
            if (! Schema::hasColumn('inspection_areas', 'additional_info_en')) {
                $table->text('additional_info_en')->nullable()->after('additional_info');
            }
            if (! Schema::hasColumn('inspection_areas', 'recommendations_en')) {
                $table->text('recommendations_en')->nullable()->after('recommendations');
            }
            if (! Schema::hasColumn('inspection_areas', 'notes_json_en')) {
                $table->json('notes_json_en')->nullable()->after('notes_json');
            }
            if (! Schema::hasColumn('inspection_areas', 'recommendations_json_en')) {
                $table->json('recommendations_json_en')->nullable()->after('recommendations_json');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inspection_areas', function (Blueprint $table) {
            $cols = [];
            foreach ([
                'name_en',
                'additional_info_en',
                'recommendations_en',
                'notes_json_en',
                'recommendations_json_en',
            ] as $col) {
                if (Schema::hasColumn('inspection_areas', $col)) {
                    $cols[] = $col;
                }
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
