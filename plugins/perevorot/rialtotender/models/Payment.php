<?php namespace Perevorot\Rialtotender\Models;

use Model;

/**
 * Model
 */
class Payment extends Model
{
    use \October\Rain\Database\Traits\Validation;

    CONST PAYMENT_TYPE_ADD_MONEY = 1;
    CONST PAYMENT_TYPE_CREATED_BID = 2;
    CONST PAYMENT_TYPE_CANCELED_BID = 3;

    /*
     * Validation
     */
    public $rules = [
        'date' => 'required',
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
    public $table = 'perevorot_rialtotender_payments';

    public $belongsTo = [
        'user' => ['Perevorot\Users\Models\User', 'key' => 'user_id', 'otherKey' => 'id'],
        'currency' => 'Perevorot\Rialtotender\Models\Currency',
    ];

    public function scopeByType($query)
    {
        return $query->where('type', Payment::PAYMENT_TYPE_ADD_MONEY);
    }

    public function getCurrencyIdOptions()
    {
        $model = Currency::lists('name', 'id');
        return $model;
    }

    public function getTypeAttribute($value)
    {
        switch ($value)
        {
            case self::PAYMENT_TYPE_ADD_MONEY:
                return 'Пополнение';
                break;
            case self::PAYMENT_TYPE_CREATED_BID:
                return 'Списание';
                break;
            case self::PAYMENT_TYPE_CANCELED_BID:
                return 'Отзыв предложения';
                break;

            default:
                return '';
                break;
        }
    }
}