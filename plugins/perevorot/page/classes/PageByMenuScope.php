<?php namespace Perevorot\Page\Classes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Session;
use Input;

class PageByMenuScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if(Input::get('menu'))
            $builder->where('menu_id', '=', Input::get('menu'));
    }

    public function remove(Builder $builder, Model $model)
    {
    }
}
