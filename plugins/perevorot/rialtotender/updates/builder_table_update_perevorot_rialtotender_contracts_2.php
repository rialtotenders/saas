<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderContracts2 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_contracts', function($table)
        {
            $table->string('title')->nullable();
            $table->string('contract_number')->nullable();
            $table->dateTime('date_signed')->nullable();
            $table->double('amount', 10, 0)->nullable();
            $table->string('status', 10)->nullable()->unsigned(false)->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_contracts', function($table)
        {
            $table->dropColumn('title');
            $table->dropColumn('contract_number');
            $table->dropColumn('date_signed');
            $table->dropColumn('amount');
            $table->smallInteger('status')->nullable()->unsigned()->default(0)->change();
        });
    }
}
