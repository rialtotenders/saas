<?php namespace Perevorot\Rialtotender\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Home extends Controller
{
    public $implement = ['Backend\Behaviors\ListController'];
    
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Perevorot.Rialtotender', 'rialtotender', 'side-menu-item2');
    }

    public $requiredPermissions = [
        'rialtotender.inform.*'
    ];
}