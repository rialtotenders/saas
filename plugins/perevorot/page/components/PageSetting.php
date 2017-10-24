<?php namespace Perevorot\Page\Components;

use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Users\Models\ExternalAuth;
use Cms\Classes\ComponentBase;
use Request;

class PageSetting extends ComponentBase
{
    use CurrentLocale;
    
    private $setting;

    public function init()
    {
        $this->setting = Setting::instance();
    }

    public function onRun()
    {
        $this->page['logo_url'] = $this->setting->get_value('logo_url');
        $this->page['getLoginUrl'] = $this->getLoginUrl();
        $this->page['getRegisterUrl'] = $this->getRegisterUrl();
    }

    public function componentDetails()
    {
        return [
            'name' => 'Настрйоки для страницы',
            'description' => 'Настрйоки для страницы',
            'icon'=>'icon-files-o',
        ];
    }

    public function defineProperties()
    {
        return [];
    }
    
    private function getLoginUrl()
    {
        $settings=ExternalAuth::instance();

        $url=$this->getCurrentLocale().'login';

        if($settings->is_enabled && $settings->is_nologin)
        {
            $provider=new \League\OAuth2\Client\Provider\GenericProvider([
                'clientId'                => $settings->clientId,
                'clientSecret'            => $settings->clientSecret,
                'urlAuthorize'            => $settings->urlAuthorize,
                'urlAccessToken'          => $settings->urlAccessToken,
                'urlResourceOwnerDetails' => $settings->urlResourceOwnerDetails,
                'redirectUri'             => Request::url(),
            ]);

            $url=$provider->getAuthorizationUrl();
        }
        
        return $url;
    }
    
    private function getRegisterUrl()
    {
        $settings=ExternalAuth::instance();

        $url=false;

        if($settings->is_enabled && $settings->urlRegister)
            return $settings->urlRegister;
        
        return $url;
    }
}
