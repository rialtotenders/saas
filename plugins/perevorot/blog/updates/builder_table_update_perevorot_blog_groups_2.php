<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotBlogGroups2 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_blog_groups', function($table)
        {
            $table->integer('sort_order')->default(1);
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_blog_groups', function($table)
        {
            $table->dropColumn('sort_order');
        });
    }
}
