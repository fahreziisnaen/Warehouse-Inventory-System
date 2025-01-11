<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inbound_records', function (Blueprint $table) {
            $table->foreignId('po_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inbound_records', function (Blueprint $table) {
            $table->foreignId('po_id')->nullable(false)->change();
        });
    }
}; 