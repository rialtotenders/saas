<?php namespace Perevorot\Rialtotender\Models;

use Model;
use Perevorot\Users\Facades\Auth;

/**
 * Model
 */
class TimingControl extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'perevorot_rialtotender_timing_controls';

    public static function getTiming($tender_id) {
        if($tender_id)
        {
            $tender = Tender::getData(['id' => $tender_id, 'user_id' => Auth::getUser()->id, 'limit' => 1]);

            if(isset($tender->id)) {
                return self::
                    where('price_from', '<=', $tender->value)
                    ->where('price_to', '>=', $tender->value)
                    ->first();
            }
        }

        return false;
    }
}