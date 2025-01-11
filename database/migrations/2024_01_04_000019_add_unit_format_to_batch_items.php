<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_formats', function (Blueprint $table) {
            $table->id('format_id');
            $table->string('name')->unique();  // unit, lonjor, pack, roll, etc
            $table->timestamps();
        });

        Schema::table('batch_items', function (Blueprint $table) {
            $table->foreignId('format_id')->nullable()->constrained('unit_formats', 'format_id');
        });
    }

    public function down(): void
    {
        Schema::table('batch_items', function (Blueprint $table) {
            $table->dropForeign(['format_id']);
            $table->dropColumn('format_id');
        });

        Schema::dropIfExists('unit_formats');
    }
}; 