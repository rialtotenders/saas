<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderTenders10 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            if(!Schema::hasColumn('perevorot_rialtotender_tenders', 'step')) {
                $table->smallInteger('step')->nullable()->unsigned()->default(0);
            }
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            if(Schema::hasColumn('perevorot_rialtotender_tenders', 'step')) {
                $table->dropColumn('step');
            }
        });
    }
}