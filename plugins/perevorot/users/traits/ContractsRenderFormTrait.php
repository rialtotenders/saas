<?php

namespace Perevorot\Users\Traits;

use Illuminate\Support\Facades\Session;
use Perevorot\Rialtotender\Exceptions\InvalidUserFromSession;
use Perevorot\Rialtotender\Models\Classifier;
use Perevorot\Rialtotender\Models\Currency;
use Perevorot\Rialtotender\Models\FormMessage;
use Perevorot\Rialtotender\Models\Procurement;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Models\Contract;
use Perevorot\Users\Components\RegistrationForm;
use Perevorot\Users\Components\Contracts;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Models\Message;
use Perevorot\Users\Models\MessageContract;
use Perevorot\Users\Models\User;
use Perevorot\Page\Models\Page;
use App;
use Psy\Exception\ErrorException;
use Config;

trait ContractsRenderFormTrait
{
    /**
     * @var ContractMessage $messages
     */
    private $messages;

    /**
     * @param $step
     * @return bool|void
     * @throws InvalidUserFromSession
     */
    private function renderStepFactory($step)
    {
        $this->contract = $this->getContract();
        $this->messages = MessageContract::instance();

        if (!($this->contract instanceof Contract)) {
            if($step == 1) {
                $this->contract = new Contract();
            } else {
                $this->clearSession();
                return redirect()->to($this->siteLocale . 'contract/search#contracts');
            }
        }

        \IntegerLog::info('contract.edit.step'.$step);

        switch ($step) {
            case 1:
                return $this->renderStepOne();

            case 2:
                return $this->renderStepTwo();

            case 3:
                return $this->renderStepThree();

            case 4:
                return $this->renderStepFour();

            default:
                return false;
        }
    }

    /**
     * @return string
     */
    private function renderStepOne()
    {
        Session::put('contract.session', 1);

        return [
            'template' => Contracts::STEP_ONE_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(1),
                'header' => $this->getHeader(1),
                'contract_json'  => $this->contract->getJson(),
                'contract'  => $this->contract,
                'currencies' => Currency::all(),
                'rationaletypes' => Procurement::getData('rationaletypes'),
            ]
        ];
    }

    /**
     * @return string
     */
    private function renderStepTwo()
    {
        Session::put('contract.session', 2);

        return [
            'template' => Contracts::STEP_TWO_TEMPLATE,
            'params'   => [
                'session_key_field' => $this->sessionKey,
                'text'  => $this->getText(2),
                'header' => $this->getHeader(2),
                'contract' => $this->contract,
            ]
        ];
    }

    /**
     * @return string
     */
    private function renderStepThree()
    {
        Session::put('contract.session', 3);

        return [
            'template' => Contracts::STEP_THREE_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(3),
                'header' => $this->getHeader(3),
                'contract_json'  => $this->contract->getJson(),
                'contract' => $this->contract,
                'edit' => Session::get('contract.update'),
                'measurers' => Classifier::getMeasurers(),
            ]
        ];
    }

    /**
     * @return string
     */
    private function renderStepFour()
    {
        Session::put('contract.session', 4);

        return [
            'template' => Contracts::STEP_FOUR_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(4),
                'header' => $this->getHeader(4),
                'contract_json'  => $this->contract->getJson(),
                'contract' => $this->contract,
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
