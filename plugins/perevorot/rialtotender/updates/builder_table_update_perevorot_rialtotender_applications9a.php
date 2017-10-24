<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApplications9a extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_applications', function($table)
        {
            if(!Schema::hasColumn('perevorot_rialtotender_applications', 'is_test')) {
                $table->boolean('is_test')->default(0);
            }
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_applications', function($table)
        {
            if(Schema::hasColumn('perevorot_rialtotender_applications', 'is_test')) {
                $table->dropColumn('is_test');
            }
        });
    }
}