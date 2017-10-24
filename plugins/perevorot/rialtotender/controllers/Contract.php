<?php namespace Perevorot\Rialtotender\Controllers;

use Perevorot\Users\Facades\Auth;
use Illuminate\Routing\Controller;
use Perevorot\Rialtotender\Models\Setting;
use Response;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use System\Models\File;

class Contract extends Controller
{
    private $user;
    
    public function get()
    {
        $this->user = Auth::getUser();
        
        if(!$this->user){
            return Response::make('Unauthorized', 401);
        }
        
        $settings=Setting::instance();

        if(!$settings->contract instanceof File){
            return Response::make('No contract found', 403);
        }

        $contract_file=$settings->contract->getLocalPath();
        
        $templateProcessor = new TemplateProcessor($contract_file);
        $templateProcessor->setValue(array_keys($this->user->attributes), array_values($this->user->attributes));

        $template=$templateProcessor->save();
        
        $file=!empty($settings->contract_name) ? $settings->contract_name : 'contract.docx';

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