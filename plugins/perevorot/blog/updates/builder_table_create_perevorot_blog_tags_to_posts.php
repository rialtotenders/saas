<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotBlogTagsToPosts extends Migration
{
    public function up()
    {
        Schema::create('perevorot_blog_tags_to_posts', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('tag_id')->unsigned();
            $table->integer('post_id')->unsigned();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_blog_tags_to_posts');
    }
}