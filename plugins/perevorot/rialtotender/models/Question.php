<?php namespace Perevorot\Rialtotender\Models;

use Model;

/**
 * Model
 */
class Question extends Model
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
    public $table = 'perevorot_rialtotender_questions';

    public $belongsTo = [
        'tender' => ['Perevorot\Rialtotender\Models\Tender', 'key' => 'tender_id', 'otherKey' => 'tender_system_id'],
        'user' => ['Perevorot\Users\Models\User', 'key' => 'user_id', 'otherKey' => 'id'],
    ];

    public static function getData($params = [])
    {
        return self::
            byTender(@$params['tender_id'])
            ->byUser(@$params['user_id'])
            ->byId(@$params['id'])
            ->byQId(@$params['qid'])
            ->byLotId(@$params['lot_id'])
            ->orderBy('created_at')
            ->get();
    }

    public function scopeByLotId($query, $data)
    {
        if($data) {
            return $query->where('lot_id', $data);
        }
    }

    public function scopeByQId($query, $data)
    {
        if($data)
        {
            return $query->where('qid', $data);
        }
    }

    public function scopeById($query, $data)
    {
        if($data)
        {
            return $query->where('id', $data);
        }
    }

    public function scopeByUser($query, $data)
    {
        if($data)
        {
            return $query->where('user_id', $data);
        }
    }

    public function scopeByTender($query, $data)
    {
        if($data)
        {
            return $query->where('tender_id', $data);
        }
    }
}