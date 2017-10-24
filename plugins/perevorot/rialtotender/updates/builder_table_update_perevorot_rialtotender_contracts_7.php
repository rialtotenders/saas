<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderContracts7 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_contracts', function($table)
        {
            $table->string('change_id')->nullable();
            $table->string('change_status')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_contracts', function($table)
        {
            $table->dropColumn('change_id');
            $table->dropColumn('change_status');
        });
    }
}
