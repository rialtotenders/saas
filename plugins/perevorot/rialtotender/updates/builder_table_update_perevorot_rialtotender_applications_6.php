<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApplications6 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_applications', function($table)
        {
            $table->string('bid_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_applications', function($table)
        {
            $table->dropColumn('bid_id');
        });
    }
}
