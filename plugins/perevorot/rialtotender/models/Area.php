<?php namespace Perevorot\Rialtotender\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\Sortable;
use Perevorot\Rialtotender\Traits\ModelTrait;

/**
 * Model
 */
class Area extends Model
{
    use Validation, Sortable, ModelTrait;

    public $implement = [
        'RainLab.Translate.Behaviors.TranslatableModel',
    ];

    public $translatable = [
        'description',
        ['title', 'index' => true],
    ];

    /*
     * Validation
     */
    public $rules = [
        'title' => 'required',
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
    public $table = 'perevorot_rialtotender_areas';

    /**
     * @var array
     */
    public $attachOne = [
        'image' => [
            'System\Models\File',
        ],
    ];
}