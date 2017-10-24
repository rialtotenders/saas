<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class Migration10111 extends Migration
{
    public function up()
    {
        Schema::table('users', function($table)
        {
            if(!Schema::hasColumn('users', 'is_test_user')) {
                $table->string('is_test_user')->nullable();
            }
            if(!Schema::hasColumn('users', 'is_formed')) {
                $table->string('is_formed')->nullable();
            }
        });
    }
    
    public function down()
    {
        Schema::table('users', function($table)
        {
            if(Schema::hasColumn('users', 'is_test_user')) {
                $table->dropColumn('is_test_user');
            }
            if(Schema::hasColumn('users', 'is_formed')) {
                $table->dropColumn('is_formed');
            }
        });
    }
}