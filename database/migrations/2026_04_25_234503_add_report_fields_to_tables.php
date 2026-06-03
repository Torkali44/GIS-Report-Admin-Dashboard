<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('property_houses', function (Blueprint $table) {
            $table->string('activity')->nullable();
            $table->string('property_type')->nullable();
            $table->string('building_status')->nullable();
            $table->string('document_number')->nullable();
            $table->string('intro_number')->nullable();
            $table->string('villa_number')->nullable();
            $table->string('road')->nullable();
            $table->string('compound')->nullable();
            $table->string('area')->nullable();
            $table->string('buyer_name')->nullable();
            $table->string('id_number')->nullable();
            $table->string('developer_name')->nullable();
            $table->string('engineering_supervisor')->nullable();
            $table->string('main_contractor')->nullable();
            $table->string('property_age')->nullable();
            $table->string('land_area')->nullable();
            $table->string('building_area')->nullable();
            $table->string('floors_count')->nullable();
            $table->string('rooms_count')->nullable();
            $table->string('bathrooms_count')->nullable();
            $table->string('halls_count')->nullable();
            $table->string('parking_count')->nullable();
            $table->string('kitchens_count')->nullable();
        });

        Schema::table('inspection_areas', function (Blueprint $table) {
            $table->integer('score')->nullable();
            $table->text('additional_info')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_houses', function (Blueprint $table) {
            $table->dropColumn([
                'activity', 'property_type', 'building_status', 'document_number', 'intro_number',
                'villa_number', 'road', 'compound', 'area', 'buyer_name', 'id_number',
                'developer_name', 'engineering_supervisor', 'main_contractor',
                'property_age', 'land_area', 'building_area', 'floors_count',
                'rooms_count', 'bathrooms_count', 'halls_count', 'parking_count', 'kitchens_count'
            ]);
        });

        Schema::table('inspection_areas', function (Blueprint $table) {
            $table->dropColumn(['score', 'additional_info']);
        });
    }
};
