<?php

namespace Perevorot\Blog\Components;

use Perevorot\Blog\Models\Group;
use Perevorot\Blog\Models\Blog as Article;
use Cms\Classes\ComponentBase;

/**
 * Class ApplicationCreater
 * @package Perevorot\Form\Components
 */
class Blog extends ComponentBase
{
    /**
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Список статей',
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
        $articles=Article::where('is_enabled', true)->orderBy('published_at', 'DESC');
        
        if($this->param('gslug'))
        {
            $group=Group::where('slug', $this->param('gslug'))->first();

            $articles->where('group_id', '=', $group->id);
        }
        
        $this->page['articles'] = $articles->paginate(8);
    }

    /**
     * @return mixed
     */
    public function onRender()
    {
        
    }
}
