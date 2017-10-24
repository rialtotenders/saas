<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApplicationFiles extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_application_files', function($table)
        {
            $table->string('upload_url');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_application_files', function($table)
        {
            $table->dropColumn('upload_url');
        });
    }
}
