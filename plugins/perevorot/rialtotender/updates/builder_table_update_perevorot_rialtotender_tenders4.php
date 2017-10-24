<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderTenders4 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            if(!Schema::hasColumn('perevorot_rialtotender_tenders', 'is_test')) {
                $table->boolean('is_test')->default(0);
            }
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            if(Schema::hasColumn('perevorot_rialtotender_tenders', 'is_test')) {
                $table->dropColumn('is_test');
            }
        });
    }
}