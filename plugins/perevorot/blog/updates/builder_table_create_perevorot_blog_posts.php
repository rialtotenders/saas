<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotBlogPosts extends Migration
{
    public function up()
    {
        Schema::create('perevorot_blog_posts', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('title');
            $table->string('slug');
            $table->integer('author_id')->unsigned();
            $table->dateTime('published_at');
            $table->boolean('is_enabled');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_blog_posts');
    }
}