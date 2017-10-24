<?php

namespace Perevorot\Rialtotender\Models;

use Model;

/**
 * User Model
 */
class Setting extends Model
{
    /**
     * @var array
     */
    public $implement = [
        'System.Behaviors.SettingsModel',
        '@RainLab.Translate.Behaviors.TranslatableModel',
    ];
    
    /**
     * @var string
     */
    public $settingsCode = 'perevorot.rialtotender.setting';

    public $translatable = [
        'offerta_link',
    ];

    /**
     * @var array
     */
    public $attachOne = [
        'contract' => [
            'System\Models\File',
        ],
        'invoice' => [
            'System\Models\File',
        ],
    ];

    /**
     * @var string
     */
    public $settingsFields = 'fields.yaml';

    public static function getMaxFileUpload()
    {
        $setting = Setting::instance();

        if(isset($setting->value))
        {
            return isset($setting->value['max_file_size']) ? $setting->value['max_file_size'] : 0;
        }

        return 0;
    }

    public static function getData($name, $lang) {
        $setting = self::instance();
        return (isset($setting->$name) ? $setting->lang($lang)->$name : null);
    }

    public function get_value($name, $default = null)
    {
        if(isset($this->value) && isset($this->value[$name]))
        {
            return $this->value[$name];
        }

        return $default;
    }

    public function checkAccess($name)
    {
        if(isset($this->value))
        {
            if(isset($this->value[$name]) && $this->value[$name]) {
                return true;
            }
        }

        return false;
    }
}
