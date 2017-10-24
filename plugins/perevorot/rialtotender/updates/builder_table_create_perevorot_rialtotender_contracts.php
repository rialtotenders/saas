<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderContracts extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_contracts', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('tender_id');
            $table->integer('user_id');
            $table->timestamp('created_at')->nullable();
            $table->string('token_id')->nullable();
            $table->string('transfer_id')->nullable();
            $table->string('contract_id');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_contracts');
    }
}
