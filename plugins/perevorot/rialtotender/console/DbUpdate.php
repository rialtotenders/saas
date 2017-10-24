<?php namespace Perevorot\Rialtotender\Console;

use DB;
use Illuminate\Support\Facades\Mail;
use Perevorot\Form\Classes\Api;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Classes\IntegerUpdateManager;
use Perevorot\Rialtotender\Classes\IntegerUpdateManagerYP;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Models\User;
use Schema;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Config;
use Perevorot\Rialtotender\Classes\SetConfig;
use File;

class DbUpdate extends Command
{
    use CurrentLocale;

    /**
     * @var string The console command name.
     */
    protected $name = 'integer:update';

    /**
     * @var string The console command description.
     */
    protected $description = 'db update';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $this->db_update();
    }

	private function db_update()
	{
        if($site = $this->option('theme')) {

            $site_name = $site;

            foreach (File::directories(base_path() . '/integer/' . $site . '/storage/framework/cache') as $directory) {
                if (!is_writable($directory)) {
                    $this->output->writeln("error clear cache for {$site_name}: permissions denied!\n\r");
                    break;
                }

                File::deleteDirectory($directory);
            }
        } else {
            $site_name = 'rialto';
        }

        $config = new SetConfig();
        $config->setConnection($site, 'integer');
        $output = '';
        $manager = IntegerUpdateManager::getInstance()->resetNotes()->update();

        foreach ($manager->getNotes() as $note) {
            $output .= $note . "\n\r";
        }

        $this->output->writeln($output);
        \IntegerLog::info('integer:db_update', $site_name . ' - db was updated.');
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
