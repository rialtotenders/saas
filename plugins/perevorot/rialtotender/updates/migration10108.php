<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class Migration10108 extends Migration
{
    public function up()
    {
        Schema::table('users', function($table)
        {
            if(!Schema::hasColumn('users', 'lang')) {
                $table->string('lang')->nullable();
            }
        });
    }
    
    public function down()
    {
        Schema::table('users', function($table)
        {
            if(Schema::hasColumn('users', 'lang')) {
                $table->dropColumn('lang');
            }
        });
    }
}