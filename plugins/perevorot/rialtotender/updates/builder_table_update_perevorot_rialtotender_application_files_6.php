<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApplicationFiles6 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_application_files', function($table)
        {
            $table->string('document_id');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_application_files', function($table)
        {
            $table->dropColumn('document_id');
        });
    }
}
