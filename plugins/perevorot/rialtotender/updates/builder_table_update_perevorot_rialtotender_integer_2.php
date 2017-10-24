<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderInteger2 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_integer', function($table)
        {
            $table->text('env')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_integer', function($table)
        {
            $table->dropColumn('env');
        });
    }
}
