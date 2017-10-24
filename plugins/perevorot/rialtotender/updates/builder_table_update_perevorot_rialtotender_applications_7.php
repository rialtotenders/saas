<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApplications7 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_applications', function($table)
        {
            $table->string('token_id', 255)->nullable();
            $table->string('transfer_id', 255)->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_applications', function($table)
        {
            $table->dropColumn('token_id');
            $table->dropColumn('transfer_id');
        });
    }
}
