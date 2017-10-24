<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderQuestions3 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_questions', function($table)
        {
            $table->string('lot_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_questions', function($table)
        {
            $table->dropColumn('lot_id');
        });
    }
}
