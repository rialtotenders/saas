<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApilog extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_apilog', function($table)
        {
            $table->text('data')->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_apilog', function($table)
        {
            $table->text('data')->nullable(false)->change();
        });
    }
}
