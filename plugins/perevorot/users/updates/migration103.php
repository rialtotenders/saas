<?php namespace Perevorot\Users\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class Migration103 extends Migration
{
    public function up()
    {
        Schema::table('users', function($table)
        {
            if(!Schema::hasColumn('users', 'bic')) {
                $table->string('bic')->nullable();
            }
            if(!Schema::hasColumn('users', 'bc')) {
                $table->string('bc')->nullable();
            }
            if(!Schema::hasColumn('users', 'iban')) {
                $table->string('iban')->nullable();
            }
        });
    }
    
    public function down()
    {
        Schema::table('users', function($table)
        {
            if(Schema::hasColumn('users', 'bic')) {
                $table->dropColumn('bic');
            }
            if(Schema::hasColumn('users', 'bc')) {
                $table->dropColumn('bc');
            }
            if(Schema::hasColumn('users', 'iban')) {
                $table->dropColumn('iban');
            }
        });
    }
}