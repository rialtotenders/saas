<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderQuestions extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_questions', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->text('question');
            $table->timestamp('created_at');
            $table->string('tender_id');
            $table->integer('user_id')->unsigned();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_questions');
    }
}
