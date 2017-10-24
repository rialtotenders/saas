<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeletePerevorotRialtotenderStatuses extends Migration
{
    public function up()
    {
        Schema::dropIfExists('perevorot_rialtotender_statuses');
    }
    
    public function down()
    {
        Schema::create('perevorot_rialtotender_statuses', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->text('tender')->nullable();
            $table->text('bid')->nullable();
            $table->text('document')->nullable();
            $table->text('qualification')->nullable();
        });
    }
}
