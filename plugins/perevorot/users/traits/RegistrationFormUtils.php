<?php

namespace Perevorot\Users\Traits;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Perevorot\Rialtotender\Exceptions\InvalidUserFromSession;
use Perevorot\Users\Models\User;

/**
 * Class RegistrationFormUtils
 * @package Perevorot\Users\Traits
 */
trait RegistrationFormUtils
{
    /**
     * Рендеринг шаблона в зависимости от выбраного шага
     *
     * @param $step
     * @return mixed
     * @throws \Exception
     */
    private function renderTemplateByStep($step)
    {
        try {
            $result = $this->renderStepFactory($step);
        } catch (InvalidUserFromSession $ex) {
            return Redirect::to($this->siteLocale);
        }

        if (!array_key_exists('template', $result)) {
            throw new \Exception('Поле `template` должно быть об`явлено');
        }

        $template = $result['template'];
        $params = (array_key_exists('params', $result)) ? $result['params'] : [];

        return $this->renderPartial($template, $params);
    }

    /**
     * @return bool
     */
    private function getUser()
    {
        $user_id = Session::get('registration.user_id');

        if (!$user_id) {
            return false;
        }

        return User::find($user_id);
    }

    /**
     * @return \Illuminate\Routing\Route|mixed
     */
    private function getPostData()
    {
        $post = post();

        unset($post['step']);

        return $post;
    }

    /**
     * @param $user
     */
    protected function sendActivationEmail($user)
    {
        $code = implode('!', [$user->id, $user->getActivationCode()]);
        $link = Request::root() . '/activate?code=' . $code;

        $data = [
            'name' => $user->name,
            'link' => $link,
            'code' => $code
        ];

        Mail::send('rainlab.user::mail.activate', $data, function($message) use ($user) {
            $message->to($user->email, $user->name);
        });
    }
}
