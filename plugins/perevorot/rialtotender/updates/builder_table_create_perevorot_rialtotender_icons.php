<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderIcons extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_icons', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->boolean('is_enabled')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_icons');
    }
}
