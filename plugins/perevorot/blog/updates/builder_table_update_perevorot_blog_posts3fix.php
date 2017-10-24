<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotBlogPosts3fix extends Migration
{
    public function up()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            //$table->string('photo', 255)->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            //$table->string('photo', 255)->nullable(false)->change();
        });
    }
}