<?php namespace Perevorot\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Illuminate\Support\Facades\DB;

class Migration1022 extends Migration
{
    public function up()
{
    
    DB::statement('ALTER TABLE `perevorot_blog_posts` MODIFY `longread_ua` LONGTEXT;');

}

public function down()
{
    DB::statement('ALTER TABLE `perevorot_blog_posts` MODIFY `longread_ua` TEXT;');
}
}