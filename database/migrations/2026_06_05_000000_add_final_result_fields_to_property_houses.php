<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_houses', function (Blueprint $table) {
            $table->text('final_result_text')->nullable()->after('total_percentage');
            $table->text('final_general_notes')->nullable()->after('final_result_text');
            $table->date('report_delivered_at')->nullable()->after('final_general_notes');
        });
    }

    public function down(): void
    {
        Schema::table('property_houses', function (Blueprint $table) {
            $table->dropColumn(['final_result_text', 'final_general_notes', 'report_delivered_at']);
        });
    }
};
