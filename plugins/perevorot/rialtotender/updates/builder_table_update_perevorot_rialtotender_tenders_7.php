<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderTenders7 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            $table->boolean('is_gov')->nullable()->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            $table->dropColumn('is_gov');
        });
    }
}
