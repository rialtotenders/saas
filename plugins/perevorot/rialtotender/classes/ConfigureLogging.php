<?php namespace Perevorot\Rialtotender\Classes;

use Illuminate\Log\Writer;
use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\ConfigureLogging as BaseConfigureLogging;

/**
 * Class ConfigureLogging
 * @package App\Bootstrap
 */
class ConfigureLogging extends BaseConfigureLogging
{

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureHandlers(Application $app, Writer $log)
    {
        $log_dir = $app->storagePath();

        if(stripos($log_dir, '/logs') === FALSE) {
            $log_dir .= '/logs';
        }

        $infoStreamHandler = new StreamHandler( env('INTEGER_LOG_PATH', $log_dir).'/users.log', Monolog::INFO, false);
        $errorStreamHandler = new StreamHandler( env('INTEGER_LOG_PATH', $log_dir).'/errors.log', Monolog::ERROR, false);

        $monolog = $log->getMonolog();

        $monolog->pushHandler($infoStreamHandler);
        $monolog->pushHandler($errorStreamHandler);
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureDailyHandler(Application $app, Writer $log)
    {
        $log->useDailyFiles($app->storagePath().'/logs/system.log', 5);
    }
}
