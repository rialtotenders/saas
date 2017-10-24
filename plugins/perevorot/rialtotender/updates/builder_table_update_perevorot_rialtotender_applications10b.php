<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderApplications10b extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_applications', function($table)
        {
            if(!Schema::hasColumn('perevorot_rialtotender_applications', 'lot_features')) {
                $table->text('lot_features')->nullable();
            }
            if(Schema::hasColumn('perevorot_rialtotender_applications', 'features')) {
                $table->renameColumn('features', 'tender_features');
            }
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_applications', function($table)
        {
            if(Schema::hasColumn('perevorot_rialtotender_applications', 'lot_features')) {
                $table->dropColumn('lot_features');
            }
            if(Schema::hasColumn('perevorot_rialtotender_applications', 'tender_features')) {
                $table->renameColumn('tender_features', 'features');
            }
        });
    }
}