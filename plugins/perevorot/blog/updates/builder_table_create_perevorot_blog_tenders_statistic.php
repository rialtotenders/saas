<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotBlogTendersStatistic extends Migration
{
    public function up()
    {
        Schema::create('perevorot_blog_tenders_statistic', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('tender_sum')->nullable();
            $table->string('tender_sum_text')->nullable();
            $table->string('violation_sum')->nullable();
            $table->string('violation_sum_text')->nullable();
            $table->string('comments')->nullable();
            $table->string('reviews')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_blog_tenders_statistic');
    }
}
