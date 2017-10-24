<?php namespace Perevorot\Rialtotender\Models;

use Model;

/**
 * Model
 */
class Qualification extends Model
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
    public $table = 'perevorot_rialtotender_qualifications';

    public $attachMany = [
        'qualificationDocuments' => [
            'System\Models\File'
        ],
    ];

    public static function getData($params = [])
    {
        return self::byUser(@$params['user_id'])
            ->byTender(@$params['tender_id'])
            ->byLot(@$params['lot_id'])
            ->byId(@$params['id'])
            ->byQid(@$params['qid'])
            //->byStatus(@$params['status'])
            ->byTest(@$params['test'])
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
                return $query->get()->keyBy('qualification_id');
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

    public function scopebyQid($query, $data)
    {
        if($data)
        {
            return $query->where("qualification_id", $data);
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
}