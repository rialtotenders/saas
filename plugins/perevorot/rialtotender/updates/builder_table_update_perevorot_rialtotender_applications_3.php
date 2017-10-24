<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApplications3 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_applications', function($table)
        {
            $table->integer('lot_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_applications', function($table)
        {
            $table->dropColumn('lot_id');
        });
    }
}
