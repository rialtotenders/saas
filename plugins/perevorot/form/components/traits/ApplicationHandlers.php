<?php

namespace Perevorot\Form\Components\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Perevorot\Rialtotender\Classes\ValidationMessages;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\Payment;
use Perevorot\Rialtotender\Models\Tariff;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Facades\Auth;
use Perevorot\Form\Classes\Api;
use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;
use Perevorot\Users\Models\User;

/**
 * Class ApplicationHandlers
 * @package Perevorot\Form\Traits
 */
trait ApplicationHandlers
{
    use CustomValidatorMessages;

    public function accessToBid()
    {
        if(Carbon::now()->diffInMinutes(Carbon::createFromTimestamp(strtotime($this->tender->tenderPeriod->endDate))) <= 5)
        {
            $api = new Api($this->gos_tender);

            return $api->getTender($this->tender);
        }

        return true;
    }

    /**
     * @return array
     */
    public function onSubmitSingleLotApplication()
    {
        if($this->checkExistsApplication()){
            return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId());
        }

        if($this->tender->status != 'active.tendering' || !$this->accessToBid())
        {
            return [
                '#application-access-error' => $this->renderPartial('@messages/application_access_error')
            ];
        }

        if(!empty(post('features'))) {
            $_price_key = 'feature_price';
        } else {
            $_price_key = 'price';
        }

        $validator = Validator::make(post(), [
            $_price_key => 'required|numeric|between:0.1,'.$this->tender->value->amount
        ], ValidationMessages::generateCustomMessages($this->customMessages, 'application'));

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if($this->tender->value->amount >= 999999999) {
            $tariff = Tariff::getTariff(['is_gov' => stripos($this->tender->tenderID, 'R-') === FALSE, 'price' => post('price'), 'currency' => $this->tender->value->currency]);

            if (!$this->user->checkMoney($tariff)) {
                return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId());
            }
        }

        $form = new Application();

        $form->price = post('price');
        $form->tender_id = post('tender_id');
        $form->user_id = Auth::getUser()->id;
        $form->created_at = Carbon::create();
        $form->is_test = (int) Auth::getUser()->is_test;
        $form->is_gov = $this->gos_tender;
        $form->feature_price = !empty(post('feature_price')) ? post('feature_price') : null;
        $form->tender_features = !empty(post('features')) ? json_encode(post('features')) : null;

        /*
        if(!empty(post('feature_price'))){
            $features=[];

            foreach(post('feature_price') as $k=>$price){
                $features[]=[
                    'code'=>post('feature_code')[$k],
                    'value'=>$price
                ];
            }

            $form->features=json_encode($features);
        }
         *
         */

        if(!empty($this->tender->lots) && sizeof($this->tender->lots)==1){
            $form->lot_id=head($this->tender->lots)->id;
        }

        $api = new Api($this->gos_tender);
        $this->saveDocType();

        if($api->bidSingleLot($form, $this->tender)){
            $form->save(null, post('_session_key', $this->sessionKey));
            $form->user->pay($this->tender, Payment::PAYMENT_TYPE_CREATED_BID, $form);

            Event::fire('perevorot.form.bid', [
                'tender' => $this->tender,
                'form' => $form,
                'type' => 'created',
            ], true);

            return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId() . "/application/update?step=2");
        }else{
            return [
                '#application-access-error'=>$this->renderPartial('@messages/application_access_error')
            ];
        }

        return [
            '#application' => $this->renderPartial('applicationcreater/single/success.htm', $this->getDefaultParameters()),
        ];
    }

    /**
     * @return array
     */
    public function onSubmitMultiLotApplication()
    {
        if($this->tender->status != 'active.tendering' || !$this->accessToBid())
        {
            return [
                '#application-access-error' => $this->renderPartial('@messages/application_access_error')
            ];
        }

        $this->processStepFactory(
            $this->getCurrentStep()
        );

        return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId() . '/application');
    }

    /**
     * @return void
     */
    public function onRefreshFiles()
    {
        $this->pageCycle();
    }

    /**
     * @return mixed
     */
    public function onReturnBack()
    {

        if($this->getStepNumber() == 3 && $this->getLotStep() > 0) {
            $this->setLotStep(
                $this->getLotStep() - 1
            );
        } else {

            if($this->getStepNumber() == 4) {
                $this->setLotStep(
                    $this->getLotStep() - 1
                );
            }

            $this->setStepNumber(
                $this->getStepNumber() - 1
            );
        }

        return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId() . '/application');
    }
}
