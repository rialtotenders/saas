<?php

namespace Perevorot\Users\Traits;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use October\Rain\Database\ModelException;
use October\Rain\Exception\ValidationException;
use Perevorot\Form\Classes\Api;
use Perevorot\Rialtotender\Exceptions\InvalidUserFromSession;
use Perevorot\Rialtotender\Models\FormMessage;
use Perevorot\Rialtotender\Models\Contract;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Users\Classes\ContractValidationFactory as ValidationFactory;
use Perevorot\Users\Classes\Validators\ValidatorInterface;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Models\Message;
use Perevorot\Users\Models\MessageContract;
use Perevorot\Users\Models\User;
use Perevorot\Users\Models\UserGroup;

trait ContractsTrait
{
    use ContractsRenderFormTrait;

    private $contract;

    /**
     * @param $step
     * @param bool $user
     * @return bool|string
     * @throws InvalidUserFromSession
     */
    private function processStepFactory($step, $contract = true)
    {
        $this->contract = $this->getContract();
        $this->messages = MessageContract::instance();

        if (!($this->contract instanceof Contract))
        {
            if($step == 1) {
                $this->contract = new Contract();
            } else {
                $this->clearSession();
                return redirect()->to($this->siteLocale.'tender/search#contracts');
            }
        }

        switch ($step) {
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

    /**
     * @return string
     * @throws ValidationException
     */
    private function processStepOne()
    {
        $data = $this->getPostData();

        /** @var ValidatorInterface $validator */
        $validator = ValidationFactory::make($data, 1);
        $validator->validate();

        $this->contract->processFields($data);

        if(isset($data['title'])) {
            $this->contract->title = $data['title'];
        }

        $this->contract->amount = $data['value']['amount'];
        $this->contract->save();

        Session::put('contract.id', $this->contract->id);

        return $this->renderStepTwo();
    }

    /**
     * @return string
     */
    private function processStepTwo()
    {
        $this->contract->save(null, post('_session_key'));

        return $this->renderStepThree();
    }

    /**
     * @return string
     */
    private function processStepThree()
    {
        $data = $this->getPostData();

        /** @var ValidatorInterface $validator */
        $validator = ValidationFactory::make($data, 3);
        $validator->validate();

        $this->contract->processFields($data);
        $this->contract->save();

        return $this->renderStepFour();
    }


    /**
     * @return string
     */
    private function processStepFour()
    {
        $api = new Api();

        $params = ['tender_system_id' => $this->contract->tender_id, 'test' => Auth::getUser()->is_test, 'user_id' => Auth::getUser()->id, 'limit' => 1];
        $tender = Tender::getData($params);

        if($api->submitActiveContract($tender, $this->contract, post('save')))
        {
            return true;
        }

        return false;
    }
}
