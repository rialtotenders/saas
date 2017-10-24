<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderApilog extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_apilog', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('ip');
            $table->string('x_request_id')->nullable();
            $table->text('url');
            $table->string('method');
            $table->text('data');
            $table->timestamp('created_at');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_apilog');
    }
}
