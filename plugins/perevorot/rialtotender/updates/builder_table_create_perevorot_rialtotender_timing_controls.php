<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderTimingControls extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_timing_controls', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->double('price_from', 10, 0)->nullable();
            $table->double('price_to', 10, 0)->nullable();
            $table->integer('enquire_days')->nullable();
            $table->integer('tender_days')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_timing_controls');
    }
}
