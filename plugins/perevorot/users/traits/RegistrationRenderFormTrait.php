<?php

namespace Perevorot\Users\Traits;

use Illuminate\Support\Facades\Session;
use Perevorot\Rialtotender\Exceptions\InvalidUserFromSession;
use Perevorot\Rialtotender\Models\Classifier;
use Perevorot\Rialtotender\Models\FormMessage;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Components\RegistrationForm;
use Perevorot\Users\Models\Message;
use Perevorot\Users\Models\User;
use Perevorot\Page\Models\Page;
use App;
/**
 * Class RegistrationRenderFormTrait
 * @package Perevorot\Users\Traits
 */
trait RegistrationRenderFormTrait
{
    /**
     * @var Message $messages
     */
    private $messages;
    private $steps = 3;

    /**
     * @param $step
     * @return bool|void
     * @throws InvalidUserFromSession
     */
    private function renderStepFactory($step)
    {
        $this->user = $this->getUser();
        $this->messages = Message::instance();
        $setting = Setting::instance();
        $this->steps = $setting->get_value('show_offerta') ? 4 : $this->steps;

        if (!($this->user instanceof User)) {
            throw new InvalidUserFromSession();
        }

        switch ($step) {
            case 1:
                return $this->renderStepOne();

            case 2:
                return $this->renderStepTwo();

            case 3:
                return $this->renderStepThree();

            case 4:
                return $this->renderStepFour();

            case 5:
                return $this->renderStepFive();

            default:
                return false;
        }
    }

    /**
     * @return string
     */
    private function renderStepOne()
    {
        Session::put('registration.session', 1);

        return [
            'template' => RegistrationForm::STEP_ONE_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(1),
                'header' => $this->getHeader(1),
                'user'  => $this->user,
                'steps' => $this->steps,
            ]
        ];
    }

    /**
     * @return string
     */
    private function renderStepTwo()
    {
        Session::put('registration.session', 2);
        $setting = Setting::instance();

        return [
            'template' => RegistrationForm::STEP_TWO_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(2),
                'header' => $this->getHeader(2),
                'user'  => $this->user,
                'showUserIsGo' => $setting->checkAccess('showUserIsGo'),
                'steps' => $this->steps,
            ]
        ];
    }

    /**
     * @return string
     */
    private function renderStepThree()
    {
        Session::put('registration.session', 3);

        return [
            'template' => RegistrationForm::STEP_THREE_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(3),
                'header' => $this->getHeader(3),
                'user'  => $this->user,
                'steps' => $this->steps,
            ]
        ];
    }

    private function renderStepFour()
    {
        Session::put('registration.session', 4);

        return [
            'template' => RegistrationForm::STEP_FOUR_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(4),
                'header' => $this->getHeader(4),
                'user'  => $this->user,
                'offerta_link' => Setting::getData('offerta_link', $this->getCurrentLocaleWithoutSlash()),
                'steps' => $this->steps,
            ]
        ];
    }

    /**
     * @return string
     */
    private function renderStepFive()
    {
        Session::remove('registration.session');
        Session::remove('registration.user_id');

        $page=Page::where('url', '/registration')->first();
        
        return [
            'template' => RegistrationForm::STEP_FIVE_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(5),
                'header' => $this->getHeader(5),
                'user'  => $this->user,
                'page' => $page
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
