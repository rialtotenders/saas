<?php namespace Perevorot\Rialtotender\Console;

use DB;
use Illuminate\Support\Facades\Mail;
use Perevorot\Form\Classes\Api;
use Perevorot\Form\Classes\Parser;
use Perevorot\Page\Models\Page;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\Contract;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Models\User;
use Schema;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Config;
use Perevorot\Rialtotender\Classes\SetConfig;
use System\Models\File;

class RemoveFilesWOUser extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'integer:remove_empty_files';

    /**
     * @var string The console command description.
     */
    protected $description = 'Удаление файлов без user_id';

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
        $config->setConnection($this->option('theme'));

        /*
        $count_files = File::whereNull('user_id')
            ->whereIn('attachment_type', [
                'Perevorot\Rialtotender\Models\Application',
                'Perevorot\Page\Models\Page',
                'Perevorot\Rialtotender\Models\Contract'
            ])->count();

        File::whereNull('user_id')
            ->whereIn('attachment_type', [
                'Perevorot\Rialtotender\Models\Application',
                'Perevorot\Page\Models\Page',
                'Perevorot\Rialtotender\Models\Contract'
            ])->delete();
        */

        $ids = Application::all('id')->lists('id');

        File::where('attachment_type', 'Perevorot\Rialtotender\Models\Application')
            ->whereNotIn('attachment_id', $ids)->delete();

        $ids = Page::all('id')->lists('id');

        File::where('attachment_type', 'Perevorot\Page\Models\Page')
            ->whereNotIn('attachment_id', $ids)->delete();

        $ids = Contract::all('id')->lists('id');

        File::where('attachment_type', 'Perevorot\Rialtotender\Models\Contract')
            ->whereNotIn('attachment_id', $ids)->delete();

        $ids = Tender::all('id')->lists('id');

        File::where('attachment_type', 'Perevorot\Rialtotender\Models\Tender')
            ->whereNotIn('attachment_id', $ids)->delete();

        /*
        foreach($files AS $file) {
            $file->delete();
            $count_files++;
        }*/

        /*
        if($count_files) {
            \IntegerLog::info('integer:remove_empty_files', 'were deleted '.$count_files.' files.');
        } else {
            \IntegerLog::info('integer:remove_empty_files', 'nothing to deleted.');
        }
        */
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
