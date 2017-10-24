<?php namespace Perevorot\Rialtotender\Console;

use DB;
use Illuminate\Support\Facades\Artisan;
use Perevorot\Rialtotender\Classes\IntegerUpdateManager;
use Perevorot\Rialtotender\Models\Integer;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Schema;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Config;
use Perevorot\Rialtotender\Classes\SetConfig;
use File;

class IntegerUpdate extends Command
{
    use CurrentLocale;

    /**
     * @var string The console command name.
     */
    protected $name = 'integer:update_not_work';

    /**
     * @var string The console command description.
     */
    protected $description = 'update data';
    protected $config;
	protected $env;
    protected $manager;

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
       $this->update_db();
    }

	private function update_db()
	{
	    $sites = Integer::where('is_enabled', 1)->get();
       /* $this->config = new SetConfig();
        $this->env = $this->config->setConnection(null, 'integer');

         Artisan::call('october:up');
         Artisan::call('perevorot:page:build');
         Artisan::call('cache:clear');
         Artisan::call('view:clear');
         Artisan::call('integer:up', [
             'host' => $this->env->DB_HOST,
             'port' => $this->env->DB_PORT,
             'database' => $this->env->DB_DATABASE,
             'username' => $this->env->DB_USERNAME,
             'password' => $this->env->DB_PASSWORD
         ]);
/*
         $output = '';
         $manager = IntegerUpdateManager::instance()->resetNotes()->update();

         foreach ($manager->getNotes() as $note) {
             $output .= $note . "\n\r";
         }

         $output = "updating rialto:\n\r".$output;

         $this->output->writeln($output);
         \IntegerLog::info('integer:update', 'rialto - db was updated.');
*/
        Artisan::call('integer:db_update');

        foreach($sites AS $site) {

            //$this->config = new SetConfig();
            //$this->env = $this->config->setConnection($site->theme_folder, 'integer');

            Artisan::call('integer:db_update', [
                '--theme' => $site->theme_folder,
            ]);

            foreach (File::directories(base_path() . '/integer/' . $site->theme_folder . '/storage/framework/cache') as $directory) {
                if(!is_writable($directory)) {
                    $this->output->writeln("error clear cache for {$site->theme_folder}: permissions denied!\n\r");
                    break;
                }

                File::deleteDirectory($directory);
            }
/*
            Artisan::call('integer:up', [
                'host' => $this->env->DB_HOST,
                'port' => $this->env->DB_PORT,
                'database' => $this->env->DB_DATABASE,
                'username' => $this->env->DB_USERNAME,
                'password' => $this->env->DB_PASSWORD
            ]);*/
        }
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['theme', null, InputOption::VALUE_OPTIONAL, 'An example option.', null]
        ];
    }

}
