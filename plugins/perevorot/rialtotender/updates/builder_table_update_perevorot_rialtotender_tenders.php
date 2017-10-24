<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderTenders extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            $table->string('tender_system_id', 255)->nullable()->change();
            $table->string('tender_id', 255)->nullable()->change();
            $table->string('value', 255)->nullable()->change();
            $table->string('currency', 255)->nullable()->change();
            $table->text('json')->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            $table->string('tender_system_id', 255)->nullable(false)->change();
            $table->string('tender_id', 255)->nullable(false)->change();
            $table->string('value', 255)->nullable(false)->change();
            $table->string('currency', 255)->nullable(false)->change();
            $table->text('json')->nullable(false)->change();
        });
    }
}
