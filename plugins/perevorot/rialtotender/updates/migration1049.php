<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class Migration1049 extends Migration
{
    public function up()
    {
        try {
        Schema::table('perevorot_rialtotender_payments', function($table)
        {
            $table->foreign('currency_id')->references('id')->on('perevorot_rialtotender_currencies');
            $table->foreign('user_id')->references('id')->on('users');
        });
        }catch (\Exception $e) {
    
        }
    }

    public function down()
    {      
        try {
        Schema::table('perevorot_rialtotender_payments', function($table)
        {
            $table->dropForeign(['currency_id']);
            $table->dropForeign(['user_id']);
        });
        }catch (\Exception $e) {
    
        }
    }
}