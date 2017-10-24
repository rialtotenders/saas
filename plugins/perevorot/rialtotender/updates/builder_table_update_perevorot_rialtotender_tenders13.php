<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderTenders13 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            if(!Schema::hasColumn('perevorot_rialtotender_tenders', 'is_empty_price')) {
                $table->boolean('is_empty_price')->nullable()->default(0);
            }
        });
    }

    public function down()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            if(Schema::hasColumn('perevorot_rialtotender_tenders', 'is_empty_price')) {
                $table->dropColumn('is_empty_price');
            }
        });
    }
}