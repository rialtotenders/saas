<?php

namespace Perevorot\Blog\Components;

use Perevorot\Blog\Models\Blog as Article;
use Cms\Classes\ComponentBase;

/**
 * Class ApplicationCreater
 * @package Perevorot\Form\Components
 */
class Last extends ComponentBase
{
    /**
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Последние статьи',
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
        $this->page['last_articles'] = Article::where('is_enabled', true)->orderBy('published_at', 'DESC')->take(5)->get();
    }

    /**
     * @return mixed
     */
    public function onRender()
    {
        
    }
}
