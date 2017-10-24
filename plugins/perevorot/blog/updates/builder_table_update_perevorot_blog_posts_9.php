<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotBlogPosts9 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            $table->integer('group_id')->unsigned();
            $table->dropColumn('type');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            $table->dropColumn('group_id');
            $table->integer('type')->unsigned()->default(1);
        });
    }
}
