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
            $table->foreignId('po_id')->nullable()->constrained('purchase_orders', 'po_id');
            $table->string('project_id');
            $table->foreign('project_id')->references('project_id')->on('projects');
            $table->foreignId('part_number_id')->nullable()->constrained('part_numbers', 'part_number_id');
            $table->integer('batch_quantity')->nullable();
            $table->foreignId('format_id')->nullable()->constrained('unit_formats', 'format_id');
            $table->enum('location', ['Gudang Jakarta', 'Gudang Surabaya']);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_records');
    }
}; 