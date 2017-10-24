<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotBlogPosts7 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            $table->string('group')->nullable();
            $table->string('budget')->nullable();
            $table->string('sum')->nullable();
            $table->string('saving')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            $table->dropColumn('group');
            $table->dropColumn('budget');
            $table->dropColumn('sum');
            $table->dropColumn('saving');
        });
    }
}
