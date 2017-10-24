<?php namespace Perevorot\Blog\Models;

use Model;

/**
 * Model
 */
class Group extends Model
{
    use \October\Rain\Database\Traits\Validation,
        \October\Rain\Database\Traits\Sortable;

    /**
     * @var array implemented traits.
     */
    public $implement = [
        '@RainLab.Translate.Behaviors.TranslatableModel',
    ];

    /*
     * Validation
     */
    public $rules = [
        'title' => 'required|string|max:255',
        'slug' => 'required|string|max:255',
        'is_enabled' => 'boolean',
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
     public $timestamps = true;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'perevorot_blog_groups';

    public $translatable = [
        'title',
    ];

    protected $fillable = [
        'title',
        'is_enabled',
    ];

    public $hasMany = [
        'blogs' => 'Perevorot\Blog\Models\Blog'
    ];

}