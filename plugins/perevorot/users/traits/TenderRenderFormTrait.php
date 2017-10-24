<?php

namespace Perevorot\Users\Traits;

use Illuminate\Support\Facades\Session;
use Perevorot\Rialtotender\Exceptions\InvalidUserFromSession;
use Perevorot\Rialtotender\Models\Classifier;
use Perevorot\Rialtotender\Models\Currency;
use Perevorot\Rialtotender\Models\FormMessage;
use Perevorot\Rialtotender\Models\Procurement;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Models\TenderFile;
use Perevorot\Users\Components\RegistrationForm;
use Perevorot\Users\Components\TenderCreate;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Models\Message;
use Perevorot\Users\Models\MessageTender;
use Perevorot\Users\Models\User;
use Perevorot\Page\Models\Page;
use App;
use Psy\Exception\ErrorException;
use Config;

/**
 * Class RegistrationRenderFormTrait
 * @package Perevorot\Users\Traits
 */
trait TenderRenderFormTrait
{
    /**
     * @var TenderMessage $messages
     */
    private $messages;

    /**
     * @param $step
     * @return bool|void
     * @throws InvalidUserFromSession
     */
    private function renderStepFactory($step)
    {
        $this->tender = $this->getTender();
        $this->messages = MessageTender::instance();

        if (!($this->tender instanceof Tender)) {
            if($step == 1) {
                $this->tender = new Tender();
            } else {
                $this->clearSession();
                return redirect()->to($this->siteLocale . 'tender/search#' . $this->is_gov . 'tenders');
            }
        }

        \IntegerLog::info('tender.add.step'.$step);

        switch ($step) {
            case 1:
                return $this->renderStepOne();

            case 8:
                return $this->renderStepOneA();

            case 2:
                return $this->renderStepTwo();

            case 3:
                return $this->renderStepThree();

            case 4:
                return $this->renderStepFour();

            case 5:
                return $this->renderStepFive();

            case 6:
                return $this->renderStepSix();

            case 7:
                return $this->renderStepSeven();

            default:
                return false;
        }
    }

