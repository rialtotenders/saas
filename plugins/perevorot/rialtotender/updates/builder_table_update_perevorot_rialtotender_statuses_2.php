<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderStatuses2 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_statuses', function($table)
        {
            $table->text('tender')->nullable();
            $table->text('bid')->nullable();
            $table->text('document')->nullable();
            $table->text('qualification')->nullable();
            $table->dropColumn('type');
            $table->dropColumn('data');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_statuses', function($table)
        {
            $table->dropColumn('tender');
            $table->dropColumn('bid');
            $table->dropColumn('document');
            $table->dropColumn('qualification');
            $table->smallInteger('type');
            $table->text('data');
        });
    }
}
