<?php

namespace Perevorot\Users\Traits;

/**
 * Class UserModelTrait
 * @package Perevorot\Users\Traits
 */
trait UserModelMutatorsTrait
{
    /**
     * @param $value
     * @return bool
     */
    public function getProviderAttribute($value)
    {
        return $value == 'on';
    }

    /**
     * @param $value
     * @return bool
     */
    public function setProviderAttribute($value)
    {
        $this->attributes['provider'] = $value ? 'on' : 'off';
    }

    /**
     * @param $value
     * @return bool
     */
    public function getCustomerAttribute($value)
    {
        return $value == 'on';
    }

    /**
     * @param $value
     * @return bool
     */
    public function setCustomerAttribute($value)
    {
        $this->attributes['customer'] = $value ? 'on' : 'off';
    }
}
