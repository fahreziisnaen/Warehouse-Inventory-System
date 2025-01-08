<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus data yang item_id nya null
        DB::table('inbound_items')->whereNull('item_id')->delete();
        
        Schema::table('inbound_items', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('inbound_items', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable()->change();
        });
    }
}; 