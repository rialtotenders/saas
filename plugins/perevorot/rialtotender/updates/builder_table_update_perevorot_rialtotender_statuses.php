<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderStatuses extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_statuses', function($table)
        {
            $table->text('data');
            $table->dropColumn('status_key');
            $table->dropColumn('status_name');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_statuses', function($table)
        {
            $table->dropColumn('data');
            $table->string('status_key', 255);
            $table->string('status_name', 255);
        });
    }
}
