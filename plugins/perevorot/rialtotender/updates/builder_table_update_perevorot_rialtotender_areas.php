<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderAreas extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_areas', function($table)
        {
            $table->string('tender_url');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_areas', function($table)
        {
            $table->dropColumn('tender_url');
        });
    }
}
