<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderTenders12 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            if(!Schema::hasColumn('perevorot_rialtotender_tenders', 'other_qs')) {
                $table->text('other_qs')->nullable();
            }
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            if(Schema::hasColumn('perevorot_rialtotender_tenders', 'other_qs')) {
                $table->dropColumn('other_qs');
            }
        });
    }
}