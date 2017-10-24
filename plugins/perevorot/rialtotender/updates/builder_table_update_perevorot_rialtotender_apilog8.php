<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApilog8 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_apilog', function($table)
        {
            $table->string('tender_id', 255)->nullable()->change();
            $table->string('format_tender_id', 255)->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_apilog', function($table)
        {
            $table->string('tender_id', 255)->nullable(false)->change();
            $table->string('format_tender_id', 255)->nullable(false)->change();
        });
    }
}