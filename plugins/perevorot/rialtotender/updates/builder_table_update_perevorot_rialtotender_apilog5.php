<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApilog5 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_apilog', function($table)
        {
            $table->integer('user_id')->unsigned();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_apilog', function($table)
        {
            $table->dropColumn('user_id');
        });
    }
}