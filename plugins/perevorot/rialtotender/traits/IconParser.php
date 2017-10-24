<?php

namespace Perevorot\Rialtotender\Traits;

trait IconParser
{
    /**
     * @param $url
     * @return string
     */
    private function getIconPath($url)
    {
        if (!$url) {
            return;
        }

        $icon = implode('/', array_slice(str_split($url, 3), 0, 3)) . '/' . $url;

        return '/storage/app/uploads/public/' . $icon;
    }
}
