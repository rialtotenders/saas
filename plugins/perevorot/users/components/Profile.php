<?php namespace Perevorot\Users\Components;

use Perevorot\Users\Models\User;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Users\Classes\ValidationFactory;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Traits\RegistrationFormTrait;
use Validator;
use ValidationException;
use Cms\Classes\ComponentBase;
use Perevorot\Users\Models\Message;

class Profile extends ComponentBase
{
    use RegistrationFormTrait;

    public function componentDetails()
    {
        return [
            'name'        => 'Profile Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRender()
    {
        $this->addJs('assets/js/registration-validation.js');
        $user = $this->getUser();
        $messages=Message::instance();
        $setting = Setting::instance();

        return $this->renderPartial('profile/_edit.htm', [
            'user' => $user,
            'messages' => $messages,
            'show_offerta' => $setting->checkAccess('show_offerta'),
            'edit_group' => $setting->checkAccess('user_edit_group'),
            'edit_gov' => $setting->checkAccess('user_edit_gov'),
            'showUserIsGo' => $setting->checkAccess('showUserIsGo'),
        ]);
    }

    public function getUser()
    {
        $user = Auth::getUser();

        if($user && !$user instanceof User) {
            $user = User::find($user->id);
        }

        if (!$user){
            throw new \Exception('Not authorized user');
        }

        return $user;
    }

    public function onSave()
    {
        \IntegerLog::info('user.profile.save');
        
        $data = post();
        $user = $this->getUser();

        $setting = Setting::instance();

        if($setting->checkAccess('user_edit_group')) {
            unset($data['roles']);
        } else {
            $this->processRoles($user, $data);
        }

        if(!isset($data['is_gov'])) {
            $data['is_gov'] = 0;
        }

        if(!isset($data['is_go'])) {
            $data['is_go'] = 0;
        }

        if(!isset($data['is_test'])) {
            $data['is_test'] = 0;
            $data['is_accelerator'] = 0;
        }

        if(!isset($data['is_accelerator'])) {
            $data['is_accelerator'] = 0;
        }

        if($setting->checkAccess('user_edit_gov')) {
            unset($data['is_gov']);
        }

        $validator = ValidationFactory::make($data, 4);
        $validator->validate();

        $user->company_name = $data['company_name'];
        $user->password = $data['password'];

        if(isset($data['roles'])) { unset($data['roles']); }
        unset($data['password']);

        $user->processFields(
            $data
        );

        $user->save();

        return [
            '#profile-change-status' => $this->renderPartial('profile/_success.htm')
        ];
    }
}
