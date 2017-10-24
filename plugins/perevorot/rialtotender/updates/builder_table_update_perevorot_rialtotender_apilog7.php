<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApilog7 extends Migration
{
    public function up()
    {
       
        Schema::table('perevorot_rialtotender_apilog', function($table)
        {
            if(!Schema::hasColumn('perevorot_rialtotender_apilog', 'action')) {
                $table->string('action');
            }
        });
    
    }
    
    public function down()
    {
   
        Schema::table('perevorot_rialtotender_apilog', function($table)
        {
            if(Schema::hasColumn('perevorot_rialtotender_apilog', 'action')) {
                $table->dropColumn('action');
            }
        });
        
    }
}