<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class Migration1047 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_tariffs', function($table)
        {
            $table->foreign('currency_id')->references('id')->on('perevorot_rialtotender_currencies')->onDelete('cascade');
        });
    }

    public function down()
    {        
        Schema::table('perevorot_rialtotender_tariffs', function($table)
        {
            $table->dropForeign(['currency_id']);
        });
    }
}