<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotBlogTags extends Migration
{
    public function up()
    {
        Schema::create('perevorot_blog_tags', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('slug');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_blog_tags');
    }
}