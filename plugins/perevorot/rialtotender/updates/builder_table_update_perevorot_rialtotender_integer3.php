<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderInteger3 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_integer', function($table)
        {
            if(!Schema::hasColumn('perevorot_rialtotender_integer', 'theme_folder')) {
                $table->string('theme_folder')->nullable();
            }
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_integer', function($table)
        {
            if(Schema::hasColumn('perevorot_rialtotender_integer', 'theme_folder')) {
                $table->dropColumn('theme_folder');
            }
        });
    }
}