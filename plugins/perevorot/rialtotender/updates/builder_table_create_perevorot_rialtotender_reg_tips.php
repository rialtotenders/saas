<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderRegTips extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_reg_tips', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('header1')->nullable();
            $table->string('header2')->nullable();
            $table->string('header3')->nullable();
            $table->text('step1')->nullable();
            $table->text('step2')->nullable();
            $table->text('step3')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_reg_tips');
    }
}
