<?php

namespace Perevorot\Form\Components\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use October\Rain\Database\Collection;
use October\Rain\Support\Facades\Form;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Users\Facades\Auth;

/**
 * Class ApplicationUpdateStepUtils
 * @package Perevorot\Form\Traits
 */
trait ApplicationCreateSessionUtils
{
    protected function removeApplications()
    {
        Session::remove('tender_' . $this->getTenderId() . '_multi_lot.applications');
        Session::remove('tender_' . $this->getTenderId() . '_multi_lot.memory.applications');
        Session::remove('tender_' . $this->getTenderId() . '_multi_lot.is_application_created');
        Session::remove('tender_' . $this->getTenderId() . '_multi_lot.step_number');
    }
    /**
     * @return mixed
     */
    protected function getApplications()
    {
        $applications = Session::get('tender_' . $this->getTenderId() . '_multi_lot.applications');

        if (is_array($applications)) {
            return $applications;
        }

        return json_decode($applications);
    }

    /**
     * @param $applications
     */
    protected function setApplications($applications)
    {
        Session::put('tender_' . $this->getTenderId() . '_multi_lot.applications', json_encode($applications));
    }
    
    /**
     * @return mixed
     */
    protected function getMemoryApplications()
    {
        $applications = Session::get('tender_' . $this->getTenderId() . '_multi_lot.memory.applications');

        return json_decode($applications);
    }

    /**
     * @param $applications
     */
    protected function setMemoryApplications($applications)
    {
        Session::put('tender_' . $this->getTenderId() . '_multi_lot.memory.applications', json_encode($applications));
    }

    /**
     * @return mixed
     */
    protected function getStepNumber()
    {
        return Session::get('tender_' . $this->getTenderId() . '_multi_lot.step_number', 1);
    }

    /**
     * @param $step
     * @return mixed
     */
    protected function setStepNumber($step)
    {
        return Session::put('tender_' . $this->getTenderId() . '_multi_lot.step_number', $step);
    }

    /**
     * @return mixed
     */
    protected function isApplicationCreated()
    {
        return Session::get('tender_' . $this->getTenderId() . '_multi_lot.is_application_created', false);
    }

    /**
     * @param $status
     */
    protected function setApplicationCreatedStatus($status)
    {
        Session::put('tender_' . $this->getTenderId() . '_multi_lot.is_application_created', $status);
    }

    protected function getLotStep()
    {
        return Session::get('tender_' . $this->getTenderId() . '_multi_lot.lot_step', 0);
    }

    protected function setLotStep($step)
    {
        return Session::put('tender_' . $this->getTenderId() . '_multi_lot.lot_step', $step);
    }
}
