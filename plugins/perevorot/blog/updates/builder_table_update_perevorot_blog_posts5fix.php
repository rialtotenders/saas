<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotBlogPosts5fix extends Migration
{
    public function up()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {           
            //$table->dropColumn('photo');
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