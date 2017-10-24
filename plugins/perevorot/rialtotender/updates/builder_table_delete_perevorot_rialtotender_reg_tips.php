<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeletePerevorotRialtotenderRegTips extends Migration
{
    public function up()
    {
        Schema::dropIfExists('perevorot_rialtotender_reg_tips');
    }
    
    public function down()
    {
        Schema::create('perevorot_rialtotender_reg_tips', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('header1', 255)->nullable();
            $table->string('header2', 255)->nullable();
            $table->string('header3', 255)->nullable();
            $table->text('step1')->nullable();
            $table->text('step2')->nullable();
            $table->text('step3')->nullable();
        });
    }
}
