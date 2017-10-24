<?php namespace Perevorot\Rialtotender\Models;

use Model;

/**
 * Model
 */
class QualificationFile extends Model
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
    public $table = 'perevorot_rialtotender_qualification_files';
}