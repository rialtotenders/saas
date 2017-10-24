<?php namespace Perevorot\Rialtotender\Models;

use Model;

/**
 * Model
 */
class Category extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $implement = [
        '@RainLab.Translate.Behaviors.TranslatableModel',
    ];

    /*
     * Validation
     */
    public $rules = [
        'name' => 'required',
        'code' => 'required',
        'cpv' => 'required',
    ];

    public $translatable = [
        'name',
    ];

    public $fillable = ['code', 'name', 'cpv'];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'perevorot_rialtotender_categories';

    public static function getData($params = [])
    {
        return self::byCategoryCode(@$params['code'])
            ->orderBy('name', 'asc')
            ->get();
    }

    public function scopeByCategoryCode($query, $data)
    {
        if($data)
        {
            $query->where('code', $data);
        }
    }
}