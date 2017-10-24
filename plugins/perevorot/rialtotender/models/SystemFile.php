<?php namespace Perevorot\Rialtotender\Models;

use Model;
use System\Models\File;

/**
 * Model
 */
class SystemFile extends File
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
    public $table = 'system_files';
}