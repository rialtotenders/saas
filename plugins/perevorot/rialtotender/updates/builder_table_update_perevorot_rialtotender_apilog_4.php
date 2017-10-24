<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApilog4 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_apilog', function($table)
        {
            $table->string('tender_id');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_apilog', function($table)
        {
            $table->dropColumn('tender_id');
        });
    }
}
