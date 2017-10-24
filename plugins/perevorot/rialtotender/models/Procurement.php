<?php namespace Perevorot\Rialtotender\Models;

use Model;
use Lang;

/**
 * Model
 */
class Procurement extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $implement = [
        'System.Behaviors.SettingsModel',
        '@RainLab.Translate.Behaviors.TranslatableModel',
    ];

    /**
     * @var string
     */
    public $settingsCode = 'perevorot.rialtotender.procurement';

    /**
     * @var string
     */
    public $settingsFields = 'fields.yaml';

    public $translatable = [
        '_type',
        '_method_type',
        'rationaletypes',
        'document_types',
    ];

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

    public static function getData($type, $code = false)
    {
        $data = self::instance();

        if(isset($data->{$type}) && $data->{$type} != "")
        {
            $tmp = explode("\n", (trim($data->{$type})."\n"));
            $statuses = [];

            foreach($tmp AS $v)
            {
                if(stripos($v, '=') !== FALSE) {

                    $status = explode("=", $v);

                    if(count($status) == 2) {

                        $status[0] = trim($status[0]);
                        $status[1] = trim($status[1]);

                        if($status[0] && $status[1]) {

                            if($code && $code == $status[0]) {
                                return $status[1];
                            }
                            else
                            {
                                $statuses[$status[0]] = $status[1];
                            }
                        }
                    }
                }
            }
        }
        else
        {
            return [];
        }

        return $statuses;
    }

}