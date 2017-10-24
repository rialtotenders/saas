<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotBlogGroup extends Migration
{
    public function up()
    {
        Schema::table('perevorot_blog_group', function($table)
        {
            $table->boolean('is_enabled');
            $table->increments('id')->unsigned(false)->change();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_blog_group', function($table)
        {
            $table->dropColumn('is_enabled');
            $table->increments('id')->unsigned()->change();
        });
    }
}
