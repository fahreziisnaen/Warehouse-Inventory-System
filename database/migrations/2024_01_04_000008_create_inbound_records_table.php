<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_records', function (Blueprint $table) {
            $table->id('inbound_id');
            $table->string('lpb_number')->unique();
            $table->date('receive_date');
            $table->foreignId('po_id')->constrained('purchase_orders', 'po_id');
            $table->string('status');
            $table->foreignId('project_id')->constrained('projects', 'project_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_records');
    }
}; 