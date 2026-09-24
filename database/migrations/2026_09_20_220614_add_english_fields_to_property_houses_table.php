<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_houses', function (Blueprint $table) {
            if (! Schema::hasColumn('property_houses', 'final_result_text_en')) {
                $table->text('final_result_text_en')->nullable()->after('final_result_text');
            }
            if (! Schema::hasColumn('property_houses', 'final_general_notes_en')) {
                $table->text('final_general_notes_en')->nullable()->after('final_general_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_houses', function (Blueprint $table) {
            $cols = array_values(array_filter([
                Schema::hasColumn('property_houses', 'final_result_text_en') ? 'final_result_text_en' : null,
                Schema::hasColumn('property_houses', 'final_general_notes_en') ? 'final_general_notes_en' : null,
            ]));
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
