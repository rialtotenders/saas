<?php

namespace Perevorot\Users\Models;

use Model;
use Perevorot\Rialtotender\Models\Status;

/**
 * User Model
 */
class MessageTenderPage extends Model
{
    /**
     * @var array
     */
    public $implement = [
        'System.Behaviors.SettingsModel',
        '@RainLab.Translate.Behaviors.TranslatableModel',        
    ];

    public $translatable = [
        'header1', 'step1',
    ];
    
    /**
     * @var string
     */
    public $settingsCode = 'perevorot.users.messagetenderpage';

    /**
     * @var string
     */
    public $settingsFields = 'fields.yaml';

    public function __construct()
    {
        parent::__construct();
    }

    public function getUserTypeOptions() {
        return [
            'all' => 'Любой',
            'supplier' => 'Поставщик',
            'customer' => 'Заказчик',
        ];
    }

    public function getTenderStatusOptions() {
        return Status::getStatuses('tender');
    }

    public function get_value($name)
    {
        if(isset($this->value) && isset($this->value[$name]))
        {
            return $this->value[$name];
        }

        return null;
    }
}
