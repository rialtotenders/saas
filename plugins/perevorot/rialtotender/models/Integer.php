<?php namespace Perevorot\Rialtotender\Models;

use Model;
use Input;
use October\Rain\Database\Traits\Validation;
use ApplicationException;


/**
 * Model
 */
class Integer extends Model
{
    use Validation;

    /*
     * Validation
     */
    public $rules = [
        'title' => 'required',
        'domain' => 'required',
        'env' => 'required',
        'theme_folder' => 'required',
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'perevorot_rialtotender_integer';

    public function getEnvAttribute($value)
    {
        if(!empty($this->theme_folder))
        {
            $env_file=base_path().'/integer/'.$this->theme_folder.'/.env';
    
            if(Input::get('fields') && in_array('env', Input::get('fields')) && is_file($env_file))
            {
                $value=file_get_contents($env_file);
            }
        }

        if(empty($value)){
            $value=file_get_contents('./.env.example');
        }

        return $value;
    }

    public function afterFetch()
    {
        $env_file=base_path().'/integer/'.$this->theme_folder.'/.env';

        if(is_file($env_file))
            $this->env=file_get_contents($env_file);
    }
    
    public function afterSave()
    {
        $env=$this->readEnvFromString($this->env);

        $config_path=base_path().'/integer/'.$this->theme_folder;

        if (!is_dir($config_path)) {
            mkdir($config_path);
        }

        file_put_contents($config_path.'/.env', $this->env);
    }

    public function getThemeFolderOptions()
    {
        $result = [
            '' => 'Выберите папку с темой'
        ];

        $folders=glob(base_path().'/integer/*', GLOB_ONLYDIR);

        foreach ($folders as $folder) {
            $folder=last(explode('/', $folder));
            $result[$folder] = $folder;
        }

        return $result;
    }
    
    protected function readEnvFromString($string)
    {
        $array=explode("\r\n", $string);
        $array=array_filter($array);
        $env=[];

        foreach($array as $one)
        {
            list($variable, $value)=explode('=', $one);

            $env[$variable]=$value;
        }

        return (object) $env;
    }
}
