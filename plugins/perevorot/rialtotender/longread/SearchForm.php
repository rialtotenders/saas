<?php namespace Perevorot\Rialtotender\Longread;

use Perevorot\Longread\Classes\LongreadComponentBase;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Traits\AccessToTenders;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use RainLab\Translate\Models\Locale;
use System\Models\File;
use Request;
use Config;
use App;
use Auth;
use Illuminate\Http\RedirectResponse;

class SearchForm extends LongreadComponentBase
{
    use AccessToTenders;

    public function onRender()
    {
        $redirect = $this->redirectTo();

        if($redirect instanceof RedirectResponse)
        {
            return $redirect;
        }

        $data = json_decode($this->property('value'));

        if ($data->search_form_image) {
            $data->search_form_image=File::find($data->search_form_image->id);
        }

        $search_type = (Request::segment(1)=='plan' ? 'plan' : 'tender');
        $preselected_values='';
        $setting = Setting::instance();

        return $this->partial([
            'siteLocale' => $this->getCurrentLocale(),
            'data' => $data,
            'search_type' => $search_type,
            'locale_href' => '',//(App::getLocale()!=Locale::getDefault()->code ? '/'.App::getLocale().'/' : ''),
            'result' => '',
            'preselected_values' => $preselected_values,
            'buttons' => Config::get('prozorro.buttons.'.$search_type),
            'is_tender' => $setting->checkAccess('is_tender'),
            'is_plan' => $setting->checkAccess('is_plan'),
            'is_gov_tender' => $setting->checkAccess('is_gov'),
            'is_gov_plan' => $setting->checkAccess('is_gov_plan'),
            'settings' => $setting,
        ]);
    }
}
