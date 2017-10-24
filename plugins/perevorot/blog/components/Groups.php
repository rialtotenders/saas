<?php

namespace Perevorot\Blog\Components;

use Perevorot\Blog\Models\Blog as ArticleModel;
use Perevorot\Blog\Models\Group;
use Cms\Classes\ComponentBase;

/**
 * Class ApplicationCreater
 * @package Perevorot\Form\Components
 */
class Groups extends ComponentBase
{
    /**
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Список групп',
            'description' => ''
        ];
    }

    /**
     * @return array
     */
    public function defineProperties()
    {
        return [];
    }

    /**
     * @return void
     */
    public function init()
    {
    }

    /**
     * @return void
     */
    public function onRun()
    {
        $groups=Group::where('is_enabled', true)->orderBy('sort_order', 'ASC')->get();

        $this->page['groups'] = $groups;

        if($this->param('gslug')){
            $slug=array_first($groups, function($item, $i){
                return $item->slug==$this->param('gslug');
            })->slug;
        }elseif($this->param('aslug')){
            $article=ArticleModel::where('slug', $this->param('aslug'))->first();

            $slug=array_first($groups, function($item, $i) use($article){
                return $item->id==$article->group_id;
            })->slug;
        }else{
            $slug=$this->param('gslug');
        }

        $this->page['slug'] = $slug;
    }

    /**
     * @return mixed
     */
    public function onRender()
    {

    }
}
