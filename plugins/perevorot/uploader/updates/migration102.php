<?php namespace Perevorot\Uploader\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class Migration102 extends Migration
{
    public function up()
    {
        Schema::table('system_files', function($table)
        {
            $table->integer('user_id')->unsigned()->nullable();
        });
    }

    public function down()
    {
        Schema::table('system_files', function($table)
        {
            $table->dropColumn('user_id');
        });
    }
}