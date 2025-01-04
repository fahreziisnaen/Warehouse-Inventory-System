<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_records', function (Blueprint $table) {
            $table->id('outbound_id');
            $table->string('lkb_number')->unique();
            $table->string('delivery_note_number')->unique();
            $table->date('outbound_date');
            $table->foreignId('vendor_id')->constrained('vendors', 'vendor_id');
            $table->string('project_id');
            $table->foreign('project_id')->references('project_id')->on('projects');
            $table->string('purpose');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_records');
    }
}; 