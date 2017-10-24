<?php

namespace Perevorot\Users\Models;

use Model;

/**
 * User Model
 */
class MessagePlan extends Model
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
    public $settingsCode = 'perevorot.users.messageplan';

    /**
     * @var string
     */
    public $settingsFields = 'fields.yaml';

    public function __construct()
    {
        parent::__construct();
    }
}
