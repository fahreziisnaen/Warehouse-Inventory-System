<?php

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

        Schema::create('batch_items', function (Blueprint $table) {
            $table->id('batch_item_id');
            $table->foreignId('part_number_id')->constrained('part_numbers', 'part_number_id');
            $table->integer('quantity')->default(0);
            $table->foreignId('format_id')->nullable()->constrained('unit_formats', 'format_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_items');
        Schema::dropIfExists('unit_formats');
    }
}; 