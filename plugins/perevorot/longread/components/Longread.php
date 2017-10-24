<?php namespace Perevorot\Longread\Components;

use Perevorot\Page\Models\Page;
use Cms\Classes\ComponentBase;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Models\User;
use Request;
use Cache;

class Longread extends ComponentBase
{
    use CurrentLocale;

    public function componentDetails()
    {
        return [
            'name' => 'perevorot.page::components.longread.component.name',
            'description' => 'perevorot.page::components.longread.component.description',
            'icon'=>'icon-files-o',
        ];
    }

    public function onRun()
    {
        if(!$this->page->url || $this->page->url == '/')
        {
            $user = Auth::getUser();

            if($user instanceof User && env('USER_SEARCH_ACCESS'))
            {
                $setting = Setting::instance();
                return redirect()->to($this->getCurrentLocale().'tender/search'.((($user->checkGroup('supplier') || $user->is_gov) && $setting->checkAccess('is_gov')) ? '/gov' : '')."?tab_type=1&status=active.enquiries&status=active.tendering");
            }
            elseif($user && env('USER_SEARCH_ACCESS'))
            {
                return redirect()->to($this->getCurrentLocale()."tender/search?tab_type=1&status=active.enquiries&status=active.tendering");
            }
        }
    }

    public function onRender()
    {
        $page=Page::where('url', '=', $this->page->url)->first();

        return $page->longread;
    }
}
