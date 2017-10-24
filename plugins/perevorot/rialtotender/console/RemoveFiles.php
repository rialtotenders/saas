<?php namespace Perevorot\Rialtotender\Console;

use DB;
use Illuminate\Support\Facades\Mail;
use Perevorot\Form\Classes\Api;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Models\User;
use Schema;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Config;
use Perevorot\Rialtotender\Classes\SetConfig;
use System\Models\File;

class RemoveFiles extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'integer:remove_files';

    /**
     * @var string The console command description.
     */
    protected $description = 'Удаление файлов';

	protected $env;

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
       // if ($this->confirm('Вы действительно хотите удалить файлы?', false)) {
         //   if ($this->confirm('Локальные файлы будут безвозвратно удалены. Вы хотите продолжить?', false)) {
                $this->remove_files();
                //$this->output->writeln('Файлы удаленны.');
           // }
        //}
    }

	private function remove_files()
	{
        $config = new SetConfig();
        $env = $config->setConnection($this->option('theme'));
        $count_files = 0;
        $files = File::all();

        foreach($files AS $file) {

            $_file = str_replace(env('APP_URL'), base_path('integer/'.$this->option('theme')), $file->path);

            if (!file_exists($_file)) {
                $file->delete();
                $count_files++;
            }
        }

        if($count_files) {
            \IntegerLog::info('integer:remove_files', 'were deleted '.$count_files.' files.');
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
