<?php namespace Perevorot\Users\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class Migration105 extends Migration
{
    public function up()
    {
        Schema::table('users', function($table)
        {
            if(!Schema::hasColumn('users', 'activity')) {
                $table->text('activity')->nullable();
            }
        });
    }
    
    public function down()
    {
        Schema::table('users', function($table)
        {
            if(Schema::hasColumn('users', 'activity')) {
                $table->dropColumn('activity');
            }
        });
    }
}