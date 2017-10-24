<?php namespace Perevorot\Rialtotender\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class SystemFiles extends Controller
{
    public $requiredPermissions = [
        'rialtotender.inform.systemfiles_permission'
    ];

    public $implement = ['Backend\Behaviors\ListController'];
    
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Perevorot.Rialtotender', 'rialtotender', 'systemfiles');
    }

    public function listExtendQuery($query)
    {
        $query->whereNull('user_id');
    }
}