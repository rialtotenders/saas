<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderPlans extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_plans', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('tender_system_id')->nullable();
            $table->string('tender_id')->nullable();
            $table->string('title');
            $table->string('value')->nullable();
            $table->string('currency')->nullable();
            $table->integer('user_id')->unsigned();
            $table->timestamp('created_at');
            $table->text('json')->nullable();
            $table->boolean('is_complete');
            $table->string('token_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_plans');
    }
}
