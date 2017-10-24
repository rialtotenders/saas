<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotBlogGroups extends Migration
{
    public function up()
    {
        Schema::rename('perevorot_blog_group', 'perevorot_blog_groups');
    }
    
    public function down()
    {
        Schema::rename('perevorot_blog_groups', 'perevorot_blog_group');
    }
}
