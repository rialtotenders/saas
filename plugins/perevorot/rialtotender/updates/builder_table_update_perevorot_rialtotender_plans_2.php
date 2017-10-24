<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderPlans22 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_plans', function($table)
        {
            $table->boolean('is_test')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('perevorot_rialtotender_plans', function($table)
        {
            $table->boolean('is_test')->nullable(false)->change();
        });
    }
}
