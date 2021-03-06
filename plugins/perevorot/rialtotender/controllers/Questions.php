<?php namespace Perevorot\Rialtotender\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Questions extends Controller
{
    public $requiredPermissions = [
        'rialtotender.inform.questions_permission'
    ];

    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Perevorot.Rialtotender', 'rialtotender', 'side-menu-item');
    }
}