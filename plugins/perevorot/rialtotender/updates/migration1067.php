<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use DB;

class Migration1067 extends Migration
{
    public function up()
    {
        try {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        Schema::table('perevorot_rialtotender_payments', function($table)
        {
            $table->dropForeign(['currency_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::table('perevorot_rialtotender_payments', function($table)
        {
            $table->foreign('currency_id')->references('id')->on('perevorot_rialtotender_currencies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }catch (\Exception $e) {
    
        }
    }

    public function down()
    {
        try {
    

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        Schema::table('perevorot_rialtotender_payments', function($table)
        {
            $table->foreign('currency_id')->references('id')->on('perevorot_rialtotender_currencies');
            $table->foreign('user_id')->references('id')->on('users');
        });
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        }catch (\Exception $e) {
    
        }
    }
}