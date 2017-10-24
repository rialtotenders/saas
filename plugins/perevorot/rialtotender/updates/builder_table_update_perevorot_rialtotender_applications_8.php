<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApplications8 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_applications', function($table)
        {
            if(!Schema::hasColumn('perevorot_rialtotender_applications', 'features')) {
                $table->text('features')->nullable();
            }
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_applications', function($table)
        {
            if(Schema::hasColumn('perevorot_rialtotender_applications', 'features')) {
                $table->dropColumn('features');
            }
        });
    }
}
