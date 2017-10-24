<?php namespace Perevorot\Users\Components;

use Perevorot\Form\Classes\Api;
use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;
use Cms\Classes\ComponentBase;
use Illuminate\Support\Facades\Request;
use Perevorot\Form\Traits\ContractPageValidator;
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

class ContractFiles extends ComponentBase
{
    use CurrentLocale, UserSetting;

    private $sessionKey;
    private $user_contract;
    private $user;
    private $user_mode;
    private $setting;
    private $siteLocale;

    public function componentDetails()
    {
        return [
            'name'        => 'Contract files component',
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
        $this->siteLocale = $this->getCurrentLocale();

        if(!$this->user) {
            return false;
        }

        $this->user_mode = $this->checkUserMode($this->user);
        $contract_id = $this->param('contractID');
        $this->user_contract = Contract::getData(['cid' => $contract_id, 'limit' => 1, 'user_id' => $this->user->id, 'is_test' => $this->user->is_test]);

        if(!$this->user_contract) {
            return false;
        }

        if (empty(post())) {
            $this->user_contract->clearChangeDocuments('otherAllActiveContractDocuments');
        }

        $component = $this->addComponent(
            'Perevorot\Uploader\Components\FileUploader',
            'fileUploader_contractDocuments',
            [
                'is_edit' => true,
                'is_delete' => true,
                'maxSize' => @$this->setting->value['max_file_size'],
                'deferredBinding' => true,
                'fileTypes' => @$this->setting->value['file_types'],
            ]
        );
        $component->bindModel('otherActiveContractDocuments', $this->user_contract);
    }

    public function onRun()
    {
        if(isset($_GET['dump']) && getenv('APP_ENV')=='local') {
            dd($this->user_contract, $this->user_contract->otherAllActiveContractDocuments, $this->user_contract->otherActiveContractDocuments);
        }
    }

    public function onRender()
    {
        if(!$this->user_contract) {
            return $this->renderPartial('@messages/contract_error');
        }

        if($this->user_contract->status != 'active') {
            return redirect()->back();
        }

        return $this->renderPartial('@contractfiles/index', [
            'siteLocale' => $this->getCurrentLocale(),
            'user_contract' => $this->user_contract,
            'c_document_types' => Procurement::getData('document_types'),
            'session_key_field' => $this->sessionKey,
        ]);
    }

    public function onContractDocumentsSubmit() {
        $post = post();

        if(!empty($post)) {

            $api = new Api();
            $tender = Tender::getData(['tender_system_id' => $this->user_contract->tender_id, 'test' => $this->user->is_test, 'user_id' => $this->user->id, 'limit' => 1]);

            if(!$this->user_contract->token_id) {
                $api->getContractToken($tender, $this->user_contract);
            }

            if($api->uploadNewDocumentsForActiveContract($this->user_contract, $tender, true)) {
                $api->uploadChangedDocumentsForActiveContract($this->user_contract, $tender, true);
                $this->user_contract->save(null, post('_session_key'));

                return redirect()->to($this->siteLocale.'contract/'.$this->user_contract->cid);
            }
        }

        return [
            '#contract-documents-error' => $this->renderPartial('@messages/contract_document_access_error')
        ];
    }

}
