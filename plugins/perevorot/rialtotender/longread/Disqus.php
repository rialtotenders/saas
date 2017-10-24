<?php namespace Perevorot\Rialtotender\Longread;

use Perevorot\Longread\Classes\LongreadComponentBase;
use October\Rain\Exception\ApplicationException;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Request;

class Disqus extends LongreadComponentBase
{
    use CurrentLocale;
    
    public function onRender()
    {
        if(!env('DISQUS_DOMAIN')){
            throw new ApplicationException('Для использования DISQUS, вам необходимо добавить настройку DISQUS_DOMAIN в файл .env');
        }

        return $this->partial([
            'domain' => env('DISQUS_DOMAIN'),
            'url'=>Request::fullUrl(),
            'identifier'=>$this->page->page->id,
            'locale'=>$this->getCurrentLocaleWithoutSlash()
        ]);
    }
}
