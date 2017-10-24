<?php namespace Perevorot\Form\Components;

use Illuminate\Support\Facades\Event;
use Perevorot\Form\Classes\Api;
use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;
use Cms\Classes\ComponentBase;
use Illuminate\Support\Facades\Request;
use Perevorot\Form\Components\Traits\ContractPageValidator;
use Perevorot\Rialtotender\Classes\ValidationMessages;
use Perevorot\Rialtotender\Models\Area;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\Procurement;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Users\Facades\Auth;
use Perevorot\Rialtotender\Models\Status;
use Perevorot\Rialtotender\Traits\AccessToTenders;
use Illuminate\Http\RedirectResponse;
use Perevorot\Rialtotender\Models\Contract;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Traits\UserSetting;
use Perevorot\Rialtotender\Models\Setting;
use October\Rain\Support\Facades\Form;

class ContractPage extends ComponentBase
{
    use CurrentLocale, UserSetting, ContractPageValidator;

    private $sessionKey;
    private $user_contract;
    private $contract;
    private $user;
    private $user_mode;
    private $setting;

    public function componentDetails()
    {
        return [
            'name'        => 'Contract page component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [
        ];
    }

    public function init()
    {
        $this->sessionKey = Form::sessionKey();
        $this->setting = Setting::instance();
        $this->user = Auth::getUser();
        $this->user_mode = $this->checkUserMode($this->user);
        $contract_id = $this->param('contractID');
        $this->contract = Contract::where('cid', $contract_id)->first();

        if(!$this->contract) {
            $this->contract = false;
        }

        if($this->user && $this->contract) {
            $this->user_contract = $this->contract->user_id == $this->user->id ? $this->contract : false;

            if($this->user_contract) {

                if (empty(post())) {
                    $this->user_contract->clearChangeDocuments('allActiveContractDocuments');
                }

                $component = $this->addComponent(
                    'Perevorot\Uploader\Components\FileUploader',
                    'fileUploader_contracts',
                    [
                        'is_edit' => true,
                        'is_delete' => true,
                        'maxSize' => @$this->setting->value['max_file_size'],
                        'deferredBinding' => true,
                        'fileTypes' => @$this->setting->value['file_types'],
                    ]
                );
                $component->bindModel('activeContractDocuments', $this->user_contract);
            }
        }

        if($this->contract) {
            $this->contract = json_decode($this->getContract($this->contract->contract_id));

            if (isset($this->contract->data)) {
                $this->contract = $this->contract->data;
                $this->contract->__tender_documents = new \stdClass();

                if(isset($this->contract->documents)) {
                    $this->contract->__tender_documents = array_where($this->contract->documents, function($doc, $key) {
                        return !isset($doc->relatedItem);
                    });
                }
            } else {
                $this->contract = false;
            }
        }
    }

    public function onRun()
    {
        if(isset($_GET['dump']) && getenv('APP_ENV')=='local') {
            dd($this->contract, $this->user_contract);
        }
    }

    public function onRender()
    {
        if(!$this->contract) {
            return $this->renderPartial('@messages/contract_error');
        }

        if($this->user_contract) {
            $this->addJs('assets/js/tender-validation.js');
        }

        $contract_documents = [];

        if(isset($this->contract->documents)) {
            $contract_documents = array_where($this->contract->documents, function($document, $key) {
                if(isset($document->relatedItem)) {
                    return $document->relatedItem == $this->contract->id;
                }
            });

            $this->change_documents($contract_documents);
        }

        $changes_documents = [];

        if(isset($this->contract->changes)) {
            foreach($this->contract->changes as $change) {
                $changes_documents[$change->id] = array_where($this->contract->documents, function ($document, $key) use($change) {
                    return $document->documentOf == 'change' and $document->relatedItem == $change->id;
                });
                $this->change_documents($changes_documents[$change->id]);
            }
        }

        return $this->renderPartial('@contract.htm', [
            'changes_documents' => $changes_documents,
            'contract_documents' => $contract_documents,
            'siteLocale' => $this->getCurrentLocale(),
            'contract' => $this->contract,
            'user_contract' => $this->user_contract,
            'contracts_status' => Status::getStatuses('contract'),
            'changes_status' => Status::getStatuses('change'),
            'rationaletypes' => Procurement::getData('rationaletypes'),
            'session_key_field' => $this->sessionKey,
        ]);
    }

    private function change_documents(&$documents)
    {
        usort($documents, function ($a, $b)
        {
            return intval(strtotime($b->dateModified))>intval(strtotime($a->dateModified));
        });

        $ids=[];

        foreach($documents as $document)
        {
            if(in_array($document->id, $ids))
            {
                $document->stroked=new \StdClass();
                $document->stroked=true;
            }

            $ids[]=$document->id;
        }
    }

    public function onContractSubmit()
    {
        $post = post();

        if(!empty($post)) {

            if($post['type'] == 1) {
                unset($this->rules['terminationDetails']);
            }

            if($post['type'] != 3) {
                $validator = Validator::make($post, $this->rules, ValidationMessages::generateCustomMessages($this->customMessages, 'contract'));

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }
            }

            $api = new Api();
            $tender = Tender::getData(['tender_system_id' => $this->user_contract->tender_id, 'test' => $this->user->is_test, 'user_id' => $this->user->id, 'limit' => 1]);

            if($api->updateContract($tender, $this->user_contract, $post)) {

               if($post['type'] == 3) {
                   Event::fire('perevorot.form.contract_terminated', [
                       'contract' => $this->user_contract,
                       'item' => $this->contract,
                   ], true);
               }

               return true;
            }
        }

        return [
            '#contract-error' => $this->renderPartial('@messages/contract_access_error')
        ];
    }

    private function getContract($id)
    {
        if(empty($id))
            return '';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if(env('API_'.$this->user_mode.'LOGIN') && env('API_'.$this->user_mode.'PASSWORD')){
            curl_setopt($ch, CURLOPT_USERPWD, env('API_'.$this->user_mode.'LOGIN') . ":" . env('API_'.$this->user_mode.'PASSWORD'));
        }

        \IntegerLog::info('contract.search.id', $id);

        $path=env('API_'.$this->user_mode.'CONTRACT').'/'.$id;

        if(isset($_GET['api']) && getenv('APP_ENV')=='local')
            dd($path);

        curl_setopt($ch, CURLOPT_URL, $path);

        $result=curl_exec($ch);

        curl_close($ch);

        return $result;
    }
}
