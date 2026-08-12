<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_areas', function (Blueprint $table) {
            $table->index(['property_house_id', 'sort_order', 'id']);
        });

        Schema::table('ready_notes', function (Blueprint $table) {
            $table->index(['note_category_id', 'sort_order']);
        });

        Schema::table('recommendation_templates', function (Blueprint $table) {
            $table->index(['note_category_id', 'sort_order']);
        });

        Schema::table('ready_sections', function (Blueprint $table) {
            $table->index(['sort_order', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('inspection_areas', function (Blueprint $table) {
            $table->dropIndex(['property_house_id', 'sort_order', 'id']);
        });

        Schema::table('ready_notes', function (Blueprint $table) {
            $table->dropIndex(['note_category_id', 'sort_order']);
        });

        Schema::table('recommendation_templates', function (Blueprint $table) {
            $table->dropIndex(['note_category_id', 'sort_order']);
        });

        Schema::table('ready_sections', function (Blueprint $table) {
            $table->dropIndex(['sort_order', 'id']);
        });
    }
};
