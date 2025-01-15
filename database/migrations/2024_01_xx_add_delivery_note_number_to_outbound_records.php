<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbound_records', function (Blueprint $table) {
            $table->string('delivery_note_number')->nullable()->after('lkb_number');
        });
    }

    public function down(): void
    {
        Schema::table('outbound_records', function (Blueprint $table) {
            $table->dropColumn('delivery_note_number');
        });
    }
}; 