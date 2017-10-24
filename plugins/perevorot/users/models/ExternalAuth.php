<?php

namespace Perevorot\Users\Models;

use Model;

/**
 * User Model
 */
class ExternalAuth extends Model
{
    /**
     * @var array
     */
    public $implement = [
        'System.Behaviors.SettingsModel',
    ];

    /**
     * @var string
     */
    public $settingsCode = 'perevorot.users.externalauth';

    /**
     * @var string
     */
    public $settingsFields = 'fields.yaml';
}
