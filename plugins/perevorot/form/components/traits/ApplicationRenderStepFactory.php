<?php

namespace Perevorot\Form\Components\Traits;

use Perevorot\Form\Classes\ApplicationFactory;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\Procurement;

/**
 * Class ApplicationRenderStepFactory
 * @package Perevorot\Form\Traits
 */
trait ApplicationRenderStepFactory
{
    /**
     * @param $step
     * @return array
     * @throws \Exception
     */
    public function processRenderStepFactory($step)
    {
        switch ($step) {
            case ApplicationFactory::FIRST_STEP:
                return $this->processRenderFirstStep();
            case ApplicationFactory::SECOND_STEP:
                return $this->processRenderSecondStep();
            case ApplicationFactory::LOT_STEP:
                return $this->processRenderLotStep();
            case ApplicationFactory::LAST_STEP:
                return $this->processRenderLastStep();

            default:
                throw new \Exception('Invalid argument exception');
        }
    }

    /**
     * @return array
     */
    private function processRenderFirstStep()
    {
        $applications = null;
        if($this->isApplicationCreated()) {
            $applicationsFromSession = $this->getApplications();
            $ids = [];

            foreach ($applicationsFromSession as $item) {
                $ids[] = $item->application_id;
            }

            $applications = Application::findMany($ids)->keyBy('lot_id');

            foreach ($applications as $k => $app) {
                if (isset($app->tender_features)) {
                    $app->tender_features = (array)json_decode($app->tender_features);
                }
                if (isset($app->lot_features)) {
                    $app->lot_features = (array)json_decode($app->lot_features);
                }
            }
        }

        return [
            'template' => self::FIRST_STEP_TEMPLATE,
            'params' => [
                'step' => $this->getCurrentStep(),
                'applications' => $applications,
            ],
        ];
    }

    private function processRenderSecondStep()
    {


        return [
            'template' => self::SECOND_STEP_TEMPLATE,
            'params' => [
                'step' => $this->getCurrentStep(),
            ],
        ];
    }

    private function processRenderLotStep()
    {
        $lot_id = null;
        $apps = $this->getApplications();

        if(!$this->getLotStep()) {
            $lot_id = head($apps)->lot_id;
            $this->setLotStep(0);
        } else {

            if(!isset($apps[$this->getLotStep()])) {
                $this->setStepNumber(4);
                return $this->processRenderLastStep();
            }

            $lot_id = $apps[$this->getLotStep()]->lot_id;
        }

        $user_app = array_first($this->getApplications(), function($app, $key) use($lot_id) {
            return $lot_id == $app->lot_id;
        });
        $lot = array_first($this->tender->lots, function($lot, $key) use($lot_id) {
            return $lot_id == $lot->id;
        });
        $application = Application::find($user_app->application_id);

        return [
            'template' => self::LOT_STEP_TEMPLATE,
            'params' => [
                'step' => $this->getCurrentStep(),
                'lot' => $lot,
                'application' => $application,
            ],
        ];
    }

    private function processRenderLastStep()
    {
        $applications = null;

        if ($this->isApplicationCreated()) {
            $applicationsFromSession = $this->getApplications();
            $ids = [];

            foreach ($applicationsFromSession as $item) {
                $ids[] = $item->application_id;
            }

            $applications = Application::findMany($ids)->keyBy('lot_id');
        }

        foreach($applications as $k => $app) {
            if(isset($app->tender_features)) {
                $app->tender_features = (array)json_decode($app->tender_features);
            }
            if(isset($app->lot_features)) {
                $app->lot_features = (array)json_decode($app->lot_features);
            }
        }

        return [
            'template' => self::LAST_STEP_TEMPLATE,
            'params' => [
                'step' => $this->getCurrentStep(),
                'applications' => $applications,
            ],
        ];
    }


}
