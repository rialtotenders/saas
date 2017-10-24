<?php namespace Perevorot\Rialtotender\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Model;
use Perevorot\Users\Facades\Auth;
use System\Models\File;

/**
 * Model
 */
class Tender extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'perevorot_rialtotender_tenders';

    public $attachMany = [
        'documents' => [
            'System\Models\File'
        ],
        'cancellingDocuments' => [
            'System\Models\File'
        ],
        'awardDocuments' => [
            'System\Models\File'
        ],
    ];

    public $attachOne = [
        'changeDocument' => [
            'System\Models\File'
        ]
    ];

    public $hasMany = [
        'tenderDocuments' => [
            'Perevorot\Rialtotender\Models\TenderFile',
            'key'=>'tender_id',
            'otherKey'=>'tender_system_id'
        ],
        'applications' => [
            'Perevorot\Rialtotender\Models\Application',
            'key' => 'tender_id',
            'otherKey' => 'tender_system_id'
        ],
        'questions' => [
            'Perevorot\Rialtotender\Models\Question',
            'key' => 'tender_id',
            'otherKey' => 'tender_system_id'
        ],
    ];

    public $belongsTo = [
        'user' => ['Perevorot\Users\Models\User', 'key' => 'user_id', 'otherKey' => 'id'],
    ];


    public function bidsGroup($ids = []) {

        if(!empty($ids)) {
            return $this->applications()->whereNotNull('bid_id')->whereNotIn('user_id', $ids)->groupBy('user_id')->get();
        } else {
            return $this->applications()->whereNotNull('bid_id')->groupBy('user_id')->get();
        }
    }

    public function questionsGroup() {
        return $this->questions()->groupBy('user_id')->get();
    }

    public function clearAwardDocuments()
    {
        foreach($this->awardDocuments as $applicationFile){
            $applicationFile->delete();
        }
    }

    public function clearDocuments() {
        if (count($this->documents) > 0) {
            foreach ($this->documents AS $doc) {
                $doc->delete();
            }
        }
    }

    public function clearChangeDocuments()
    {
        foreach($this->tenderDocuments as $applicationFile){
            if((int)$applicationFile->change_system_file_id>0){

                $file=File::find($applicationFile->change_system_file_id);
                $file->delete();

                $applicationFile->change_system_file_id=0;
                $applicationFile->save();
            }
        }
    }

    public function beforeSave()
    {
        $json = json_decode($this->json);

        if(isset($json->tender->enquiryPeriod->endDate))
        {
            $json->tender->enquiryPeriod->startDate = Carbon::now()->addMinutes(15)->format('H:i d.m.Y');
            $this->json = json_encode($json);
        }

        if(isset($json->lots) && !is_array($json->lots)) {
            $json->lots = (array)$json->lots;
        }
    }

    public static function getData($params = [])
    {
        return self::byUser(@$params['user_id'])
            ->byTender(@$params['tender_id'])
            ->byTenderId(@$params['tender_system_id'])
            ->byId(@$params['id'])
            ->byComplete(@$params['complete'])
            ->byClosed(@$params['without_closed'])
            ->byEmptyStatus(@$params['without_status'])
            ->byType(@$params['type'])
            ->byTest(@$params['test'])
            ->byGov(@$params['gov'])
            ->orderBy('tender_id')
            ->byLimit(@$params['limit']);
    }

    public function scopebyType($query, $data)
    {
        if((int) $data === 2)
        {
            return $query->whereNull('status');
        }elseif((int) $data === 1)
        {
            return $query->whereNotNull('status');
        }
    }

    public function scopebyEmptyStatus($query, $data)
    {
        if($data)
        {
            return $query->whereNull('status');
        }
    }

    public function scopebyClosed($query, $data)
    {
        if($data)
        {
            return $query->where('is_closed', '!=', 1);
        }
    }

    public function scopebyComplete($query, $data)
    {
        if($data !== null)
        {
            return $query->where('is_complete', $data);
        }
    }

    public function scopebyLimit($query, $data)
    {
        switch ($data)
        {
            case 1:
                return $query->first();
                break;
            default:
                return $query->get();
                break;
        }
    }

    public function scopebyTenderId($query, $data)
    {
        if($data)
        {
            return $query->where("tender_system_id", $data);
        }
    }

    public function scopebyTender($query, $data)
    {
        if($data)
        {
            return $query->where("tender_id", $data);
        }
    }

    public function scopebyGov($query, $data)
    {
        if($data !== null)
        {
            return $query->where("is_gov", (int) $data);
        }
    }

    public function scopebyTest($query, $data)
    {
        if($data !== null)
        {
            return $query->where("is_test", (int) $data);
        }
    }

    public function scopebyId($query, $data)
    {
        if($data)
        {
            return $query->where("id", $data);
        }
    }

    public function scopebyUser($query, $data)
    {
        if($data)
        {
            return $query->where('user_id', $data);
        }
    }

    public function processFields($fields)
    {
        if(isset($this->attributes['step'])) {
            $fields['step'] = $fields['step'] == 8 ? 2 : $fields['step'];
            $this->attributes['step'] = $this->attributes['step'] < $fields['step'] ? $fields['step'] : $this->attributes['step'];
        } else {
            $this->attributes['step'] = $fields['step'];
        }

        unset($fields['_uploader']);
        unset($fields['step']);

        $json = $fields;
        $setting = Setting::instance();

        if(!empty($json)) {

            if(isset($this->attributes['json']))
            {
                $_json = json_decode($this->attributes['json']);
            }
            else
            {
                $_json = null;
            }

            if(is_object($_json))
            {
                foreach($json AS $k => $value)
                {
                    $convert = true;

                    if($k == 'features') {

                        foreach($value AS $fk => $feature) {
                            if(!$feature['title']) {
                                unset($value[$fk]);
                            }
                        }

                        if(is_array($value) && !empty($value)) {
                            $_json->{$k} = (array)$value;
                        } else {
                            unset($_json->{$k});
                        }

                        $convert = false;
                    }
                    elseif($k == 'guarantee')
                    {
                        if($setting->checkAccess('guarantee')) {
                            $value['currency'] = $_json->value->currency;
                        }
                    }
                    elseif($k == 'minimalStep' && isset($value['amount']))
                    {
                        $value['currency'] = $_json->value->currency;
                        $value['valueAddedTaxIncluded'] = $_json->value->valueAddedTaxIncluded;
                    }
                    elseif($k == 'lots') {
                        foreach($value AS $lk => $lot) {

                            $lot['is_empty_price'] = isset($lot['is_empty_price']) ? 1 : 0;

                            if($lot['is_empty_price']) {
                                $this->attributes['is_empty_price'] = 1;
                            }

                            foreach($lot AS $ik => $_lot_data) {
                                if($ik == 'items') {
                                    foreach($_lot_data AS $item_key => $_item) {
                                        foreach($_item AS $_ik => $_item_data) {
                                            if($_ik == 'unit') {
                                                $_item_data['name'] = Classifier::getMeasurers($_item_data['code']);
                                            }
                                            elseif($_ik == 'classification') {
                                                $_item_data['description'] = Classifier::getCpvByCode($_item_data['id']);
                                            }

                                            if (is_array($_item_data)) {
                                                $value[$lk][$ik][$item_key][$_ik] = (object)$_item_data;
                                            }
                                        }
                                    }
                                }
                                elseif($ik == 'features') {
                                    foreach($_lot_data AS $fk => $feature) {
                                        if(!$feature['title']) {
                                            unset($_lot_data[$fk]);
                                            unset($value[$lk][$ik]);
                                        }
                                    }

                                    if(is_array($_lot_data) && !empty($_lot_data)) {
                                        //$_json->{$k}[$lk]->$ik = (array)$_lot_data;
                                        $value[$lk][$ik] = (array)$_lot_data;
                                    } else {
                                        if(isset($_json->{$k}[$lk]->$ik)) {
                                            $_json->{$k}[$lk]->$ik;
                                        }
                                    }
                                }
                                elseif($ik == 'guarantee')
                                {
                                    if($setting->checkAccess('guarantee')) {
                                        $value[$lk][$ik]['currency'] = $_json->value->currency;
                                    }
                                }
                                elseif($ik == 'minimalStep' && isset($_lot_data['amount']))
                                {
                                    $value[$lk][$ik]['currency'] = $_json->value->currency;
                                    $value[$lk][$ik]['valueAddedTaxIncluded'] = $_json->value->valueAddedTaxIncluded;
                                }
                                elseif($ik == 'value' && isset($_lot_data['amount']))
                                {
                                    $value[$lk][$ik]['currency'] = $_json->value->currency;
                                    $value[$lk][$ik]['valueAddedTaxIncluded'] = $_json->value->valueAddedTaxIncluded;
                                }
                            }

                            if(isset($value[$lk]['features'])) {
                                $value[$lk]['features'] = (array)$value[$lk]['features'];
                            }
                            if(isset($value[$lk]['items'])) {
                                $value[$lk]['items'] = (array)$value[$lk]['items'];
                            }
                        }

                        $_json->{$k} = (array)$value;
                        $convert = false;
                    }
                    elseif($k == 'items') {
                        foreach($value AS $vk => $item) {
                            foreach($item AS $ik => $_item_data) {
                                if($ik == 'unit') {
                                    $_item_data['name'] = Classifier::getMeasurers($_item_data['code']);
                                }
                                elseif($ik == 'classification') {
                                    $_item_data['description'] = Classifier::getCpvByCode($_item_data['id']);
                                }

                                if (is_array($_item_data)) {
                                    $value[$vk][$ik] = (object)$_item_data;
                                }
                            }
                        }

                        $_json->{$k} = (array)$value;
                        $convert = false;
                    }

                    if($convert) {
                        $_json->{$k} = is_array($value) ? (object)$value : $value;
                    }
                }

                if(isset($fields['title']) && !isset($fields['lot'])) {
                    $_json->lot = 0;
                }
                if(isset($fields['title']) && !isset($fields['criteria'])) {
                    $_json->criteria = 0;
                }

                if(!$_json->lot && post('step') == 2) {
                    $_json->is_empty_price = isset($fields['is_empty_price']) ? 1 : 0;
                    $this->attributes['is_empty_price'] = $_json->is_empty_price;
                }
            }
            else
            {
                if(!isset($fields['lot'])) {
                    $json['lot'] = 0;
                }
                if(!isset($fields['criteria'])) {
                    $json['criteria'] = 0;
                }

                $_json = $json;

                $this->attributes['user_id'] = Auth::getUser()->id;
                $this->attributes['is_test'] = (int) Auth::getUser()->is_test;
                $this->attributes['is_gov'] = (int) Auth::getUser()->is_gov;
            }

            $this->attributes['json'] = json_encode($_json);
        }

        $this->attributes['created_at'] = Carbon::now();
    }

    public function getJson()
    {
        return json_decode($this->json);
    }
}