<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderTariffs extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_tariffs', function($table)
        {
            if(!Schema::hasColumn('perevorot_rialtotender_tariffs', 'is_gov')) {
                $table->boolean('is_gov')->nullable()->default(0);
            }
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_tariffs', function($table)
        {
            if(Schema::hasColumn('perevorot_rialtotender_tariffs', 'is_gov')) {
                $table->dropColumn('is_gov');
            }
        });
    }
}