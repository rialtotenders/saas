<?php

namespace Perevorot\Rialtotender\Models;

use Model;

/**
 * User Model
 */
class MessagesExportExclude extends Model
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
    public $settingsCode = 'perevorot.rialtotender.messages_export_exclude';

    public $settingsFields = 'fields.yaml';
}
