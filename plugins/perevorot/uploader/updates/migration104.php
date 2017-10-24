<?php namespace Perevorot\Uploader\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class Migration104 extends Migration
{
    public function up()
    {
        Schema::table('system_files', function($table)
        {
            if(!Schema::hasColumn('system_files', 'lot_id')) {
                $table->string('lot_id')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('system_files', function($table)
        {
            if(Schema::hasColumn('system_files', 'lot_id')) {
                $table->dropColumn('lot_id');
            }
        });
    }
}