<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderPayments extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_payments', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->integer('currency_id')->unsigned();
            $table->integer('number')->nullable();
            $table->dateTime('date');
            $table->integer('sum');
            $table->smallInteger('type')->default(1);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_payments');
    }
}