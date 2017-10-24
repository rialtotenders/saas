<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderContracts5 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_contracts', function($table)
        {
            $table->text('cid')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_contracts', function($table)
        {
            $table->dropColumn('cid');
        });
    }
}
