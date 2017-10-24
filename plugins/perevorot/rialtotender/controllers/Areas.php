<?php namespace Perevorot\Rialtotender\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Areas extends Controller
{
    public $requiredPermissions = [
        'rialtotender.inform.areas_permisssion'
    ];

    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Perevorot.Rialtotender', 'rialtotender', 'areas');
    }
}