<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderPlans3 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_plans', function($table)
        {
            $table->boolean('is_gov')->nullable()->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_plans', function($table)
        {
            $table->dropColumn('is_gov');
        });
    }
}
