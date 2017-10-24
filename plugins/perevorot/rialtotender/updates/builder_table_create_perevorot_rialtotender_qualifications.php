<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotRialtotenderQualifications extends Migration
{
    public function up()
    {
        Schema::create('perevorot_rialtotender_qualifications', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('tender_id')->nullable();
            $table->integer('user_id')->nullable()->unsigned();
            $table->string('token_id')->nullable();
            $table->string('bid_id')->nullable();
            $table->string('status')->nullable();
            $table->string('qualification_id')->nullable();
            $table->boolean('is_test')->nullable()->default(0);
            $table->string('lot_id')->nullable();
            $table->text('json')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_rialtotender_qualifications');
    }
}
