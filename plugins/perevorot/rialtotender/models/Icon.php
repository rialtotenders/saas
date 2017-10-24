<?php namespace Perevorot\Rialtotender\Models;

use Model;
use October\Rain\Database\Traits\Validation;

/**
 * Model
 */
class Icon extends Model
{
    use Validation;

    public $implement = [
        'RainLab.Translate.Behaviors.TranslatableModel',
    ];

    public $translatable = [
        ['name', 'index' => true],
    ];

    /*
     * Validation
     */
    public $rules = [
        'name' => 'required',
        'image' => 'required',
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'perevorot_rialtotender_icons';

    /**
     * @var array
     */
    public $attachOne = [
        'image' => [
            'System\Models\File',
        ],
    ];
}