<?php namespace Perevorot\Rialtotender\Widgets;

use Backend\Classes\ReportWidgetBase;
use Artisan;
use Flash;
use Lang;

class GitReport extends ReportWidgetBase
{

    protected $defaultAlias = 'perevorot_git_report';
	
    public function render(){
        return $this->makePartial('widget');
    }

    public function defineProperties()
    {
        return [
            'title' => [
                'title'             => 'Git & WS report',
                'default'           => 'Git & WS report',
                'type'              => 'string',
            ],
        ];
    }
}
