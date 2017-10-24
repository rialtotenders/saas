<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeletePerevorotBlogTendersStatistic extends Migration
{
    public function up()
    {
        Schema::dropIfExists('perevorot_blog_tenders_statistic');
    }
    
    public function down()
    {
        Schema::create('perevorot_blog_tenders_statistic', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('tender_sum', 255)->nullable();
            $table->string('tender_sum_text', 255)->nullable();
            $table->string('violation_sum', 255)->nullable();
            $table->string('violation_sum_text', 255)->nullable();
            $table->string('comments', 255)->nullable();
            $table->string('reviews', 255)->nullable();
        });
    }
}
