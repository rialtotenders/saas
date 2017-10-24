<?php namespace Perevorot\Rialtotender\Models;

use Model;
use Perevorot\Users\Facades\Auth;
use System\Models\File;

/**
 * Model
 */
class Contract extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'perevorot_rialtotender_contracts';

    public $attachOne = [
        'changeDocument' => [
            'System\Models\File'
        ]
    ];

    public $attachMany = [
        'contractDocuments' => [
            'System\Models\File'
        ],
        'activeContractDocuments' => [
            'System\Models\File'
        ],
        'otherActiveContractDocuments' => [
            'System\Models\File'
        ],
        'changeDocuments' => [
            'System\Models\File'
        ],
    ];

    public $hasMany = [
        'allContractDocuments' => [
            'Perevorot\Rialtotender\Models\ContractFile',
            'key'=>'contract_id',
            'otherKey'=>'contract_id'
        ],
        'allActiveContractDocuments' => [
            'Perevorot\Rialtotender\Models\ContractFile',
            'key'=>'contract_id',
            'otherKey'=>'contract_id'
        ],
        'otherAllActiveContractDocuments' => [
            'Perevorot\Rialtotender\Models\ContractFile',
            'key'=>'contract_id',
            'otherKey'=>'contract_id'
        ],
        'allChangeDocuments' => [
            'Perevorot\Rialtotender\Models\ChangeFile',
            'key'=>'change_id',
            'otherKey'=>'change_id'
        ],
    ];

    public $belongsTo = [
        'tender' => ['Perevorot\Rialtotender\Models\Tender', 'key' => 'tender_id', 'otherKey' => 'tender_system_id'],
        'user' => ['Perevorot\Users\Models\User', 'key' => 'user_id', 'otherKey' => 'id'],
    ];

    public function clearChangeDocuments($relation = 'allContractDocuments')
    {
        foreach($this->{$relation} as $applicationFile){
            if((int)$applicationFile->change_system_file_id>0){

                if($file=File::find($applicationFile->change_system_file_id)) {
                    $file->delete();
                }

                $applicationFile->change_system_file_id=0;
                $applicationFile->save();
            }
        }
    }

    public static function getContracts() {
        return self::
                where('user_id', Auth::getUser()->id)
                ->where('status', 'active')
                ->get();
    }

    public static function getData($params = [])
    {
        return self::byUser(@$params['user_id'])
            ->byTender(@$params['tender_id'])
            ->byLot(@$params['lot_id'])
            ->byId(@$params['id'])
            ->byCid(@$params['cid'])
            ->byStatus(@$params['status'])
            ->byTest(@$params['test'])
            ->orderBy('id')
            ->byLimit(@$params['limit']);
    }

    public function scopebyStatus($query, $data)
    {
        if($data !== null)
        {
            return $query->where('status', $data);
        } else {
            return $query->where('status', '!=', 'pending');
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

    public function scopebylot($query, $data)
    {
        if($data)
        {
            return $query->where("lot_id", $data);
        }
    }

    public function scopebyTender($query, $data)
    {
        if($data)
        {
            return $query->where("tender_id", $data);
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
    
    public function scopebyCid($query, $data)
    {
        if($data)
        {
            return $query->where("cid", $data);
        }
    }

    public function scopebyUser($query, $data)
    {
        if($data)
        {
            return $query->where('user_id', $data);
        }
    }

    public static function getPendingContract($data, $id = '') {

        if($id) {
            if ($c = self::where('contract_id', $id)->where('is_test', Auth::getUser()->is_test)->where('user_id', Auth::getUser()->id)->first()) {
                return $c;
            }
        }
        elseif(!$id) {
            foreach ($data AS $v) {
                if ($v->status == 'pending') {
                    if ($c = self::where('contract_id', $v->id)->where('is_test', Auth::getUser()->is_test)->where('user_id', Auth::getUser()->id)->first()) {
                        return $c;
                    }
                }
            }
        }

        return false;
    }

    public function getJson()
    {
        return json_decode($this->json);
    }

    public function processFields($fields)
    {
        $json = $fields;

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
                    if($k == 'items') {

                        foreach($value AS $vk => $item)
                        {
                            foreach($item AS $ik => $_item_data) {

                                if($ik == 'unit') {
                                    $_item_data['name'] = Classifier::getMeasurers($_item_data['code']);
                                }

                                $_json->{$k}[$vk]->$ik = is_array($_item_data) ? (object)$_item_data : $_item_data;
                            }
                        }
                    }
                    elseif($k == 'change') {

                        if(!isset($_json->{$k})) {
                            $_json->{$k} = new \stdClass();
                        }

                        $_json->{$k} = $value;
                    }
                    elseif($k == 'value') {
                        $_json->{$k}->amount = $value['amount'];
                    }
                    else
                    {
                        $_json->{$k} = is_array($value) ? (object)$value : $value;
                    }
                }
            }
            else
            {
                $_json = $json;
            }

            unset($_json->last_change);

            $this->attributes['json'] = json_encode($_json);
        }
    }
}