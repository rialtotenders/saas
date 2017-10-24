<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderCurrencies2 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_currencies', function($table)
        {
            $table->integer('sort_order')->default(1);
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_currencies', function($table)
        {
            $table->dropColumn('sort_order');
        });
    }
}