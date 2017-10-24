<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderComplaintPeriods extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_complaint_periods', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('days');
            $table->string('procurement');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_complaint_periods');
    }
}
