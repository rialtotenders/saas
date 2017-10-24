<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApplicationFiles3 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_application_files', function($table)
        {
            $table->text('json');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_application_files', function($table)
        {
            $table->dropColumn('json');
        });
    }
}
