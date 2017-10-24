<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApplications5 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_applications', function($table)
        {
            $table->string('lot_id', 255)->change();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_applications', function($table)
        {
            $table->string('lot_id', 10)->change();
        });
    }
}
