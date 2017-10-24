<?php

namespace Perevorot\Form\Components\Traits;

use Illuminate\Support\Facades\Session;

/**
 * Class ApplicationUpdateSessionUtils
 * @package Perevorot\Form\Traits
 */
trait ApplicationUpdateSessionUtils
{
    protected function removeApplications()
    {
        Session::remove('tender_' . $this->getTenderId() . '_multi_lot.u_applications');
        Session::remove('tender_' . $this->getTenderId() . '_multi_lot.memory.u_applications');
        Session::remove('tender_' . $this->getTenderId() . '_multi_lot.updated');
        Session::remove('tender_' . $this->getTenderId() . '_multi_lot.u_step_number');
        Session::remove('tender_' . $this->getTenderId() . '_multi_lot.u_lot_step');
    }

    /**
     * @return mixed
     */
    protected function getApplications()
    {
        $applications = Session::get('tender_' . $this->getTenderId() . '_multi_lot.u_applications');

        return json_decode($applications);
    }

    /**
     * @param $applications
     */
    protected function setApplications($applications)
    {
        Session::put('tender_' . $this->getTenderId() . '_multi_lot.u_applications', json_encode($applications));
    }

    /**
     * @return mixed
     */
    protected function getMemoryApplications()
    {
        $applications = Session::get('tender_' . $this->getTenderId() . '_multi_lot.memory.u_applications');

        return json_decode($applications);
    }

    /**
     * @param $applications
     */
    protected function setMemoryApplications($applications)
    {
        Session::put('tender_' . $this->getTenderId() . '_multi_lot.memory.u_applications', json_encode($applications));
    }

    /**
     * @return mixed
     */
    protected function getStepNumber()
    {
        return Session::get('tender_' . $this->getTenderId() . '_multi_lot.u_step_number', 1);
    }

    /**
     * @param $step
     * @return mixed
     */
    protected function setStepNumber($step)
    {
        return Session::put('tender_' . $this->getTenderId() . '_multi_lot.u_step_number', $step);
    }

    /**
     * @return mixed
     */
    protected function isApplicationCreated()
    {
        return Session::get('tender_' . $this->getTenderId() . '_multi_lot.is_application_updated', false);
    }

    /**
     * @param $status
     */
    protected function setApplicationCreatedStatus($status)
    {
        Session::put('tender_' . $this->getTenderId() . '_multi_lot.is_application_updated', $status);
    }

    protected function getLotStep()
    {
        return Session::get('tender_' . $this->getTenderId() . '_multi_lot.u_lot_step', 0);
    }

    protected function setLotStep($step)
    {
        return Session::put('tender_' . $this->getTenderId() . '_multi_lot.u_lot_step', $step);
    }
}
