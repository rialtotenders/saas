<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderInteger extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_integer', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('title');
            $table->string('domain');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_integer');
    }
}
