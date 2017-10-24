<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApilog2 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_apilog', function($table)
        {
            $table->text('response')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_apilog', function($table)
        {
            $table->dropColumn('response');
        });
    }
}
