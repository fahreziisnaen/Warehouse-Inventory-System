<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inbound_items', function (Blueprint $table) {
            $table->id('inbound_item_id');
            $table->foreignId('inbound_id')->constrained('inbound_records', 'inbound_id');
            $table->foreignId('item_id')->nullable()->constrained('items', 'item_id');
            $table->enum('condition', ['Baru', 'Bekas']);
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_items');
    }
}; 