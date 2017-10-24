<?php namespace Perevorot\Blog\Models;

use Model;

/**
 * Model
 */
class Author extends Model
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
    public $table = 'perevorot_blog_authors';

    public $attachOne = [
        'photo' => [
            'System\Models\File',
        ],
    ];
}