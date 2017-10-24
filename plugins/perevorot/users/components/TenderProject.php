<?php

namespace Perevorot\Users\Components;

use Cms\Classes\ComponentBase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Traits\TenderTrait;
use Perevorot\Users\Traits\TenderUtils;
use Perevorot\Users\Facades\Auth;

/**
 * Class RegistrationForm
 * @package Perevorot\Users\Components
 */
class TenderProject extends ComponentBase
{
    use CurrentLocale;

    /**
     * @var
     */

    public $siteLocale;
    private $setting;

    /**
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'TenderProject',
            'description' => 'Tender project page'
        ];
    }

    /**
     * @return array
     */
    public function defineProperties()
    {
        return [
            'type' => [
                'label' => 'Тип'
            ]
        ];
    }

    public function init()
    {
        $this->siteLocale = $this->getCurrentLocale();
        //$this->setting = Setting::instance();
    }

    /**
     * @return array|RedirectResponse|mixed|string
     */
    public function onRun()
    {
        if($tenderID = $this->param('id')) {

            $tender = Tender::find($tenderID);
            $this->page['tender'] = $tender->getJson();
            $this->page['user_tender'] = $tender;
            $this->page['siteLocale'] = $this->siteLocale;
        }
        else
        {
            return redirect()->to($this->siteLocale);
        }
    }

    /**
     * @return mixed
     */
    public function onRender()
    {
        return $this->renderPartial('@tenderproject/index');
    }

}
