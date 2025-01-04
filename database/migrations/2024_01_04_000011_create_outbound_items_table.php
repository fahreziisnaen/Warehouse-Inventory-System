<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_items', function (Blueprint $table) {
            $table->id('outbound_item_id');
            $table->foreignId('outbound_id')->constrained('outbound_records', 'outbound_id');
            $table->foreignId('item_id')->constrained('items', 'item_id');
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_items');
    }
}; 