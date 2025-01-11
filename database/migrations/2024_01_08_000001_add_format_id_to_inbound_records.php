<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inbound_records', function (Blueprint $table) {
            $table->foreignId('format_id')->nullable()->after('batch_quantity')->constrained('unit_formats', 'format_id');
        });
    }

    public function down(): void
    {
        Schema::table('inbound_records', function (Blueprint $table) {
            $table->dropForeign(['format_id']);
            $table->dropColumn('format_id');
        });
    }
}; 