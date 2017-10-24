<?php namespace Perevorot\Rialtotender\Console;

use DB;
use Schema;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Perevorot\Page\Models\Page;
use Perevorot\Page\Models\Menu;
use RainLab\User\Models\UserGroup;
use Symfony\Component\Console\Input\InputArgument;

class InitialData extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'integer:up';

    /**
     * @var string The console command description.
     */
    protected $description = 'Наполнение начальными данными';

	protected $env;

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
	    $this->translations();
        $this->user_groups();
        $this->menu();
        $this->homepage();
        $this->update_users();

        $this->output->writeln('Данные добавлены');
    }

	private function update_users()
	{
    	if(!Schema::hasColumn('users', 'data'))
    		DB::statement('alter table `users` add `data` longtext not null');
	}

	private function menu()
	{
		$top_menu = Menu::where('alias', 'top')->first();
        $bottom_menu = Menu::where('alias', 'bottom')->first();

        if(!$top_menu) {
            $menu=new Menu();

            $menu->alias='top';
            $menu->title='Верхнее меню';

            $menu->save();
        }

        if(!$bottom_menu) {
            $menu=new Menu();

            $menu->alias='bottom';
            $menu->title='Нижнее меню';

            $menu->save();
        }
	}

	private function user_groups()
	{
    	DB::statement('truncate table `user_groups`');

		$supplier = UserGroup::where('code', 'supplier')->first();
		$customer = UserGroup::where('code', 'customer')->first();

        if(!$supplier) {
            UserGroup::create([
                'name' => 'Поставщики',
                'code' => 'supplier',
                'description' => ''
            ]);
        }

        if(!$customer) {
            UserGroup::create([
                'name' => 'Заказчики',
                'code' => 'customer',
                'description' => ''
            ]);
        }
	}

	private function homepage()
	{
		$home_page_url = Page::where('url', '/')->first();

        if(!$home_page_url){
            $menu=Menu::where('alias', '=', 'top')->first();

            $page=new Page();

            DB::table($page->table)->insert([
                'title' => 'Главная страница',
                'layout' => 'default.htm',
                'url' => '/',
                'type' => 1,
                'menu_id' => $menu->id,
                'is_disabled' => 1,
                'created_at'=>Carbon::now(),
                'updated_at'=>Carbon::now(),
                'longread_en'=>'[{"alias":"searchForm","value":{"section":"","search_form_title":"","search_form_image":"","show_suggest":""},"index":"2","files":{"search_form_image":"__longread_one_longread_en_searchForm_search_form_image_2"}},{"alias":"registrationAndSearch","value":{"section":"","search_header":"","search_text":""},"index":"1","files":[]}]'
            ]);
        }
	}

	private function translations()
	{
        /*
		$tableName='rainlab_translate_messages';

		DB::statement('TRUNCATE TABLE '.$tableName);

		$messages=DB::table($tableName)->get();

		foreach($messages as $message)
			DB::table($tableName)->insert((array) $message);
        */
	}

	private function setConnection()
    {
        Config::set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => $this->argument('host'),
            'port' => $this->argument('port'),
            'database' => $this->argument('database'),
            'username' => $this->argument('username'),
            'password' => $this->argument('password'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ]);
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['host', InputArgument::REQUIRED, 'db_host'],
            ['port', InputArgument::REQUIRED, 'db_port'],
            ['database', InputArgument::REQUIRED, 'db_database'],
            ['username', InputArgument::REQUIRED, 'db_username'],
            ['password', InputArgument::REQUIRED, 'db_password']
        ];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }

}
