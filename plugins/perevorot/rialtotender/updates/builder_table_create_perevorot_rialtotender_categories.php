<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderCategories extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_categories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('code');
            $table->string('name');
            $table->text('cpv');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_categories');
    }
}
