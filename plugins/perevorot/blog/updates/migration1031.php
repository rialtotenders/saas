<?php namespace Perevorot\Blog\Updates;

use Schema;
use DB;
use October\Rain\Database\Updates\Migration;

class Migration1031 extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE `perevorot_blog_posts` CHANGE `body` `body` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;');
    }

    public function down()
    {
        // Schema::drop('perevorot_blog_table');
    }
}