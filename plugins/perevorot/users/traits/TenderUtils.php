<?php

namespace Perevorot\Users\Traits;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use October\Rain\Filesystem\Definitions;
use Perevorot\Rialtotender\Exceptions\InvalidUserFromSession;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Models\User;

/**
 * Class RegistrationFormUtils
 * @package Perevorot\Users\Traits
 */
trait TenderUtils
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
        $params['show_features'] = $this->setting->checkAccess('features');
        $params['show_lots'] = $this->setting->checkAccess('lots');
        $params['file_types'] = $this->processFileTypes($this->setting->get_value('file_types'));
        $params['_update'] = Session::get('tender.update') ? Session::get('tender.id') : '';

        return $this->renderPartial($template, $params);
    }

    public function processFileTypes($types, $includeDot = true)
    {
        $types = $types;

        if (!$types || $types == '*') {
            $types = implode(',', Definitions::get('defaultExtensions'));
        }

        if (!is_array($types)) {
            $types = explode(',', $types);
        }

        $types = array_map(function($value) use ($includeDot) {
            $value = trim($value);

            if (substr($value, 0, 1) == '.') {
                $value = substr($value, 1);
            }

            if ($includeDot) {
                $value = '.'.$value;
            }

            return $value;
        }, $types);

        return implode(',', $types);
    }

    /**
     * @return bool
     */
    private function getTender()
    {
        $tender_id = Session::get('tender.id');

        if (!$tender_id)
        {
            return false;
        }

        $tender = Tender::getData(['id' => $tender_id, 'gov' => $this->user->is_gov, 'test' => $this->user->is_test, 'user_id' => $this->user->id, 'limit' => 1]);

        if(!isset($tender->id))
        {
            $this->clearSession();
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

        unset($post['next_step']);
        unset($post['save']);
        unset($post['document_type']);
        unset($post['_validation']);
        if(isset($post['doc_type'])) unset($post['doc_type']);

        return $post;
    }

    private function clearSession($clear_docs = true) {
        Session::remove('tender.session');
        Session::remove('tender.id');
        Session::remove('tender.update');
        Session::remove('tender.lot_docs');

        if($clear_docs) {
            $tender = $this->getTender();

            if ($tender instanceof Tender) {
                $tender->clearDocuments();
            }
        }

        return true;
    }
}
