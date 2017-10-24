<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderApplications extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_applications', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('tender_id');
            $table->integer('user_id');
            $table->timestamp('created_at');
            $table->decimal('price', 10, 0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_applications');
    }
}
