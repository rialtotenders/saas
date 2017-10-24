<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderInteger extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_integer', function($table)
        {
            $table->boolean('is_enabled');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_integer', function($table)
        {
            $table->dropColumn('is_enabled');
        });
    }
}
