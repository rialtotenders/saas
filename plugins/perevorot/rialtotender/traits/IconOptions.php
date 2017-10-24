<?php

namespace Perevorot\Rialtotender\Traits;

use Perevorot\Rialtotender\Models\Icon;

trait IconOptions
{
    public function getRepeaterIconOptions()
    {
        $result = [
            0 => 'Выберите нужную иконку'
        ];

        $icons = Icon::all();

        foreach ($icons as $icon) {
            if($icon && $icon->image) {
                $result[$icon->image->disk_name] = $icon->name;
            }
        }

        return $result;
    }
}
