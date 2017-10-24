<?php namespace Perevorot\Users\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class Migration104 extends Migration
{
    public function up()
    {
        Schema::table('users', function($table)
        {
            if(!Schema::hasColumn('users', 'is_do')) {
                $table->boolean('is_do')->nullable()->default(0);
            }
        });
    }
    
    public function down()
    {
        Schema::table('users', function($table)
        {
            if(Schema::hasColumn('users', 'is_do')) {
                $table->dropColumn('is_do');
            }
        });
    }
}