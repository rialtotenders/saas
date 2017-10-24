<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderContracts3 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_contracts', function($table)
        {
            $table->boolean('is_test')->nullable()->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_contracts', function($table)
        {
            $table->dropColumn('is_test');
        });
    }
}
