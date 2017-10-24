<?php

namespace Perevorot\Blog\Components;

use Perevorot\Blog\Models\Blog as ArticleModel;
use Perevorot\Blog\Models\Group as GroupModel;
use Cms\Classes\ComponentBase;

/**
 * Class ApplicationCreater
 * @package Perevorot\Form\Components
 */
class Article extends ComponentBase
{
    /**
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Статья',
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
        $article=ArticleModel::where('slug', $this->param('aslug'))->first();

        if(!$article)
            return \Response::make('Page not found', 404);

        $group=GroupModel::where('id', '=', $article->group_id)->first();

        $this->page['article'] = $article;
        $this->page['gslug'] = $group->slug;        
        $this->page['random_articles'] = ArticleModel::where('is_enabled', true)->whereNotIn('id', [$article->id])->orderByRaw("RAND()")->take(2)->get();
    }

    /**
     * @return mixed
     */
    public function onRender()
    {
        
    }
}
