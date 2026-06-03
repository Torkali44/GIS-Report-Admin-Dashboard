<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('inspection_photos');
    }

    public function down(): void
    {
        // لا حاجة لجدول الصور — التطبيق تقارير نصية فقط
    }
};
