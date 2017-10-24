<?php namespace RainLab\User\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UpdateUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function($table)
        {
            $table->dropUnique('users_email_unique');
        });
    }

    public function down()
    {
        Schema::table('users', function($table)
        {
            $table->unique('users_email_unique');
        });
    }
}
