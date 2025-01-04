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
            $table->foreignId('customer_id')->constrained('customers', 'customer_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id');
            $table->string('purpose');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_records');
    }
}; 