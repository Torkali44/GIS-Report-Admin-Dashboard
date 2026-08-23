<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_houses', function (Blueprint $table) {
            $table->date('inspection_date')->nullable()->after('reference_code');
        });

        DB::table('property_houses')
            ->whereNull('inspection_date')
            ->update(['inspection_date' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('property_houses', function (Blueprint $table) {
            $table->dropColumn('inspection_date');
        });
    }
};
