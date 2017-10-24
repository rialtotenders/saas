<?php

namespace Perevorot\Users\Models;

use Model;

/**
 * User Model
 */
class MessageApplication extends Model
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
        'header5', 'step5',
        'header6', 'step6',
        'header7', 'step7',
    ];

    /**
     * @var string
     */
    public $settingsCode = 'perevorot.users.message_application';

    /**
     * @var string
     */
    public $settingsFields = 'fields.yaml';
}
