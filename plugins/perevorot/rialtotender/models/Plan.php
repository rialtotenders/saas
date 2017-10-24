<?php namespace Perevorot\Rialtotender\Models;

use Carbon\Carbon;
use Model;
use Perevorot\Users\Facades\Auth;

/**
 * Model
 */
class Plan extends Model
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
    public $table = 'perevorot_rialtotender_plans';

    public $belongsTo = [
        'user' => ['Perevorot\Users\Models\User', 'key' => 'user_id', 'otherKey' => 'id'],
    ];

    public function json($field)
    {
        $json = json_decode($this->json);

        switch ($field)
        {
            case 'cpv':
                return substr($json->classification->id, 0 ,3) . ' ' . Classifier::getCpvByCode($json->classification->id);
                break;
            case 'month':
                return isset($json->month) ? $json->month : '';
                break;
            case 'year':
                return isset($json->budget->year) ? $json->budget->year : '';
                break;
        }
    }

    public function getJson()
    {
        return json_decode($this->json);
    }

    public static function getData($params = [])
    {
        return self::byUser(@$params['user_id'])
            ->byPlan(@$params['plan_id'])
            ->byId(@$params['id'])
            ->byComplete(@$params['complete'])
            ->byTest(@$params['test'])
            ->byGov(@$params['gov'])
            ->orderBy('plan_id')
            ->byLimit(@$params['limit']);
    }

    public function scopebyGov($query, $data)
    {
        if($data !== null)
        {
            return $query->where('is_gov', (int) $data);
        }
    }

    public function scopebyTest($query, $data)
    {
        if($data !== null)
        {
            return $query->where('is_test', (int) $data);
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

    public function scopebyPlan($query, $data)
    {
        if($data)
        {
            return $query->where("plan_id", $data);
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

                                if (is_array($_item_data)) {
                                    $value[$vk][$ik] = (object)$_item_data;
                                }
                            }
                        }

                        $_json->{$k} = (array)$value;
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

                $this->attributes['user_id'] = Auth::getUser()->id;
                $this->attributes['is_test'] = (int) Auth::getUser()->is_test;
                $this->attributes['is_gov'] = (int) Auth::getUser()->is_gov;
            }

            if(is_array($_json)) {
                $_json['tender']['tenderPeriod']['startDate'] = Carbon::createFromFormat('Y-n-d H:i:s', "{$_json['budget']['year']}-{$_json['month']}-01 00:00:00")->toAtomString();
            } else {

                if(!isset($_json->tender->tenderPeriod)) {
                    $_json->tender->tenderPeriod = new \stdClass();
                }
                $_json->tender->tenderPeriod->startDate = Carbon::createFromFormat('Y-n-d H:i:s', "{$_json->budget->year}-{$_json->month}-01 00:00:00")->toAtomString();
            }

            $this->attributes['json'] = json_encode($_json);
        }

        $this->attributes['created_at'] = Carbon::now();
    }
}