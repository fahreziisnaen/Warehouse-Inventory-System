<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_numbers', function (Blueprint $table) {
            $table->id('part_number_id');
            $table->foreignId('brand_id')->constrained('brands', 'brand_id');
            $table->string('part_number')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_equipment')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_numbers');
    }
}; 