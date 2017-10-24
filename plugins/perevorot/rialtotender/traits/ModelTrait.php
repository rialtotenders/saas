<?php

namespace Perevorot\Rialtotender\Traits;

trait ModelTrait
{
    /**
     * @param $scope
     * @return mixed
     */
    public function scopeAvailable($scope)
    {
        return $scope->where('is_enabled', true);
    }

    /**
     * @param $scope
     * @return mixed
     */
    public function scopeIndex($scope)
    {
        return $scope->where('is_index', true);
    }

    /**
     * @param $scope
     * @return mixed
     */
    public function scopeSortable($scope)
    {
        return $scope->orderBy('sort_order', 'ASC');
    }
}