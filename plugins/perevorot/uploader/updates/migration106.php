<?php namespace Perevorot\Uploader\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class Migration106 extends Migration
{
    public function up()
    {
        Schema::table('system_files', function($table)
        {
            if(!Schema::hasColumn('system_files', 'doc_cdb_type')) {
                $table->string('doc_cdb_type')->nullable();
            }
            if(!Schema::hasColumn('system_files', 'conf_text')) {
                $table->text('conf_text')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('system_files', function($table)
        {
            if(Schema::hasColumn('system_files', 'doc_cdb_type')) {
                $table->dropColumn('doc_cdb_type');
            }
            if(Schema::hasColumn('system_files', 'conf_text')) {
                $table->dropColumn('conf_text');
            }
        });
    }
}