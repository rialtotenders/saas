<?php

namespace Perevorot\Users\Models;

use Model;

/**
 * User Model
 */
class Message extends Model
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
        'header3', 'step3',
        'header4', 'step4',
    ];
    
    /**
     * @var string
     */
    public $settingsCode = 'perevorot.users.message';

    /**
     * @var string
     */
    public $settingsFields = 'fields.yaml';

    public function __construct()
    {
        parent::__construct();

        $this->noFallbackLocale();
    }
}
