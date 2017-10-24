<?php

namespace Perevorot\Users\Controllers;

use BackendMenu;
use Illuminate\Http\RedirectResponse;
use Perevorot\Users\Models\ExternalAuth;
use Perevorot\Rialtotender\Models\Setting;
use RainLab\User\Controllers\Users as BaseUsers;

/**
 * Users Back-end Controller
 */
class Users extends BaseUsers
{
    protected $jsonable = [
        'data',
    ];

    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.RelationController',
        'Backend\Behaviors\ListController',
        'Backend.Behaviors.ImportExportController',
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $relationConfig = 'config_relation.yaml';
    public $importExportConfig = 'config_import_export.yaml';
    public $externalSettings;
    public $settings;

    public $requiredPermissions = [
        'rialtotender.users.*'
    ];

    public function __construct()
    {
        $this->externalSettings=ExternalAuth::instance();
        $this->settings=Setting::instance();

        if($this->externalSettings->is_enabled){
            $this->formConfig='config_form_external.yaml';
        }

        parent::__construct();


        BackendMenu::setContext('Perevorot.Users', 'user', 'administrators');
    }

    public function update() {
        $response = $this->checkPermissions();

        if($response instanceof RedirectResponse) {
            return $response;
        } else {
            parent::update(current($this->params));
        }
    }

    public function create() {
        $response = $this->checkPermissions();

        if($response instanceof RedirectResponse) {
            return $response;
        } else {
            parent::create();
        }
    }

    public function checkPermissions() {

        if(!$this->user->is_superuser) {
            if ($this->action && $this->action != 'preview') {
                if (!$this->user->hasPermission('rialtotender.users.all_permissions')) {
                    return redirect()->to('/backend/perevorot/users/users/');
                }
            }
        }

        return $this->action;
    }

    public function formExtendFields($form)
    {
        if(!$this->settings->checkAccess('is_gov')){
            $form->removeField('is_gov');
        }
    }
}
