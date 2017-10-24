<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderQuestions2 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_questions', function($table)
        {
            $table->boolean('is_answered')->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_questions', function($table)
        {
            $table->dropColumn('is_answered');
        });
    }
}
