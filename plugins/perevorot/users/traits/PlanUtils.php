<?php

namespace Perevorot\Users\Traits;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Perevorot\Rialtotender\Exceptions\InvalidUserFromSession;
use Perevorot\Rialtotender\Models\Plan;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Models\User;

/**
 * Class RegistrationFormUtils
 * @package Perevorot\Users\Traits
 */
trait PlanUtils
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
        $params['CurrentLocale'] = $this->getCurrentLocaleWithoutSlash();
        $params['siteLocale'] = $this->siteLocale;

        return $this->renderPartial($template, $params);
    }

    /**
     * @return bool
     */
    private function getPlan()
    {
        $tender_id = Session::get('plan.id');

        if (!$tender_id && (null === Session::get('plan.session') || Session::get('plan.session') == 1)) {
            return new Plan();
        }
        elseif (!$tender_id)
        {
            return false;
        }

        $tender = Plan::getData(['id' => $tender_id, 'test' => $this->user->is_test, 'user_id' => $this->user->id, 'limit' => 1]);

        if(!isset($tender->id))
        {
            return false;
        }

        return $tender;
    }

    /**
     * @return \Illuminate\Routing\Route|mixed
     */
    private function getPostData()
    {
        $post = post();

        unset($post['step']);
        unset($post['save']);

        return $post;
    }
}
