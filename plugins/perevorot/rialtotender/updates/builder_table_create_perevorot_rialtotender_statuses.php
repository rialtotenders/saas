<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderStatuses extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_statuses', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('status_key');
            $table->string('status_name');
            $table->smallInteger('type');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_statuses');
    }
}
