<?php namespace Perevorot\Blog;

use System\Classes\PluginBase;
use Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'Perevorot\Blog\Components\Blog' => 'blog',
            'Perevorot\Blog\Components\Article' => 'article',
            'Perevorot\Blog\Components\Last' => 'last',
            'Perevorot\Blog\Components\Groups' => 'groups',
        ];
    }

    public function registerSettings()
    {
    }

    public function boot()
    {
    }
}
