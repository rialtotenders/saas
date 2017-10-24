<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderFormMessages extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_form_messages', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('header1_ru')->nullable();
            $table->string('header2_ru')->nullable();
            $table->string('header3_ru')->nullable();
            $table->text('step1_ru')->nullable();
            $table->text('step2_ru')->nullable();
            $table->text('step3_ru')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_form_messages');
    }
}
