<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderTenders5 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            $table->integer('empty_questions')->nullable()->unsigned()->default(0);
            $table->dateTime('date')->nullable();
            $table->string('status')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_tenders', function($table)
        {
            $table->dropColumn('empty_questions');
            $table->dropColumn('date');
            $table->dropColumn('status');
        });
    }
}
