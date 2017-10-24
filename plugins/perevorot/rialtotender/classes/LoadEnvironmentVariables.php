<?php namespace Perevorot\Rialtotender\Classes;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Symfony\Component\Console\Input\ArgvInput;
use Illuminate\Contracts\Foundation\Application;

class LoadEnvironmentVariables
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $path = $app['path.base'];
        $file = '.env';
        $envs = [];

        if(!empty($_SERVER['HTTP_HOST']))
        {
            $host=$_SERVER['HTTP_HOST'];
            $is_found=false;

            $integet_root=base_path('integer');


            if(strpos($host, ':')!==false)
                $host=substr($host, 0, strpos($host, ':'));

            foreach (glob($integet_root.'/*/.env') as $filename)
            {
                $domain=$this->readEnvFromString(file_get_contents($filename), 'APP_URL');

                if(parse_url($domain, PHP_URL_HOST)==$host)
                {
                    $path=mb_substr($filename, 0, -5);
                    $localpath=substr($path, strlen(base_path())+1);
                    $file='.env';
                    $is_found=true;

                    break;
                }
            }

            if (file_exists($path.'/'.$file) && $is_found)
            {
                $envs=[
                    'LOCAL_STORAGE_PATH'=>$path.'/storage/app',
                    'INTEGER_LOG_PATH'=>$path.'/storage/logs',
                    'STORAGE_PATH'=>'/'.$localpath.'/storage/app/uploads',
                    'MEDIA_PATH'=>'/'.$localpath.'/storage/app/media',
                    'THEMES_PUBLIC_PATH'=>'/'.$localpath,
                    'THEMES_PATH'=>$path,
                    'ACTIVE_THEME'=>'theme',
                    'CACHE_PATH'=>$path.'/storage/framework/cache',
                    'SESSIONS_PATH'=>$path.'/storage/framework/sessions'
                ];
            }
            else
            {
                $path = $app['path.base'];
                $file = '.env';
            }
        }

        try {
            $dotenv=(new Dotenv($path, $file));
            $dotenv->load();

            foreach($envs as $env=>$value){
                $this->setEnvironmentVariable($env, $value);
            }
        }
        catch (InvalidPathException $e) {
            //
        }

        $app->detectEnvironment(function () {
            return env('APP_ENV', 'production');
        });

    }

    protected function readEnvFromString($string, $_variable=false)
    {
        $array=explode("\r\n", $string);
        $array=array_filter($array);
        $env=[];

        foreach($array as $one)
        {
            list($variable, $value)=explode('=', $one);

            if($_variable && $variable==$_variable){
                return $value;
            }

            $env[$variable]=$value;
        }

        return (object) $env;
    }

    public function setEnvironmentVariable($name, $value = null)
    {
        if (function_exists('apache_getenv') && function_exists('apache_setenv') && apache_getenv($name)) {
            apache_setenv($name, $value);
        }

        if (function_exists('putenv')) {
            putenv("$name=$value");
        }

        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
