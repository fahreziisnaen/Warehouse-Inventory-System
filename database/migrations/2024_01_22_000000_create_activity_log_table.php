<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection(config('activitylog.database_connection'))->create(config('activitylog.table_name'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            
            $table->string('subject_id', 36)->nullable();
            $table->string('subject_type')->nullable();
            $table->index(['subject_id', 'subject_type']);

            $table->string('causer_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->index(['causer_id', 'causer_type']);

            $table->json('properties')->nullable();
            $table->string('event')->nullable();
            $table->string('batch_uuid', 36)->nullable();
            
            $table->timestamps();
            $table->index('log_name');
        });
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))->dropIfExists(config('activitylog.table_name'));
    }
}
