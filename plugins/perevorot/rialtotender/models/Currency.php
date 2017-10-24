<?php namespace Perevorot\Rialtotender\Models;

use Model;

/**
 * Model
 */
class Currency extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;
    /*
     * Validation
     */
    public $rules = [
        'name' => 'required',
        'code' => 'required'
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'perevorot_rialtotender_currencies';

    public $implement = [
        '@RainLab.Translate.Behaviors.TranslatableModel',
    ];

    public $translatable = [
        'name',
    ];
}