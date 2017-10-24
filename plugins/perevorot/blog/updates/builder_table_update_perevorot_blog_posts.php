<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotBlogPosts extends Migration
{
    public function up()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            $table->text('full_description');
            $table->text('short_description');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            $table->dropColumn('full_description');
            $table->dropColumn('short_description');
        });
    }
}
