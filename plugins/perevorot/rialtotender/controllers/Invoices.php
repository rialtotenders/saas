<?php namespace Perevorot\Rialtotender\Controllers;

use Carbon\Carbon;
use Perevorot\Users\Facades\Auth;
use Illuminate\Routing\Controller;
use Perevorot\Rialtotender\Models\Setting;
use Response;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use System\Models\File;

class Invoices extends Controller
{
    private $user;
    
    public function get()
    {
        $this->user = Auth::getUser();
        
        if(!$this->user){
            return Response::make('Unauthorized', 401);
        }
        
        $settings=Setting::instance();

        if(!$settings->invoice instanceof File){
            return Response::make('No contract found', 403);
        }

        $contract_file=$settings->invoice->getLocalPath();
        $params = $this->user->attributes;
        $templateProcessor = new TemplateProcessor($contract_file);
        //$templateProcessor->setValue(array_keys($this->user->attributes), array_values($this->user->attributes));
        //$template=$templateProcessor->save();

        $number = !empty($settings->invoice_number) ? $settings->invoice_number : 0;
        $_number = $number;

        if(!$number) {
            $number = '0000';
        } else {
            switch ($number) {
                case $number < 10:
                    $number = "000$number";
                    break;
                case $number < 100:
                    $number = "00$number";
                    break;
                case $number < 1000:
                    $number = "0$number";
                    break;
            }
        }

        $number = (!empty($settings->invoice_prefix) ? $settings->invoice_prefix : '') . '-' . $number;
        $file = $number.'.docx';

        $_params = [
            'number' => $number,
            'dateFrom' => Carbon::now()->format('d.m.Y'),
            'dateTo' => Carbon::now()->addDays(3)->format('d.m.Y'),
            'sum' => '500',
            'tax' => '100',
        ];

        $params = array_merge($params, $_params);
        $templateProcessor->setValue(array_keys($params), array_values($params));
        $template=$templateProcessor->save();


        if(!$_number || $_number == 9999) {
            $settings->invoice_number = 0;
        }

        $settings->invoice_number++;
        $settings->save();

        header("Content-Description: File Transfer");
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        
        readfile($template);
        unlink($template);
    }    
}