<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inbound_records', function (Blueprint $table) {
            $table->foreignId('part_number_id')->nullable()->constrained('part_numbers', 'part_number_id');
            $table->integer('batch_quantity')->nullable();
        });

        Schema::table('outbound_records', function (Blueprint $table) {
            $table->foreignId('part_number_id')->nullable()->constrained('part_numbers', 'part_number_id');
            $table->integer('batch_quantity')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inbound_records', function (Blueprint $table) {
            $table->dropForeign(['part_number_id']);
            $table->dropColumn(['part_number_id', 'batch_quantity']);
        });

        Schema::table('outbound_records', function (Blueprint $table) {
            $table->dropForeign(['part_number_id']);
            $table->dropColumn(['part_number_id', 'batch_quantity']);
        });
    }
}; 