<?php

namespace Perevorot\Rialtotender\Traits;
use RainLab\Translate\Classes\Translator;

trait CurrentLocale
{
    /**
     * @return string (/ or /ru/)
     */
    protected function getCurrentLocale($lang = null)
    {
        $translator = Translator::instance();
        $activeLocale = $lang ? $lang : $translator->getLocale();
        return $translator->getDefaultLocale() == $activeLocale ? '/' : '/'.$activeLocale.'/';
    }

    /**
     * @return string (en or ru or etc)
     */
    protected function getCurrentLocaleWithoutSlash()
    {
        $translator = Translator::instance();
        return $translator->getLocale();
    }

    protected function getLocaleForEmail($lang = null) {
        $translator = Translator::instance();
        $activeLocale = $lang ? $lang : $translator->getLocale();
        return $translator->getDefaultLocale() == $activeLocale ? '' : ($activeLocale."::");
    }
}
