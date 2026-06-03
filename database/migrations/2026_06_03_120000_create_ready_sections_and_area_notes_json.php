<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ready_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('note_category_id')->nullable()->constrained('note_categories')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('inspection_areas', function (Blueprint $table) {
            $table->json('notes_json')->nullable()->after('additional_info');
            $table->json('recommendations_json')->nullable()->after('recommendations');
        });

        $defaults = [
            'الفناء الخارجي',
            'الصالة',
            'المجلس',
            'غرفة رقم 1',
            'غرفة رقم 2',
            'غرفة رقم 3',
            'غرفة الماستر',
            'دورة المياه',
            'المطبخ',
            'السطح',
            'مواقف السيارات',
            'الحديقة',
        ];

        foreach ($defaults as $i => $name) {
            DB::table('ready_sections')->insert([
                'name' => $name,
                'note_category_id' => null,
                'sort_order' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('inspection_areas', function (Blueprint $table) {
            $table->dropColumn(['notes_json', 'recommendations_json']);
        });

        Schema::dropIfExists('ready_sections');
    }
};
