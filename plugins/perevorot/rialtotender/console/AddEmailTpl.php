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

class AddEmailTpl extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'integer:add_email_tpl';

    /**
     * @var string The console command description.
     */
    protected $description = 'Добавление почтовых шаблонов';

	protected $env;

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        if ($this->confirm('Вы действительно хотите добавить почтовые шаблоны?', false)) {
            $this->add_data();
            $this->output->writeln('Данные добавлены.');
        }
    }

	private function add_data()
	{
        $config = new SetConfig();
        $config->setConnection($this->option('theme'));

        $locales = DB::table('rainlab_translate_locales')
            ->whereNotNull('is_enabled')
            ->where('is_enabled', true)
            ->lists('name', 'code');
        $defLocale = DB::table('rainlab_translate_locales')
            ->where('is_default', true)
            ->value('code');

        $mail_tpls = MailTemplate::all();

        foreach($mail_tpls as $tpl) {
            if(count(explode('::', $tpl->code)) == 2) {

                foreach($locales as $code => $locale) {

                    if($code == $defLocale) { continue; }

                    $new_code = str_replace("::", ('::' . $code . '::'), $tpl->code);

                    if(!$new_tpl = MailTemplate::where('code', $new_code)->first()) {
                        $template = new MailTemplate();
                        $template->code = $new_code;
                        $template->description = $tpl->description;
                        $template->subject = $tpl->subject;
                        $template->is_custom = 1;
                        $template->layout_id = $tpl->layout_id;
                        $template->content_html = $tpl->content_html;
                        $template->content_text = $tpl->content_text;
                        $template->forceSave();
                    }
                }

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
