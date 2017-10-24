<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderTenderFiles extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_tender_files', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('filename');
            $table->string('hash');
            $table->string('url');
            $table->string('tender_id');
            $table->string('upload_url');
            $table->text('json');
            $table->integer('system_file_id');
            $table->integer('change_system_file_id');
            $table->string('document_id');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_tender_files');
    }
}
