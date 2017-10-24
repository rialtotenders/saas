<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderCustomers extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_customers', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('title');
            $table->string('url');
            $table->boolean('is_index')->default(0);
            $table->boolean('is_enabled')->default(0);
            $table->integer('sort_order')->nullable()->unsigned();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_customers');
    }
}
