<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderTenders9_1 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            $table->boolean('is_close_qualification')->nullable()->default(0);
        });
    }

    public function down()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            $table->dropColumn('is_close_qualification');
        });
    }
}