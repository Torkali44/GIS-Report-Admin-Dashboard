<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Note categories (e.g. الجدران, السيراميك, الكهرباء, etc.)
        Schema::create('note_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');         // e.g. "الجدران"
            $table->string('name_en')->nullable(); // e.g. "Walls"
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Ready-made notes per category
        Schema::create('ready_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_category_id')->constrained()->cascadeOnDelete();
            $table->text('text');           // The note template text with (الموقع) placeholder
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Recommendation templates (manageable ready-made recommendations)
        Schema::create('recommendation_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_category_id')->constrained()->cascadeOnDelete();
            $table->text('text');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_templates');
        Schema::dropIfExists('ready_notes');
        Schema::dropIfExists('note_categories');
    }
};
