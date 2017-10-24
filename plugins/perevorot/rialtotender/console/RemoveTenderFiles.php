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

class RemoveTenderFiles extends Command
{
    use CurrentLocale;

    /**
     * @var string The console command name.
     */
    protected $name = 'integer:remove_tender_files';

    /**
     * @var string The console command description.
     */
    protected $description = 'Удаление файлов тендера';

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
        $tenders = Tender::getData(['complete' => 1]);
        $count_files = 0;

        foreach($tenders AS $tender) {
            if(in_array($tender->status, ['complete', 'cancelled', 'unsuccessful'])) {
                foreach($tender->tenderDocuments AS $file) {
                    if($system_file = File::find($file->system_file_id)) {

                        $_file = base_path('integer').'/'.$this->option('theme').'/storage/app/'.$system_file->getDiskPath();

                        if(file_exists($_file)) {
                            if (is_writable($_file)) {
                                unlink($_file);
                                $count_files++;
                            } else {
                                \IntegerLog::info('integer:remove_tender_files', 'error delete file!');
                            }
                        }

                        $system_file->delete();
                    }

                    $file->delete();
                }
            }
        }

        if($count_files) {
            \IntegerLog::info('integer:remove_tender_files', 'were deleted '.$count_files.' files.');
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
