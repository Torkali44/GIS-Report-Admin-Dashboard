<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_houses', function (Blueprint $table) {
            $cols = [
                'activity_en' => 'activity',
                'property_type_en' => 'property_type',
                'building_status_en' => 'building_status',
                'compound_en' => 'compound',
                'area_en' => 'area',
                'buyer_name_en' => 'buyer_name',
                'developer_name_en' => 'developer_name',
                'engineering_supervisor_en' => 'engineering_supervisor',
                'main_contractor_en' => 'main_contractor',
            ];

            foreach ($cols as $enCol => $after) {
                if (! Schema::hasColumn('property_houses', $enCol)) {
                    $table->string($enCol)->nullable()->after($after);
                }
            }
        });

        if (! Schema::hasColumn('ready_notes', 'text_en')) {
            Schema::table('ready_notes', function (Blueprint $table) {
                $table->text('text_en')->nullable()->after('text');
            });
        }

        if (! Schema::hasColumn('recommendation_templates', 'text_en')) {
            Schema::table('recommendation_templates', function (Blueprint $table) {
                $table->text('text_en')->nullable()->after('text');
            });
        }
    }

    public function down(): void
    {
        Schema::table('property_houses', function (Blueprint $table) {
            $cols = [];
            foreach ([
                'activity_en',
                'property_type_en',
                'building_status_en',
                'compound_en',
                'area_en',
                'buyer_name_en',
                'developer_name_en',
                'engineering_supervisor_en',
                'main_contractor_en',
            ] as $col) {
                if (Schema::hasColumn('property_houses', $col)) {
                    $cols[] = $col;
                }
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });

        if (Schema::hasColumn('ready_notes', 'text_en')) {
            Schema::table('ready_notes', function (Blueprint $table) {
                $table->dropColumn('text_en');
            });
        }

        if (Schema::hasColumn('recommendation_templates', 'text_en')) {
            Schema::table('recommendation_templates', function (Blueprint $table) {
                $table->dropColumn('text_en');
            });
        }
    }
};
