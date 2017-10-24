<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotBlogPosts3 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
        });
    }
}
