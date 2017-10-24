<?php namespace Perevorot\Uploader\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class Migration105 extends Migration
{
    public function up()
    {
        Schema::table('system_files', function($table)
        {
            if(!Schema::hasColumn('system_files', 'doc_type')) {
                $table->integer('doc_type')->default(1)->unsigned()->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('system_files', function($table)
        {
            if(Schema::hasColumn('system_files', 'doc_type')) {
                $table->dropColumn('doc_type');
            }
        });
    }
}