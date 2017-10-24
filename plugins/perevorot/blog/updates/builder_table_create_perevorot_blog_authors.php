<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePerevorotBlogAuthors extends Migration
{
    public function up()
    {
        Schema::create('perevorot_blog_authors', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('full_name');
            $table->string('slug');
            $table->text('description')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('perevorot_blog_authors');
    }
}
