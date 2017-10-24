<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeletePerevorotRialtotenderFormMessages extends Migration
{
    public function up()
    {
        Schema::dropIfExists('perevorot_rialtotender_form_messages');
    }
    
    public function down()
    {
        Schema::create('perevorot_rialtotender_form_messages', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('header1_ru', 255)->nullable();
            $table->string('header2_ru', 255)->nullable();
            $table->string('header3_ru', 255)->nullable();
            $table->text('step1_ru')->nullable();
            $table->text('step2_ru')->nullable();
            $table->text('step3_ru')->nullable();
            $table->string('header1_ua', 255)->nullable();
            $table->string('header2_ua', 255)->nullable();
            $table->string('header3_ua', 255)->nullable();
            $table->text('step1_ua')->nullable();
            $table->text('step2_ua')->nullable();
            $table->text('step3_ua')->nullable();
            $table->string('header1_en', 255)->nullable();
            $table->string('header2_en', 255)->nullable();
            $table->string('header3_en', 255)->nullable();
            $table->text('step1_en')->nullable();
            $table->text('step2_en')->nullable();
            $table->text('step3_en')->nullable();
        });
    }
}
