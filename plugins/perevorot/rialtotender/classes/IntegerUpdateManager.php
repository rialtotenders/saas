<?php namespace Perevorot\Rialtotender\Classes;

use App;
use System\Classes\UpdateManager;

class IntegerUpdateManager extends UpdateManager
{
    /**
     * Create a new instance of this singleton.
     */
    final public static function getInstance()
    {
        self::$instance = new static;
        return self::$instance;
    }

    public function bindContainerObjects()
    {
        $this->migrator = App::make('migrator');
        $this->migrator->setConnection('integer');

        $this->repository = App::make('migration.repository');
    }
}
