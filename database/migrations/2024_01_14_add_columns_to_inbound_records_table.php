<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inbound_records', function (Blueprint $table) {
            // Periksa dulu apakah kolom sudah ada
            if (!Schema::hasColumn('inbound_records', 'part_number_id')) {
                $table->foreignId('part_number_id')->nullable()->after('location')->constrained('part_numbers', 'part_number_id');
            }
            
            if (!Schema::hasColumn('inbound_records', 'batch_quantity')) {
                $table->integer('batch_quantity')->nullable()->after('part_number_id');
            }
            
            if (!Schema::hasColumn('inbound_records', 'format_id')) {
                $table->foreignId('format_id')->nullable()->after('batch_quantity')->constrained('unit_formats', 'format_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inbound_records', function (Blueprint $table) {
            if (Schema::hasColumn('inbound_records', 'part_number_id')) {
                $table->dropForeign(['part_number_id']);
                $table->dropColumn('part_number_id');
            }
            
            if (Schema::hasColumn('inbound_records', 'batch_quantity')) {
                $table->dropColumn('batch_quantity');
            }
            
            if (Schema::hasColumn('inbound_records', 'format_id')) {
                $table->dropForeign(['format_id']);
                $table->dropColumn('format_id');
            }
        });
    }
}; 