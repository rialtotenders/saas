<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class Migration109 extends Migration
{
    public function up()
    {
        Schema::dropIfExists('perevorot_blog_tags_to_posts');
        
        Schema::create('perevorot_blog_tag_to_post', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('tag_id')->unsigned();
            $table->integer('post_id')->unsigned();
            $table->primary(['tag_id', 'post_id']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_blog_tag_to_post');
    }
}