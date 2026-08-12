<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_houses', function (Blueprint $table) {
            $table->string('inspector_rating_override')->nullable()->after('total_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('property_houses', function (Blueprint $table) {
            $table->dropColumn('inspector_rating_override');
        });
    }
};
