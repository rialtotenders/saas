<?php

namespace Perevorot\Users\Traits;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Perevorot\Rialtotender\Exceptions\InvalidUserFromSession;
use Perevorot\Rialtotender\Models\Contract;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Models\User;


trait ContractsUtils
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
    private function getContract()
    {
        $contract_id = Session::get('contract.id');

        if (!$contract_id)
        {
            return false;
        }

        $contract = Contract::getData(['id' => $contract_id, 'test' => Auth::getUser()->is_test, 'user_id' => Auth::getUser()->id, 'limit' => 1]);

        if(!isset($contract->id))
        {
            $this->clearSession();
            return false;
        }

        return $contract;
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

    public function clearSession() {
        Session::remove('contract.session');
        Session::remove('contract.id');
        Session::remove('contract.update');

        return true;
    }
}
