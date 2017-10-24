<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderContracts4 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_contracts', function($table)
        {
            $table->text('json')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_contracts', function($table)
        {
            $table->dropColumn('json');
        });
    }
}
