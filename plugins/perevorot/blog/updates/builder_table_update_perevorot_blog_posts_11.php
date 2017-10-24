<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotBlogPosts11 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            $table->text('body');
            $table->dropColumn('longread_ua');
            $table->dropColumn('longread_en');
            $table->dropColumn('longread_ru');
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_blog_posts', function($table)
        {
            $table->dropColumn('body');
            $table->text('longread_ua')->nullable();
            $table->text('longread_en')->nullable();
            $table->text('longread_ru')->nullable();
        });
    }
}
