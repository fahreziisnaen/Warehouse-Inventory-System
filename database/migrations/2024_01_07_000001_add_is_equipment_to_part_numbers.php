<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part_numbers', function (Blueprint $table) {
            $table->boolean('is_equipment')->default(true)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('part_numbers', function (Blueprint $table) {
            $table->dropColumn('is_equipment');
        });
    }
}; 