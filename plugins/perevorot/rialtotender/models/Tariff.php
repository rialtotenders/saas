<?php namespace Perevorot\Rialtotender\Models;

use Model;

/**
 * Model
 */
class Tariff extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [
        'price_from' => 'required',
        'price_to' => 'required',
        'sum' => 'required',
        'currency_id' => 'required'
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'perevorot_rialtotender_tariffs';

    public $hasOne = [
        'currency' => ['Perevorot\Rialtotender\Models\Currency', 'key' => 'id', 'otherKey' => 'currency_id']
    ];

    public function beforeSave() {
        if(isset($_GET['is_gov'])) {
            $this->is_gov = (int)$_GET['is_gov'];
        }
    }

    public function getCurrencyIdOptions()
    {
        $model = Currency::lists('name', 'id');
        return $model;
    }

    public static function getTariff($params = [])
    {
        return self::select('perevorot_rialtotender_tariffs.*')
            ->join('perevorot_rialtotender_currencies', 'perevorot_rialtotender_tariffs.currency_id', '=', 'perevorot_rialtotender_currencies.id')
            ->where('perevorot_rialtotender_currencies.code', '=', $params['currency'])
            ->where('perevorot_rialtotender_tariffs.price_from', '<=', $params['price'])
            ->where('perevorot_rialtotender_tariffs.price_to', '>=', $params['price'])
            ->where('perevorot_rialtotender_tariffs.is_gov', $params['is_gov'])
            ->first();
    }
}