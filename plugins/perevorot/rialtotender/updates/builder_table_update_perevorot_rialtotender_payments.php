<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderPayments extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_payments', function($table)
        {
            $table->string('number')->nullable()->unsigned(false)->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_payments', function($table)
        {
            $table->integer('number')->nullable()->unsigned(false)->default(null)->change();
        });
    }
}
