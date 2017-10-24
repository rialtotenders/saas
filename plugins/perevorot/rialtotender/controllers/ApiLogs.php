<?php namespace Perevorot\Rialtotender\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Perevorot\Rialtotender\Models\ApiLog;
use System\Classes\SettingsManager;

class ApiLogs extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = [
        'rialtotender.apilog_permission' 
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Perevorot.Rialtotender', 'apilogs');
    }

    public function onDelete()
    {
        ApiLog::truncate();
        return $this->listRefresh();
    }
}