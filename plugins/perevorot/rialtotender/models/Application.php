<?php namespace Perevorot\Rialtotender\Models;

use Model;
use System\Models\File;

/**
 * Model
 */
class Application extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $casts = ['is_gov'];

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

    public $table = 'perevorot_rialtotender_applications';

    public $attachMany = [
        'documents' => [
            'System\Models\File'
        ],
        'qualificationDocuments' => [
            'System\Models\File'
        ]
    ];

    public $attachOne = [
        'changeDocument' => [
            'System\Models\File'
        ],
    ];

    public $belongsTo = [
        'tender' => [
            'Perevorot\Rialtotender\Models\Tender',
            'key' => 'tender_id',
            'otherKey' => 'tender_system_id'
        ],
        'user' => [
            'Perevorot\Users\Models\User',
            'key' => 'user_id',
            'otherKey' => 'id'
        ]
    ];

    public $hasMany = [
        'bidDocuments' => [
            'Perevorot\Rialtotender\Models\ApplicationFile',
            'key'=>'bid_id',
            'otherKey'=>'bid_id'
        ],
    ];
    
    public function getFormattedPriceAttribute()
    {
        return str_replace('.00', '', number_format($this->feature_price ? $this->feature_price : $this->price, 2, '.', ' '));
    }

    public function clearDocuments()
    {
        foreach($this->documents as $k => $file) {
            $_file = str_replace(env('APP_URL'),base_path(),$file->path);
            if(!file_exists($_file)) {
                $file->delete();
                unset($this->documents[$k]);
            }
        }
    }

    public function clearUnattachedFiles($user_id)
    {
        File::whereNull('field')->whereNull('attachment_id')->whereNull('attachment_type')->where('user_id', $user_id)->delete();
    }
    
    public function clearChangeDocuments()
    {
        foreach($this->bidDocuments as $applicationFile){
            if((int)$applicationFile->change_system_file_id>0){
                
                $file=File::find($applicationFile->change_system_file_id);
                $file->delete();
                
                $applicationFile->change_system_file_id=0;
                $applicationFile->save();
            }
        }
    }

    public static function getData($params = [])
    {
        return self::byUser(@$params['user_id'])
            ->byTender(@$params['tender_id'])
            ->byLot(@$params['lot_id'])
            ->byId(@$params['id'])
            ->byTest(@$params['test'])
            ->byUrlSend(@$params['is_url_send'])
            ->byNotEmptyBid()
            ->byLimit(@$params['limit']);
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

    public function scopebyUrlSend($query, $data)
    {
        if($data !== null)
        {
            return $query->where("is_url_send", $data);
        }
    }

    public function scopebyTest($query, $data)
    {
        if($data !== null)
        {
            return $query->where("is_test", (int) $data);
        }
    }


    public function scopebyNotEmptyBid($query)
    {
        return $query->whereNotNull("bid_id");
    }

    public function scopebyId($query, $data)
    {
        if($data)
        {
            return $query->where("id", $data);
        }
    }

    public function scopebyLot($query, $data)
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

    public function scopebyUser($query, $data)
    {
        if($data)
        {
            return $query->where('user_id', $data);
        }
    }
}