    /**
     * @return string
     */
    private function renderStepOne()
    {
        Session::put('tender.session', 1);

        $method_type = Procurement::getData('_method_type');

        if(!$this->setting->checkAccess('tenderTwoStage')) {
            if(isset($method_type['aboveThresholdTS'])) {
                unset($method_type['aboveThresholdTS']);
            }
        }

        return [
            'template' => TenderCreate::STEP_ONE_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(1),
                'header' => $this->getHeader(1),
                'tender'  => $this->tender->getJson(),
                'tender_id' => $this->tender->id,
                'tender_step' => $this->tender->step,
                'procurement_types' => Procurement::getData('_type'),
                'procurement_method_types' => $method_type,
                'show_p_types' => $this->setting->checkAccess('procurementMethod'),
                'show_p_method_types' => $this->setting->checkAccess('procurementMethodType'),
                'show_p_method_types_default' => $this->setting->checkAccess('procurementMethodTypeDefault'),
            ]
        ];
    }

    private function renderStepOneA()
    {
        Session::put('tender.session', 8);

        return [
            'template' => TenderCreate::STEP_ONE_A_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(8),
                'header' => $this->getHeader(8),
                'tender'  => $this->tender->getJson(),
                'tender_id' => $this->tender->id,
                'tender_step' => $this->tender->step,
            ]
        ];
    }

    /**
     * @return string
     */
    private function renderStepFive()
    {
        Session::put('tender.session', 5);

        return [
            'template' => TenderCreate::STEP_FIVE_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(5),
                'header' => $this->getHeader(5),
                'tender'  => $this->tender->getJson(),
                'tender_id' => $this->tender->id,
                '_tender' => $this->tender,
                'tender_step' => $this->tender->step,
                'session_key_field' => $this->sessionKey,
                'tender_document_types' => Procurement::getData('tender_document_types'),
            ]
        ];
    }

    /**
     * @return string
     */
    private function renderStepTwo()
    {
        Session::put('tender.session', 2);

        return [
            'template' => TenderCreate::STEP_TWO_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(2),
                'header' => $this->getHeader(2),
                'tender'  => $this->tender->getJson(),
                'tender_id' => $this->tender->id,
                'tender_step' => $this->tender->step,
                'is_empty_price' => $this->tender->is_empty_price,
                'currencies' => Currency::all(),
                'show_guarantee' => $this->setting->checkAccess('guarantee'),
                'auction_step_from' => $this->setting->get_value('auction_step_from'),
                'auction_step_to' => $this->setting->get_value('auction_step_to'),
                'withEmptyPrice' => $this->setting->checkAccess('withEmptyPrice'),
                'choseTax' => $this->setting->checkAccess('choseTax'),
            ]
        ];
    }

    /**
     * @return string
     */
    private function renderStepThree()
    {
        Session::put('tender.session', 3);

        return [
            'template' => TenderCreate::STEP_THREE_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(3),
                'header' => $this->getHeader(3),
                'tender'  => $this->tender->getJson(),
                'tender_id' => $this->tender->id,
                'is_empty_price' => $this->tender->is_empty_price,
                'tender_step' => $this->tender->step,
                'weekend_dates' => implode("','", Config::get('weekend_dates')),
            ]
        ];
    }

    /**
     * @return string
     */
    private function renderStepFour()
    {
        Session::put('tender.session', 4);

        $json = $this->tender->getJson();

        if(isset($json->lots) && !is_array($json->lots)) {
            $json->lots = (array)$json->lots;
        }

        return [
            'template' => TenderCreate::STEP_FOUR_TEMPLATE,
            'params'   => [
                'user' => Auth::getUser(),
                'text'  => $this->getText(4),
                'header' => $this->getHeader(4),
                'tender_id' => $this->tender->id,
                'tender_step' => $this->tender->step,
                'tender_is_complete' => $this->tender->is_complete,
                'tender' => $json,
                '_tender' => $this->tender,
                'is_empty_price' => $this->tender->is_empty_price,
                'edit' => Session::get('tender.update'),
                'measurers' => Classifier::getMeasurers(),
                'show_guarantee' => $this->setting->checkAccess('guarantee'),
                'auction_step_from' => $this->setting->get_value('auction_step_from'),
                'auction_step_to' => $this->setting->get_value('auction_step_to'),
                'tender_document_types' => Procurement::getData('tender_document_types'),
                'withEmptyPrice' => $this->setting->checkAccess('withEmptyPrice'),
            ]
        ];
    }

    /**
     * @return string
     */
    private function renderStepSix()
    {
        Session::put('tender.session', 6);

        return [
            'template' => TenderCreate::STEP_SIX_TEMPLATE,
            'params'   => [
                'user' => Auth::getUser(),
                'edit' => Session::get('tender.update'),
                'text'  => $this->getText(6),
                'header' => $this->getHeader(6),
                'tender'  => $this->tender->getJson(),
                'tender_id' => $this->tender->id,
                'tender_step' => $this->tender->step,
            ]
        ];
    }

    /**
     * @return string
     */
    private function renderStepSeven()
    {
        Session::put('tender.session', 7);

        $json = $this->tender->getJson();

        $documents = [];

        if(count($this->tender->documents) > 0 ) {
	   
            $_change_documents = array_where($this->tender->tenderDocuments->all(), function($document, $key) {
                return $document->change_system_file_id > 0;
            });

            $documents = $this->tender->documents;

            if(!empty($_change_documents)) {
                foreach ($_change_documents AS $cd) {
                    foreach ($documents AS $dkey => $document) {
                        if ($cd->system_file_id == $document->id) {
                            unset($documents[$dkey]);
                            break;
                        }
                    }
                }
            }
        }

        if(isset($json->lots) && !is_array($json->lots)) {
            $json->lots = (array)$json->lots;
        }

        return [
            'template' => TenderCreate::STEP_SEVEN_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(7),
                'header' => $this->getHeader(7),
                'tender'  => $json,
                'documents'  => $documents,
                'tender_id' => $this->tender->id,
                'tender_step' => $this->tender->step,
            ]
        ];
    }

    /**
     * @param $step
     * @return mixed
     */
    private function getText($step)
    {
        return ($this->messages ? $this->messages->{'step' . $step} : '');
    }

    /**
     * @param $step
     * @return mixed
     */
    private function getHeader($step)
    {
        return ($this->messages ? $this->messages->{'header' . $step} : '');
    }
}
