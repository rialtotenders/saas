<?php

namespace Perevorot\Form\Components\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redirect;
use October\Rain\Database\Collection;
use October\Rain\Support\Facades\Flash;
use Perevorot\Form\Classes\ApplicationFactory;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\Payment;
use Perevorot\Rialtotender\Models\Tariff;
use Perevorot\Users\Facades\Auth;
use Perevorot\Form\Classes\Api;
use Perevorot\Users\Models\User;
use System\Models\File;
use Perevorot\Rialtotender\Classes\ValidationMessages;
use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;

trait ApplicationStepFactory
{
    use ApplicationRenderStepFactory, CustomValidatorMessages;

    /**
     * @param $step
     * @return array|void
     */
    public function processStepFactory($step)
    {
        switch ($step) {
            case ApplicationFactory::FIRST_STEP:
                return $this->processFirstStep();

            case ApplicationFactory::SECOND_STEP:
                return $this->processSecondStep();

            case ApplicationFactory::LOT_STEP:
                return $this->processLotStep();

            case ApplicationFactory::LAST_STEP:
                return $this->processLastStep();

        }
    }

    /**
     * @return array
     */
    private function processFirstStep($last_step = false)
    {
        $applicationsFromSession = [];

        /** @var array $lots */
        $lots = array_filter(post('lots'), function ($item) {
            return isset($item['price']) && $item['price'] != '' && !empty($item['lot_id']);
        });

        if(empty($lots)) {
            Flash::error(true);
        }

        $_rules = [];

        foreach ($lots as $lkey => $lot) {
            $_key = 'lots.'.$lkey.'.'.(isset($lot['features']) ? 'feature_price' : 'price');
            $_rules[$_key] = 'numeric|between:0.1,'.$lot['lot_price'];
            $_customMessages[$_key.'.numeric'] = '';
            $_customMessages[$_key.'.between'] = '';
        }

        $validator = Validator::make(post(), $_rules, ValidationMessages::generateCustomMessages($_customMessages, 'application', true));

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if($this->tender->value->amount >= 999999999) {
            foreach($lots AS $item) {
                $tariff = Tariff::getTariff(['is_gov' => stripos($this->tender->tenderID, 'R-') === FALSE, 'price' => $item['price'], 'currency' => $this->tender->value->currency]);

                if (!$this->user->checkMoney($tariff)) {
                    return Redirect::to($this->siteLocale . 'tender/' . $this->getTenderId());
                }
            }
        }

        if ($this->isApplicationCreated()) {
            $applicationsFromSession = $this->getApplications();

            /** @var array $ids */

            $ids = [];

            foreach ($applicationsFromSession as $item) {
                $ids[] = $item->application_id;
            }

            $applications = Application::findMany($ids)->keyBy('lot_id');
            //$applications = Application::where('is_test', Auth::getUser()->is_test)->where('user_id', Auth::getUser()->id)->where('tender_id', $this->getSystemTenderId())->get()->keyBy('lot_id');
            $applicationsFromSession = [];

            foreach ($lots as $lkey => $lot) {
                $lot_id = $lot['lot_id'];

                if (!$applications->has($lot_id)) {
                    $applicationsFromSession[] = $this->createApplication($lot);
                    continue;
                }

                /** @var Application $application */
                $application = $applications[$lot_id];
                $application->price = $lot['price'];
                $application->feature_price = isset($lot['feature_price']) ? $lot['feature_price'] : null;
                $application->lot_features = isset($lot['features']) ? json_encode($lot['features']) : null;
                $application->tender_features = !empty(post('features')) ? json_encode(post('features')) : null;
                $application->save();

                $applicationsFromSession[] = [
                    'application_id' => $application->id,
                    'lot_id' => $lot_id,
                    'lot_features' => isset($lot['features']) ? $lot['features'] : [],
                    'tender_features' => !empty(post('features')) ? post('features') : [],
                ];

                unset($applications[$lot_id]);
            }

            if (sizeof($applications) > 0) {
                foreach ($applications as $item) {
                    $item->delete();
                }
            }
        } else {
            foreach ($lots as $item) {
                $applicationsFromSession[] = $this->createApplication($item);
            }

            $this->setApplicationCreatedStatus(true);
        }

        $this->setApplications($applicationsFromSession);

        if($last_step) {
            return true;
        }

        $this->setStepNumber(2);
        //$application = array_shift($applicationsFromSession);

        return $this->processRenderSecondStep();
    }

    private function processSecondStep()
    {
        $applications = $this->getApplications();

        foreach($applications AS $_app) {
            $app = Application::find($_app->application_id);
            $app->save(null, post('_session_key'));
            break;
        }

        $this->saveDocType();

        //$this->setApplications($applications);

        //$memory = $this->getMemoryApplications();
        //$memory[] = $first_application;

        //$this->setMemoryApplications($memory);

        $this->setStepNumber(3);

        return $this->processRenderLotStep();

        /*
        if (sizeof($applications) > 0) {
            $application = array_shift($applications);

            return $this->processRenderThirdStep(
                $application
            );
        } else {
            return $this->processFinishStep();
        }*/
    }

    private function processLotStep()
    {
        $applications = $this->getApplications();

        foreach($applications AS $_app) {
            $app = Application::find($_app->application_id);
            $app->save(null, post('_session_key'));
            break;
        }

        $this->saveDocType();
        $this->setLotStep(((int)$this->getLotStep())+1);

        return $this->processRenderLotStep();
    }

    /**
     * @return array
     */
    private function processLastStep()
    {
        $applications = $this->getApplications();
        $app = Application::find($applications[0]->application_id);

        $app->save(null, post('_session_key'));
        $this->processFirstStep();

        $api = new Api($this->gos_tender);
        $_files = [];

        $this->saveDocType();

        foreach($app->documents AS $doc) {
            if($doc->lot_id) {
                $_files[$doc->lot_id][] = $doc;
            } else {
                $_files[] = $doc;
            }
        }

        if ($api->bidToMultiLot($applications, $this->tender, $_files)) {
            foreach($applications as $__app) {
                $_app = Application::find($__app->application_id);
                $_app->user->pay($this->tender, Payment::PAYMENT_TYPE_CREATED_BID, $_app);
            }

            Event::fire('perevorot.form.created_bid', [
                'tender' => $this->tender,
                'user' => $app,
                'type' => 'created',
            ], true);
        } else {
            return redirect()->back();
        }

        $this->setStepNumber(
            0
        );

        return redirect()->to($this->siteLocale . 'tender/' . $this->getTenderId());
        //return $this->processRenderLotStep();
    }

}
