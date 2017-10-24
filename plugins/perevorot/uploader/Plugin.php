<?php namespace Perevorot\Uploader;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    /**
     * @var array
     */
    public $require = [
        'Responsiv.Uploader',
    ];

    public function registerComponents()
    {
        return [
            'Perevorot\Uploader\Components\FileUploader' => 'fileUploader',
        ];
    }

    public function registerSettings()
    {
    }
}
