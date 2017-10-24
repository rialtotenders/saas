<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderApplicationFiles extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_application_files', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('id');
            $table->string('filename');
            $table->string('hash');
            $table->string('url');
            $table->string('bid_id');
            $table->primary(['id']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_application_files');
    }
}
