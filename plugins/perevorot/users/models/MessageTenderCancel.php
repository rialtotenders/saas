<?php

namespace Perevorot\Users\Models;

use Model;

/**
 * User Model
 */
class MessageTenderCancel extends Model
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
        'header2', 'step2',
    ];
    
    /**
     * @var string
     */
    public $settingsCode = 'perevorot.users.messagetendercancel';

    /**
     * @var string
     */
    public $settingsFields = 'fields.yaml';

    public function __construct()
    {
        parent::__construct();
    }
}
