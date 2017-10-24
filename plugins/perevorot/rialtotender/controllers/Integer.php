<?php namespace Perevorot\Rialtotender\Controllers;

use Perevorot\Rialtotender\Classes\IntegerUpdateManager;
use Backend\Classes\Controller;
use ApplicationException;
use BackendMenu;
use Artisan;
use Input;
use Config;
use DB;

class Integer extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    
    var $env;

    public function __construct()
    {
        parent::__construct();

	    $this->parseEnv();
	    $this->setConnection();

        BackendMenu::setContext('Perevorot.Rialtotender', 'rialtotenderinteger', 'integer');
    }

    public function onCallRefresh()
    {
	    DB::connection('integer')->statement('SET FOREIGN_KEY_CHECKS=0;');

	    $tables=DB::connection('integer')->table('INFORMATION_SCHEMA.TABLES')->where('TABLE_TYPE', '=', 'BASE TABLE')->where('TABLE_SCHEMA', $this->env->DB_DATABASE)->get();
	    $tables=array_pluck($tables, 'TABLE_NAME');

	    foreach($tables as $table)
		    DB::connection('integer')->statement('DROP TABLE `'.$table.'`');

	    DB::connection('integer')->statement('SET FOREIGN_KEY_CHECKS=1;');

	    throw new ApplicationException('Готово');
    }

    public function onCallMigrate()
    {
	    $this->parseEnv();
	    $this->setConnection();

        $manager = IntegerUpdateManager::instance()->resetNotes()->update();

        $output='';

        foreach ($manager->getNotes() as $note)
            $output.=$note;

		Artisan::call('integer:up', [
			'host' => $this->env->DB_HOST,
            'port' => $this->env->DB_PORT,
            'database' => $this->env->DB_DATABASE,
            'username' => $this->env->DB_USERNAME,
            'password' => $this->env->DB_PASSWORD
		]);

        throw new ApplicationException(strip_tags($output));
    }
    
    
    private function parseEnv()
    {
	    $env=Input::get('Integer.env');

	    $connection=[];

		foreach(explode("\n", $env) as $row) {
            if(!empty(trim($row))) {
                list($param, $value)=explode('=', $row);

                $connection[trim($param)]=trim($value);
            }
        }	    

	    $this->env=(object) $connection;
    }
    
    private function setConnection()
    {
	    if(empty($this->env->DB_HOST))
	    	return;

        Config::set('database.connections.integer', [
            'driver' => 'mysql',
            'host' => $this->env->DB_HOST,
            'port' => $this->env->DB_PORT,
            'database' => $this->env->DB_DATABASE,
            'username' => $this->env->DB_USERNAME,
            'password' => $this->env->DB_PASSWORD,
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ]);	    
    }
}
