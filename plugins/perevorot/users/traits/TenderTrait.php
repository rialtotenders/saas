<?php

namespace Perevorot\Users\Traits;

use Illuminate\Support\Facades\Event;
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
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Users\Classes\TenderValidationFactory as ValidationFactory;
use Perevorot\Users\Classes\Validators\ValidatorInterface;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Models\Message;
use Perevorot\Users\Models\MessageTender;
use Perevorot\Users\Models\User;
use Perevorot\Users\Models\UserGroup;
use System\Models\File;

trait TenderTrait
{
    private $tender;
    
    use TenderRenderFormTrait;

    /**
     * @param $step
     * @param bool $user
     * @return bool|string
     * @throws InvalidUserFromSession
     */

    private function processStepFactory($step, $tender = true)
    {
        $this->tender = $this->getTender();
        $this->messages = MessageTender::instance();

        if (!($this->tender instanceof Tender))
        {
            if($step == 1) {
                $this->tender = new Tender();
            } else {
                $this->clearSession();
                return redirect()->to($this->siteLocale.'tender/search#'.$this->is_gov.'tenders');
            }
        }

        switch ($step) {
            case 1:
                return $this->processStepOne();

            case 8:
                return $this->processStepOneA();

            case 2:
                return $this->processStepTwo();

            case 3:
                return $this->processStepThree();

            case 4:
                return $this->processStepFour();

            case 5:
                return $this->processStepFive();

            case 6:
                return $this->processStepSix();

            case 7:
                return $this->processStepSeven();

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
        $validator = ValidationFactory::make($data, 1, $this->_form_validation_field);
        $validate = $validator->validate();

        if($this->_form_validation) {
            return $validate;
        }

        $this->tender->processFields($data);
        $this->tender->title = $data['title'];

        $_tender = $this->tender->getJson();

        if(isset($_tender->features) && !isset($data['criteria'])) {
            unset($_tender->features);
            $this->tender->json = json_encode($_tender);
        }

        $this->tender->save();

        if(!$this->param('tenderID')) {
            $this->tender->clearDocuments();
        }

        Session::put('tender.id', $this->tender->id);

        if(isset($data['criteria']) && $data['criteria']) {
            return $this->renderStepOneA();
        } else {
            return $this->renderStepTwo();
        }
    }

    /**
     * @return string
     * @throws ValidationException
     */
    private function processStepOneA()
    {
        $data = $this->getPostData();

        /** @var ValidatorInterface $validator */
        $validator = ValidationFactory::make($data, 8, $this->_form_validation_field);
        $validate = $validator->validate();

        if($this->_form_validation) {
            return $validate;
        }

        if(!empty($data)) {
            $this->tender->processFields($data);
            $this->tender->save();
        } else {
            $_tender = $this->tender->getJson();

            if(isset($_tender->features)) {
                unset($_tender->features);
            }

            $this->tender->json = json_encode($_tender);
            $this->tender->save();
        }

        return $this->renderStepTwo();
    }

    /**
     * @return string
     */
    private function processStepFive()
    {
        /*
        $data = $this->getPostData();

        $validator = ValidationFactory::make($data, 2);
        $validator->validate();

        $this->tender->processFields($data);
        */

        //$data = $this->getPostData();

        $this->tender->step = $this->tender->step < post('step') ? post('step') : $this->tender->step;
        $this->tender->save(null, post('_session_key'));
        $this->saveDocType();
        //$this->tender->clearChangeDocuments();

        return $this->renderStepSix();
    }

    public function saveDocType() {

        if(!empty(post('doc_type'))) {
            foreach(post('doc_type') AS $id => $doc_type) {
                if($file = File::find($id)) {
                    $file->doc_cdb_type = $doc_type;
                    $file->save();
                }
            }
        }

        return true;
    }

    /**
     * @return string
     */
    private function processStepTwo()
    {
        $data = $this->getPostData();

        /** @var ValidatorInterface $validator */
        $validator = ValidationFactory::make($data, 3, $this->_form_validation_field);
        $validate = $validator->validate();

        if($this->_form_validation) {
            return $validate;
        }

        $this->tender->processFields($data);
        $this->tender->currency = $data['value']['currency'];
        $this->tender->value = isset($data['value']['amount']) ? $data['value']['amount'] : 0;
        $this->tender->save();

        return $this->renderStepThree();
    }

    /**
     * @return string
     */
    private function processStepThree()
    {
        $data = $this->getPostData();

        /** @var ValidatorInterface $validator */
        $validator = ValidationFactory::make($data, 4, $this->_form_validation_field);
        $validate = $validator->validate();

        if($this->_form_validation) {
            return $validate;
        }

        $this->tender->processFields($data);
        $this->tender->save();

        return $this->renderStepFour();
    }

    /**
     * @return string
     */
    private function processStepFour()
    {
        $data = $this->getPostData();

        /** @var ValidatorInterface $validator */
        $validator = ValidationFactory::make($data, 5, $this->_form_validation_field);
        $validate = $validator->validate();

        if($this->_form_validation) {
            return $validate;
        }

        $this->tender->save(null, post('_session_key'));
        $this->saveDocType();
        $this->tender->processFields($data);
        $_tender = $this->tender->getJson();

        if($_tender->lot && isset($_tender->lots)) {
            $this->tender->value = array_sum(array_map(function($lot) {
                return isset($lot->value->amount) ? $lot->value->amount : 0;
            }, (array)$_tender->lots));
        } elseif(!$_tender->lot && isset($_tender->lots)) {
            unset($_tender->lots);
            $this->tender->json = json_encode($_tender);
        }

        $this->tender->save();

        return $this->renderStepFive();
    }

    /**
     * @return string
     */
    private function processStepSix()
    {
        $data = $this->getPostData();

        /** @var ValidatorInterface $validator */
        $validator = ValidationFactory::make($data, 6, $this->_form_validation_field);
        $validate = $validator->validate();

        if($this->_form_validation) {
            return $validate;
        }

        $this->tender->processFields($data);
        $this->tender->save();

        return $this->renderStepSeven();
    }

    /**
     * @return string
     */
    private function processStepSeven()
    {
        $_tender_files = $_lot_files = [];

        foreach($this->tender->documents AS $k => $doc) {
            if($doc->lot_id !== null) {
                $_lot_files[$doc->lot_id][] = $doc;
            } else {
                $_tender_files[] = $doc;
            }
        }

        $api = new Api();
        $is_complete = $this->tender->is_complete;

        if($api->createTender($this->tender, Session::get('tender.update'), $_tender_files, $_lot_files))
        {
            if(!$is_complete && $this->tender->is_complete) {
                Event::fire('perevorot.users.tender', [
                    'tender' => $this->tender,
                    'type' => 'created',
                ], true);
            } else {
                Event::fire('perevorot.users.tender', [
                    'tender' => $this->tender,
                    'type' => 'updated',
                ], true);
            }

            $this->clearSession(false);
            return true;
        }

        return false;
    }
}
