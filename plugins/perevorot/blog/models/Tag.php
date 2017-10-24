<?php namespace Perevorot\Blog\Models;

use Model;
use Str;

/**
 * Model
 */
class Tag extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [
    ];

    /*
     * Fillable
     */
    public $fillable = [
        'name'
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'perevorot_blog_tags';

    /**
    * something
    */
    public $belongsToMany = [
        'posts' => [
            'Perevorot\Blog\Models\Blog',
            'table'    => 'perevorot_blog_tag_to_post',
            'key' => 'tag_id',
            'otherKey' => 'post_id',
        ]
    ];

    public function beforeCreate()
    {
        $this->slug = Str::slug($this->name);
    }
}