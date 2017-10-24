<?php namespace Perevorot\Blog\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Perevorot\Blog\Models\Blog;
use RainLab\Translate\Models\Locale;
use Backend\Widgets\Form;

class Blogs extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = [
        'perevorot.blog.manage_blogs'
    ];

    public $bodyClass;
    public $menu;

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Perevorot.Blog', 'main-menu-item');
    }
}