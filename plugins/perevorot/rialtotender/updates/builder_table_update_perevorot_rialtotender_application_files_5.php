<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApplicationFiles5 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_application_files', function($table)
        {
            $table->integer('change_system_file_id');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_application_files', function($table)
        {
            $table->dropColumn('change_system_file_id');
        });
    }
}
