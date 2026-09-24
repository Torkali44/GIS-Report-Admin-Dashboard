<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ready_sections', 'name_en')) {
            Schema::table('ready_sections', function (Blueprint $table) {
                $table->string('name_en')->nullable()->after('name');
            });
        }

        $translations = [
            'الفناء الخارجي' => 'Outdoor Courtyard',
            'الصالة' => 'Living Room',
            'المجلس' => 'Majlis',
            'غرفة رقم 1' => 'Bedroom 1',
            'غرفة رقم 2' => 'Bedroom 2',
            'غرفة رقم 3' => 'Bedroom 3',
            'غرفة الماستر' => 'Master Bedroom',
            'دورة المياه' => 'Bathroom',
            'المطبخ' => 'Kitchen',
            'السطح' => 'Roof',
            'مواقف السيارات' => 'Parking',
            'الحديقة' => 'Garden',
            'الكهرباء' => 'Electrical Works',
            'السباكة' => 'Plumbing Works',
            'الجدران' => 'Walls',
            'الأسقف' => 'Ceilings',
            'التكييف والتهوية' => 'HVAC & Ventilation',
            'عازل المياه' => 'Waterproofing',
            'الأعمال الخشبية' => 'Wood Works',
            'الالمنيوم النوافذ والابواب' => 'Aluminum Windows & Doors',
            'واجهات المبنى' => 'Building Facades',
            'الموقع العام' => 'General Site',
        ];

        foreach ($translations as $ar => $en) {
            DB::table('ready_sections')->where('name', $ar)->whereNull('name_en')->update(['name_en' => $en]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ready_sections', 'name_en')) {
            Schema::table('ready_sections', function (Blueprint $table) {
                $table->dropColumn('name_en');
            });
        }
    }
};
