<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_types', function (Blueprint $table) {
            $table->id('vendor_type_id');
            $table->string('type_name');
            $table->timestamps();
        });

        // Insert default vendor types
        DB::table('vendor_types')->insert([
            ['type_name' => 'Supplier'],
            ['type_name' => 'Customer']
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_types');
    }
}; 