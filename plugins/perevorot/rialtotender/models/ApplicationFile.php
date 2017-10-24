<?php namespace Perevorot\Rialtotender\Models;

use Model;

/**
 * Model
 */
class ApplicationFile extends Model
{
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
    
    public $jsonable=[
        'json'
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'perevorot_rialtotender_application_files';

    public $belongsToMany = [
        'bids' => [
            'Perevorot\Rialtotender\Models\Application',
            'key'=>'bid_id',
            'otherKey'=>'bid_id'
        ]
    ];
}