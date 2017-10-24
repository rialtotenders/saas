<?php namespace Perevorot\Rialtotender\Console;

use DB;
use Illuminate\Console\Command;

class MessagesExport extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'integer:messages';

    /**
     * @var string The console command description.
     */
    protected $description = 'Экспорт сообщений';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $messages=DB::table('rainlab_translate_messages')->get();

        if(empty($messages))
        {
            $this->output->writeln('Нет переводов');
            exit;
        }

        $row=array_keys(json_decode(head($messages)->message_data, true));

        $out=[
            $row
        ];

        foreach($messages as $message)
        {
            $data=json_decode($message->message_data, true);
            $s=[];

            foreach($row as $column)
            {
                $s[$column]=!empty($data[$column]) ? $data[$column] : '';
            }

            array_push($out, $s);
        }

        $fp = fopen('messages.csv', 'w');

        foreach($out as $row)
            fputcsv($fp, $row);

        fclose($fp);

        $this->output->writeln('Готово: messages.csv');
    }

	private function update_users()
	{
		DB::statement('alter table `users` add `data` longtext not null');
	}
}
