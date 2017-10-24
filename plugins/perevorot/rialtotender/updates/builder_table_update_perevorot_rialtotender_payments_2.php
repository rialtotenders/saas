<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderPayments2 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_payments', function($table)
        {
            if(!Schema::hasColumn('perevorot_rialtotender_payments', 'application_id')) {
                $table->integer('application_id')->nullable()->unsigned();
                $table->index('application_id');
            }
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_payments', function($table)
        {
            if(Schema::hasColumn('perevorot_rialtotender_payments', 'application_id')) {
                $table->dropColumn('application_id');
            }
        });
    }
}