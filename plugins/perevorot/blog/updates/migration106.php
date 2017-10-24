<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Illuminate\Database\Schema\Blueprint;

class Migration106 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            $table->foreign('author_id')->references('id')->on('perevorot_blog_authors');
        });
        
        Schema::table('perevorot_blog_tags_to_posts', function($table)
        {
            $table->primary(['tag_id', 'post_id']);
        });
    }

    public function down()
    {
        Schema::table('perevorot_blog_posts', function (Blueprint $table)
        {           
            $table->dropForeign(['author_id']);
        });
        
        Schema::table('perevorot_blog_tags_to_posts', function (Blueprint $table)
        {           
            $table->dropPrimary(['tag_id', 'post_id']);
        });
    }
}