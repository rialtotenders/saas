<?php namespace Perevorot\Rialtotender\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Applications extends Controller
{
    public $requiredPermissions = [
        'rialtotender.inform.applications_permission'
    ];

    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Perevorot.Rialtotender', 'rialtotender', 'applications');
    }
}