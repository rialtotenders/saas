<?php namespace Perevorot\Blog\Models;

use Model;

/**
 * Model
 */
class Blog extends Model
{
    use \October\Rain\Database\Traits\Validation;

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
        'published_at' => 'required|date',
        'slug' => 'required|string|max:255',
        'author_id' => 'required|numeric',
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
    public $table = 'perevorot_blog_posts';

    public $translatable = [
        'body',
        'title',
        'short_description',
        'full_description',
    ];

    protected $fillable = [
        'title',
        'short_description',
        'full_description',
        'author_id',
        'group_id',
        'photo',
        'body',
        'is_enabled',
        'published_at',
    ];

    /**
     * @var array
     */
    public $attachOne = [
        'photo' => [
            'System\Models\File',
        ],
    ];

    public $belongsToMany = [
        'tags' => [
            'Perevorot\Blog\Models\Tag',
            'table'    => 'perevorot_blog_tag_to_post',
            'key' => 'post_id',
            'otherKey' => 'tag_id',
        ]
    ];
    
    public $belongsTo = [
        'group' => ['Perevorot\Blog\Models\Group'],
        'author' => 'Perevorot\Blog\Models\Author',
    ];

    public function getAuthorIdOptions()
    {
        return array_pluck(Author::all()->toArray(), 'full_name', 'id');
    }
}