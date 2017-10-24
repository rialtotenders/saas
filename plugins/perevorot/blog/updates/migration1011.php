<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class Migration1011 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_blog_tag_to_post', function($table)
        {
            $table->foreign('tag_id')->references('id')->on('perevorot_blog_tags')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('perevorot_blog_posts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('perevorot_blog_tag_to_post', function (Blueprint $table)
        {           
            $table->dropForeign(['tag_id']);
            $table->dropForeign(['post_id']);
        });
    }
}