<?php

namespace Perevorot\Users\Traits;

trait UserSetting
{
    public function checkUserMode($user = null, $source = null, $test = false) {

        if($source !== null) {

            $prefix = $source ? 'GOV_' : '';
            $prefix .= ((($user && $user->is_test) || $test) ? 'TEST_' : '');

            return $prefix;
        }
        elseif($user) {

            $prefix = ($user->is_gov ? 'GOV_' : '');
            $prefix .= ($user->is_test || $test) ? 'TEST_' : '';

            return $prefix;
        } elseif($test) {
            return 'TEST_';
        }

        return '';
    }
}