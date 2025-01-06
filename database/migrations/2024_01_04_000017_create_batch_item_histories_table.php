<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_item_histories', function (Blueprint $table) {
            $table->id('history_id');
            $table->foreignId('batch_item_id')->constrained('batch_items', 'batch_item_id');
            $table->enum('type', ['inbound', 'outbound']);
            $table->integer('quantity');
            $table->morphs('recordable'); // Untuk InboundRecord atau OutboundRecord
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_item_histories');
    }
}; 