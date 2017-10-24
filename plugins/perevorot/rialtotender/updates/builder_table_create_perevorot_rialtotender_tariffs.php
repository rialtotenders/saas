<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderTariffs extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_tariffs', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('price_from');
            $table->integer('price_to');
            $table->integer('sum');
            $table->integer('currency_id')->unsigned();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_tariffs');
    }
}
