<?php namespace Perevorot\Uploader\Updates;

use Illuminate\Support\Facades\DB;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\Contract;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Models\TenderFile;
use Schema;
use October\Rain\Database\Updates\Migration;
use System\Models\File;

class Migration103 extends Migration
{
    public function up()
    {
        if(DB::table('system_files')->whereNull('user_id')->count()) {

            $data = Tender::all();

            if(!$data->isEmpty()) {
                foreach ($data AS $tender) {
                    foreach ($tender->documents AS $doc) {
                        $doc->user_id = $tender->user_id;
                        $doc->save();
                    }
                    foreach ($tender->cancellingDocuments AS $doc) {
                        $doc->user_id = $tender->user_id;
                        $doc->save();
                    }
                    foreach ($tender->awardDocuments AS $doc) {
                        $doc->user_id = $tender->user_id;
                        $doc->save();
                    }
                    foreach ($tender->tenderDocuments AS $doc) {
                        if ($file = File::find($doc->system_file_id)) {
                            $file->user_id = $tender->user_id;
                            $file->save();
                        }
                    }
                }
            }

            $data = Application::all();

            if($data->isEmpty()) {
                foreach ($data AS $app) {
                    foreach ($app->documents AS $doc) {
                        $doc->user_id = $tender->user_id;
                        $doc->save();
                    }
                    foreach ($app->qualificationDocuments AS $doc) {
                        $doc->user_id = $app->user_id;
                        $doc->save();
                    }
                    foreach ($app->bidDocuments AS $doc) {
                        if ($file = File::find($doc->system_file_id)) {
                            $file->user_id = $app->user_id;
                            $file->save();
                        }
                    }
                }
            }

            $data = Contract::all();

            if($data->isEmpty()) {
                foreach ($data AS $contract) {
                    foreach ($contract->contractDocuments AS $doc) {
                        $doc->user_id = $contract->user_id;
                        $doc->save();
                    }
                    foreach ($contract->activeContractDocuments AS $doc) {
                        $doc->user_id = $contract->user_id;
                        $doc->save();
                    }
                    foreach ($contract->otherActiveContractDocuments AS $doc) {
                        $doc->user_id = $contract->user_id;
                        $doc->save();
                    }
                    foreach ($contract->changeDocuments AS $doc) {
                        $doc->user_id = $contract->user_id;
                        $doc->save();
                    }
                    foreach ($contract->allContractDocuments AS $doc) {
                        if ($file = File::find($doc->system_file_id)) {
                            $file->user_id = $contract->user_id;
                            $file->save();
                        }
                    }
                    foreach ($contract->allActiveContractDocuments AS $doc) {
                        if ($file = File::find($doc->system_file_id)) {
                            $file->user_id = $contract->user_id;
                            $file->save();
                        }
                    }
                    foreach ($contract->otherAllActiveContractDocuments AS $doc) {
                        if ($file = File::find($doc->system_file_id)) {
                            $file->user_id = $contract->user_id;
                            $file->save();
                        }
                    }
                    foreach ($contract->allChangeDocuments AS $doc) {
                        if ($file = File::find($doc->system_file_id)) {
                            $file->user_id = $contract->user_id;
                            $file->save();
                        }
                    }
                }
            }
        }
    }

    public function down()
    {
    }
}