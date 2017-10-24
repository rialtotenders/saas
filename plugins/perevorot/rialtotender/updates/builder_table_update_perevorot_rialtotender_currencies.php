<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderCurrencies extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_currencies', function($table)
        {
            $table->string('code');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_currencies', function($table)
        {
            $table->dropColumn('code');
        });
    }
}