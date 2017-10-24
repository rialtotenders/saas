<?php namespace Perevorot\Rialtotender\Models;

use Model;
use Lang;
use Perevorot\Rialtotender\Classes\SetConfig;

/**
 * Model
 */
class EnvSettings extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $implement = [
        'System.Behaviors.SettingsModel',
    ];

    /**
     * @var string
     */
    public $settingsCode = 'perevorot.rialtotender.envsettings';

    /**
     * @var string
     */
    public $settingsFields = 'fields.yaml';

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

    public function getSettingsAttribute()
    {
        $config = new SetConfig();
        $params = $config->getEnvAttribute(env('APP_THEME'));

        foreach($params AS $k => $v) {
            if(stripos($k, 'PASS') !== FALSE || stripos($k, 'KEY') !== FALSE || stripos($k, 'PROJECT_ID') !== FALSE) {
                if(strlen($v) > 7) {
                    $params->$k = substr($v, 0, 7).'...';
                } else {
                    $params->$k = substr($v, 0, 3).'...';
                }
            }
        }

        return $params;
    }
}