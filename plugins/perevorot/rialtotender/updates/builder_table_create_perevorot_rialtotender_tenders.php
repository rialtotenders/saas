<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderTenders extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_tenders', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('tender_system_id');
            $table->string('tender_id');
            $table->string('title');
            $table->string('value');
            $table->string('currency');
            $table->integer('user_id');
            $table->timestamp('created_at');
            $table->text('json');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_tenders');
    }
}
