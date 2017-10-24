<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderQuestions extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_questions', function($table)
        {
            $table->string('title')->nullable();
            $table->string('qid')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_questions', function($table)
        {
            $table->dropColumn('title');
            $table->dropColumn('qid');
        });
    }
}
