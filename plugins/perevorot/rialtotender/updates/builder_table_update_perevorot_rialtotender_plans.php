<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderPlans extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_plans', function($table)
        {
            $table->string('plan_system_id', 255)->nullable();
            $table->string('plan_id', 255)->nullable();
            $table->dropColumn('tender_system_id');
            $table->dropColumn('tender_id');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_plans', function($table)
        {
            $table->dropColumn('plan_system_id');
            $table->dropColumn('plan_id');
            $table->string('tender_system_id', 255)->nullable();
            $table->string('tender_id', 255)->nullable();
        });
    }
}
