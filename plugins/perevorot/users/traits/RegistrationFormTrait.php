<?php

namespace Perevorot\Users\Traits;

use Backend;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use October\Rain\Database\ModelException;
use October\Rain\Exception\ValidationException;
use Perevorot\Rialtotender\Classes\ValidationMessages;
use Perevorot\Rialtotender\Exceptions\InvalidUserFromSession;
use Perevorot\Rialtotender\Models\FormMessage;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Users\Classes\ValidationFactory;
use Perevorot\Users\Classes\Validators\ValidatorInterface;
use Perevorot\Users\Models\Message;
use Perevorot\Users\Models\User;
use Perevorot\Users\Models\UserGroup;
use RainLab\User\Models\Settings;

trait RegistrationFormTrait
{
    use RegistrationRenderFormTrait;

    /**
     * @var User $user
     */
    private $user;

    /**
     * @param $step
     * @param bool $user
     * @return bool|string
     * @throws InvalidUserFromSession
     */
    private function processStepFactory($step, $user = true)
    {
        $this->user = $this->getUser();
        $this->messages = Message::instance();

        if (!($this->user instanceof User) && $user) {
            throw new InvalidUserFromSession();
        }

        \IntegerLog::info('user.register.step'.$step);

        switch ($step) {
            case 0:
                return $this->processStepZero();

            case 1:
                return $this->processStepOne();

            case 2:
                return $this->processStepTwo();

            case 3:
                return $this->processStepThree();

            case 4:
                return $this->processStepFour();

            default:
                return false;
        }
    }

    private function processStepZero()
    {
        $userset = Settings::instance();

        //$salt = rand(100, 3000);
        $password = str_random(6);

        $user = new User();
        $user->username = post('username');
        $user->email = post('email');
        $user->password = $password;
        $user->is_test = (int)$userset->user_in_test;
        //$user->send_invite = true;

        ValidationMessages::generateCustomMessages($user, 'registration');

        $user->validate();

        $user->save();

        $this->setProperty('paramCode', 'code');

        //$this->sendActivationEmail($user);

        $code = implode('!', [$user->id, $user->getActivationCode()]);
        $link = Request::root() . '/activate?code=' . $code;

        $data = [
            'link' => $link,
            'login' => $user->username,
            'password' => $password,
        ];

        if($userset->activate_mode != 'admin') {
            Mail::send('perevorot.rialtotender::'.$this->getLocaleForEmail().'mail.created_account', $data, function ($message) use ($user) {
                $message->to($user->email, $user->username);
            });
        }

        Session::put('registration.user_id', $user->id);
        Session::put('registration.session', 1);

        return Redirect::to($this->siteLocale.'signup');
    }

    /**
     * @return string
     * @throws ValidationException
     */
    private function processStepOne()
    {
        $data = $this->getPostData();
        $user = $this->getUser();

        /** @var ValidatorInterface $validator */
        $validator = ValidationFactory::make($data, 1);
        $validator->validate();

        $user->processFields($data);
        $user->company_name = $data['company_name'];
        $user->save();

        return $this->renderStepTwo();
    }

    /**
     * @return string
     */
    private function processStepTwo()
    {
        $data = $this->getPostData();

        $user = $this->getUser();
        $this->processRoles($user, $data);

        /** @var ValidatorInterface $validator */
        $validator = ValidationFactory::make($data, 2);
        $validator->validate();

        unset($data['roles']);

        $user->processFields($data);

        $user->save();

        return $this->renderStepThree();
    }

    /**
     * @return string
     */
    private function processStepThree()
    {
        $data = $this->getPostData();
        $user = $this->getUser();

        /** @var ValidatorInterface $validator */
        $validator = ValidationFactory::make($data, 3);
        $validator->validate();

        $user->processFields($data);

        $user->save();

        $userset = Settings::instance();

        if($userset->admin_email && $userset->admin_notify_template) {
            $_data['url'] = Backend::url('perevorot/users/users/preview/'.$user->id);
            $_data['activity'] = $user->activity;

            $userset->admin_notify_template = str_replace("::", ("::".$this->getLocaleForEmail()), $userset->admin_notify_template);

            Mail::send($userset->admin_notify_template, $_data, function ($message) use ($user, $userset) {
                $message->to($userset->admin_email, 'admin');
            });
        }

        if($userset->notify_template) {

            $data = [];

            if($userset->activate_mode == 'admin') {
                $code = implode('!', [$user->id, $user->getActivationCode()]);
                $link = Request::root() . '/activate?code=' . $code;
                $password = str_random(6);

                $user->password = $password;
                $user->save();

                $data = [
                    'link' => $link,
                    'login' => $user->username,
                    'password' => $password,
                ];
            }

            $userset->notify_template = str_replace("::", ("::".$this->getLocaleForEmail()), $userset->notify_template);

            Mail::send($userset->notify_template, $data, function ($message) use ($user) {
                $message->to($user->email, $user->username);
            });
        }

        $setting = Setting::instance();

        if($setting->get_value('show_offerta')) {
            return $this->renderStepFour();
        }

        return $this->renderStepFive();
    }

    private function processStepFour()
    {
        if(!post('is_do')) {
            return $this->renderStepFour();
        }

        $data = $this->getPostData();
        $user = $this->getUser();

        $user->processFields($data);
        $user->save();

        return $this->renderStepFive();
    }

    /**
     * @param $user
     * @param $data
     */
    private function processRoles($user, $data)
    {
        $ids = [];

        if (!array_key_exists('roles', $data)) {
            $user->groups()->sync([]);

            return;
        }

        foreach ($data['roles'] as $key => $role) {
            $group = UserGroup::where('code', $role)->first();

            if (!$group) {
                continue;
            }

            $ids[] = $group->id;
        }

        $user->groups()->sync($ids);
    }
}
