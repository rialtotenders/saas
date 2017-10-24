<?php namespace Perevorot\Rialtotender\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Plans extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = [
        'rialtotender.inform.plans_permission' 
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Perevorot.Rialtotender', 'rialtotender', 'side-menu-item4');
    }
}