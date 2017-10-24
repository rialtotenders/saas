<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class Migration10110 extends Migration
{
    public function up()
    {
        Schema::table('users', function($table)
        {
            if(!Schema::hasColumn('users', 'company_name')) {
                $table->string('company_name')->nullable();
            }
        });
    }
    
    public function down()
    {
        Schema::table('users', function($table)
        {
            if(Schema::hasColumn('users', 'company_name')) {
                $table->dropColumn('company_name');
            }
        });
    }
}