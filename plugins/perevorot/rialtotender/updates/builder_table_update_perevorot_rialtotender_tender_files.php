<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderTenderFiles extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_tender_files', function($table)
        {
            if(!Schema::hasColumn('perevorot_rialtotender_tender_files', 'lot_id')) {
                $table->string('lot_id')->nullable();
            }
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_tender_files', function($table)
        {
            if(Schema::hasColumn('perevorot_rialtotender_tender_files', 'lot_id')) {
                $table->dropColumn('lot_id');
            }
        });
    }
}