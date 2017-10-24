<?php namespace Perevorot\Elixir;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'elixir' => [$this, 'elixirVersions']
            ]
        ];
    }

    public function elixirVersions($file)
    {
        static $manifest = null;

        if (is_null($manifest)) {
            $manifest = json_decode(file_get_contents(public_path('build/rev-manifest.json')), true);
        }

        if (isset($manifest[$file])) {
            return '/build/'.$manifest[$file];
        }

        throw new InvalidArgumentException("File {$file} not defined in asset manifest.");
    }
}