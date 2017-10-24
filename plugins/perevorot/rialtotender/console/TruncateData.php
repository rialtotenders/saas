<?php namespace Perevorot\Rialtotender\Console;

use DB;
use Perevorot\Rialtotender\Classes\SetConfig;
use Schema;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Perevorot\Page\Models\Page;
use Perevorot\Page\Models\Menu;
use RainLab\User\Models\UserGroup;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Config;

class TruncateData extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'integer:truncate';

    /**
     * @var string The console command description.
     */
    protected $description = 'Удаление тестовых данных';

	protected $env;

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        if ($this->confirm('Вы действительно хотите очистить пользовательские данные?', false)) {
            if ($this->confirm('Данные о пользователях, добавленных тендерах, планах, вопросах, заявках будут безвозвратно удалены. Вы хотите продолжить?', false)) {
                $this->trucate_tables();
                $this->output->writeln('Данные удалены.');
            }
        }
    }

	private function trucate_tables()
	{
	    $prefix = 'perevorot_rialtotender_';
        $tables = [
            $prefix.'apilog',
            $prefix.'application_files',
            $prefix.'applications',
            $prefix.'payments',
            $prefix.'plans',
            $prefix.'questions',
            $prefix.'tender_files',
            $prefix.'tenders',
            $prefix.'contract_files',
            $prefix.'change_files',
            $prefix.'contracts',
            $prefix.'qualifications',
            $prefix.'qualification_files',
            'system_files',
            'users',
        ];

        $config = new SetConfig();
        $config->setConnection($this->option('theme'));

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach($tables as $table) {
            DB::statement('truncate table `'.$table.'`');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
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
