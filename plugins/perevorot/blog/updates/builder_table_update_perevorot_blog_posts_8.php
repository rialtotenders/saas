<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotBlogPosts8 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            $table->integer('type')->unsigned()->default(1);
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            $table->dropColumn('type');
        });
    }
}
