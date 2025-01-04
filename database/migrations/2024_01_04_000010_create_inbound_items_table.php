<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_items', function (Blueprint $table) {
            $table->id('inbound_item_id');
            $table->foreignId('inbound_id')
                ->constrained('inbound_records', 'inbound_id')
                ->onDelete('cascade');
            $table->foreignId('item_id')
                ->constrained('items', 'item_id')
                ->onDelete('cascade');
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_items');
    }
}; 