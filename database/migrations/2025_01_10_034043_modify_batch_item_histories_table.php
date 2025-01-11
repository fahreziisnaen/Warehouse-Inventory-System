<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('batch_item_histories', function (Blueprint $table) {
            // Hapus kolom lama jika ada
            $table->dropMorphs('recordable');
            
            // Tambah kolom baru
            $table->nullableMorphs('recordable');
        });
    }

    public function down()
    {
        Schema::table('batch_item_histories', function (Blueprint $table) {
            $table->dropMorphs('recordable');
            $table->morphs('recordable');
        });
    }
}; 