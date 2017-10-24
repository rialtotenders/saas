<?php

namespace Perevorot\Form\Components\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use October\Rain\Database\Collection;
use October\Rain\Support\Facades\Form;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\Procurement;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Models\MessageApplication;
use App;
use System\Models\File;

/**
 * Class ApplicationStepUtils
 * @package Perevorot\Form\Traits
 */
trait ApplicationStepUtils
{
    /**
     * @param $lot_id
     * @return mixed
     */
    protected function findLotById($lot_id)
    {
        return array_first($this->tender->lots, function ($lot, $key) use ($lot_id) {
            return $lot->id == $lot_id;
        });
    }

    /**
     * @return bool
     */
    protected function isMultiLot()
    {
        return (isset($this->tender->lots)) ? sizeof($this->tender->lots) > 1 : false;
    }

    /**
     * @return mixed
     */
    protected function getTenderId()
    {
        return $this->tender->tenderID;
    }

    /**
     * @return mixed
     */
    protected function getSystemTenderId()
    {
        return $this->tender->id;
    }

    /**
     * @return string
     */
    protected function getCurrentStep()
    {
        if ($this->getStepNumber() == 1) {
            return 'first';
        }

        if ($this->getStepNumber() == 2) {
            return 'second';
        }

        if ($this->getStepNumber() == 3) {
            return 'lot';
        }

        if ($this->getStepNumber() == 4) {
            return 'last';
        }

        return false;
    }

    /**
     * @return mixed
     */
    protected function getCurrentLot()
    {
        return $this->findLotById(
            $this->getCurrentLotId()
        );
    }

    /**
     * @return \Illuminate\Routing\Route|mixed
     */
    protected function getCurrentLotId()
    {
        $defaultValue = null;

        if (($this->getStepNumber() > 0) && (!empty($this->getApplications()))) {
            $applications = $this->getApplications();
            $application = array_shift($applications);

            if ($application) {
                $defaultValue = $application->lot_id;
            }
        }

        return post('lot_id', $defaultValue);
    }

    /**
     * @return \Illuminate\Routing\Route|mixed
     */
    protected function getCurrentApplicationId()
    {
        $defaultValue = null;

        if (($this->getStepNumber() > 0) && (!empty($this->getApplications()))) {
            $applications = $this->getApplications();
            $application = array_shift($applications);

            if ($application) {
                $defaultValue = $application->application_id;
            }
        }

        return post('application_id', $defaultValue);
    }

    /**
     * @return array
     */
    protected function getDefaultParameters()
    {
        return [
            'is_auth' => (bool) $this->user,
            'session_key_field' => $this->sessionKey,
            'tender_id' => $this->param('tenderID'),
            'item' => $this->tender,
            'is_multilot' => $this->isMultiLot(),
            'messages' => $this->getMessages(),
            'siteLocale' => $this->siteLocale,
            'document_types' => $this->tender->procurementMethodType == 'aboveThresholdTS' ? Procurement::getData('bid_document_types') : [],
        ];
    }

    protected function getMessages()
    {
        return MessageApplication::instance();
    }

    /**
     * @param $tender
     * @return mixed
     */
    protected function getApplicationsByTender($tender)
    {
        /** @var Collection $applications */
        $applications = Application::where('tender_id', $tender->id)->get()->keyBy('lot_id');

        foreach ($tender->lots as $lot) {
            if (!$applications->has($lot->id)) {
                continue;
            }

            $applications[$lot->id]->lot = $lot;
        }

        return $applications;
    }

    /**
     * @param $lot
     * @return array
     */
    protected function createApplication($lot)
    {
        $form = new Application();

        $form->price = $lot['price'];
        $form->tender_id = $this->getSystemTenderId();
        $form->user_id = $this->user->id;
        $form->is_test = $this->user->is_test;
        $form->created_at = Carbon::create();
        $form->lot_id = $lot['lot_id'];
        $form->is_gov = $this->gos_tender;
        $form->feature_price = isset($lot['feature_price']) ? $lot['feature_price'] : null;
        $form->lot_features = isset($lot['features']) ? json_encode($lot['features']) : null;
        $form->tender_features = !empty(post('features')) ? json_encode(post('features')) : null;

        $form->save(null, post('_session_key', $this->sessionKey));

        return [
            'application_id' => $form->id,
            'lot_id' => $lot['lot_id'],
            'lot_features' => isset($lot['features']) ? $lot['features'] : null,
            'tender_features' => !empty(post('features')) ? post('features') : null,
        ];
    }

    protected function checkExistsApplication()
    {
        return (boolean) Application::where('bid_id', '!=', '')->where('is_test', $this->user->is_test)->where('tender_id', $this->getSystemTenderId())->where('user_id', $this->user->id)->first();
    }

    public function saveDocType() {

        if(!empty(post('doc_type'))) {

            $confidentiality = !empty(post('confidentiality')) ? post('confidentiality') : [];
            $confidentiality_text = !empty(post('confidentiality_text')) ? post('confidentiality_text') : [];

            foreach(post('doc_type') AS $id => $doc_type) {
                if($file = File::find($id)) {
                    $file->doc_cdb_type = $file->doc_type == 2 ? 'commercialProposal' : $doc_type;
                    $file->conf_text = isset($confidentiality[$id]) ? $confidentiality_text[$id] : null;
                    $file->save();
                }
            }
        }

        return true;
    }
}
