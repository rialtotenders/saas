<?php namespace Perevorot\Rialtotender\Longread;

use Perevorot\Longread\Classes\LongreadComponentBase;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Rialtotender\Traits\IconParser;
use System\Models\File;
use Request;

class RegistrationAndSearch extends LongreadComponentBase
{
    use CurrentLocale;

    public function onRender()
    {
        $data = json_decode($this->property('value'));
        $search_type = (Request::segment(1)=='plan' ? 'plan' : 'tender');

        if ($data->search_image) {
            $data->search_image=File::find($data->search_image->id);
        }

        $setting = Setting::instance();

        return $this->partial([
            'is_gov' => ($this->param('source')=='gov'),
            'siteLocale' => $this->getCurrentLocale(),
            'data' => $data,
            'search_type' => $search_type,
            'access_to_search' => (boolean) env('USER_SEARCH_ACCESS'),
            'is_tender' => $setting->checkAccess('is_tender'),
            'is_plan' => $setting->checkAccess('is_plan'),
            'is_gov_tender' => $setting->checkAccess('is_gov'),
            'is_gov_plan' => $setting->checkAccess('is_gov_plan'),
        ]);
    }
}
