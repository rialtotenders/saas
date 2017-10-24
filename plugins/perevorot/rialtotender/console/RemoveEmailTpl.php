<?php namespace Perevorot\Rialtotender\Console;

use DB;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Schema;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Config;
use Perevorot\Rialtotender\Classes\SetConfig;
use System\Models\MailTemplate;
use RainLab\Translate\Models\Locale AS LocaleModel;

class RemoveEmailTpl extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'integer:remove_email_tpl';

    /**
     * @var string The console command description.
     */
    protected $description = 'Удаление почтовых шаблонов';

	protected $env;

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        if ($this->confirm('Вы действительно хотите удалить почтовые шаблоны?', false)) {
            $this->remove_data();
            $this->output->writeln('Данные удалены.');
        }
    }

	private function remove_data()
	{
        $config = new SetConfig();
        $config->setConnection($this->option('theme'));

        $mail_tpls = MailTemplate::all();

        foreach($mail_tpls as $tpl) {
            if(count(explode('::', $tpl->code)) > 2) {
                $tpl->delete();
            }
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
