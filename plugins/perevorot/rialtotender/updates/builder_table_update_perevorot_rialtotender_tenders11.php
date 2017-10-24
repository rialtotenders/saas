<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderTenders11 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            if(!Schema::hasColumn('perevorot_rialtotender_tenders', 'dt_notify_empty_question')) {
                $table->dateTime('dt_notify_empty_question')->nullable();
            }
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            if(Schema::hasColumn('perevorot_rialtotender_tenders', 'dt_notify_empty_question')) {
                $table->dropColumn('dt_notify_empty_question');
            }
        });
    }
}