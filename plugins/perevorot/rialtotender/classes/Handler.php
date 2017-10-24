<?php namespace Perevorot\Rialtotender\Classes;

use Carbon\Carbon;
use Log;
use Event;
use Response;
//use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use October\Rain\Exception\AjaxException;
use ReflectionFunction;
use Exception;
use Closure;
use App;

class Handler extends \October\Rain\Foundation\Exception\Handler
{
    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        if ($this->shouldntReport($exception))
            return;

        if (class_exists('Log')) {
            Log::error($exception);
        }

        if (App::runningInConsole())
        {
            $path = App::basePath();
            $file = '.env';

            if(!empty($_SERVER['argv']) && isset($_SERVER['argv'][2])) {
                $host = explode('=', $_SERVER['argv'][2])[1];
                $is_found = false;
                $integet_root = base_path('integer');
                $env = head(glob($integet_root . '/'.$host.'/.env'));
                $data = $this->readEnvFromString(file_get_contents($env));
                $msg = "Error on <".$data->APP_URL."|".$data->APP_URL."> with code #".$exception->getCode().":";
                $api = new SlackApi(@$data->SLACK_HOOKS_URL);
            } else {
                return;
            }
        } else {
            $msg = "Error on <".env('APP_URL')."|".env('APP_URL')."> with code #".$exception->getCode().":";
            $api = new SlackApi(env('SLACK_HOOKS_URL'));
        }

        $_msg = $exception->getMessage().' in '.$exception->getFile().':'.$exception->getLine()."\ndt=".Carbon::now();
        $api->sendHook($msg, $_msg);
    }

    public function readEnvFromString($string, $_variable=false)
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
}
