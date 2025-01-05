<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbound_records', function (Blueprint $table) {
            $table->foreignId('purpose_id')->after('project_id')->constrained('purposes', 'purpose_id');
        });
    }

    public function down(): void
    {
        Schema::table('outbound_records', function (Blueprint $table) {
            $table->dropForeign(['purpose_id']);
            $table->dropColumn('purpose_id');
        });
    }
}; 