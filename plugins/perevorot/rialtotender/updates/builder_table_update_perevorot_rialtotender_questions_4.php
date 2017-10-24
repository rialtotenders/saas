<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderQuestions4 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_questions', function($table)
        {
            $table->text('title')->nullable()->unsigned(false)->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_questions', function($table)
        {
            $table->string('title', 255)->nullable()->unsigned(false)->default(null)->change();
        });
    }
}