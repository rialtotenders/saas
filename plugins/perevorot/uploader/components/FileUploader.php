<?php namespace Perevorot\Uploader\Components;

use Illuminate\Support\Facades\Session;
use Perevorot\Rialtotender\Classes\ValidationMessages;
use Perevorot\Rialtotender\Models\ApplicationFile;
use Perevorot\Rialtotender\Models\ChangeFile;
use Perevorot\Rialtotender\Models\ContractFile;
use Perevorot\Rialtotender\Models\TenderFile;
use Perevorot\Users\Facades\Auth;
use Responsiv\Uploader\Components\FileUploader as BaseFileUploader;
use Input;
use Request;
use Response;
use Validator;
use ValidationException;
use ApplicationException;
use Exception;
use System\Models\File;

class FileUploader extends BaseFileUploader
{
    public static $messagesCode = [
        'file_upload.max',
        'file_upload.extensions'
    ];

    public $customMessages = [
        'max' => '',
        'extensions' => '',
    ];

    public $isEdit;
    public $isMultiPage;
    public $isDelete;
    public $byLot;
    public $docType;
    public $exclude;

    public function defineProperties()
    {
        $properties = [
            'placeholderText' => [
                'title'       => 'Placeholder text',
                'description' => 'Wording to display when no file is uploaded',
                'default'     => 'Click or drag files to upload',
                'type'        => 'string',
            ],
            'maxSize' => [
                'title'       => 'Max file size (MB)',
                'description' => 'The maximum file size that can be uploaded in megabytes.',
                'default'     => '5',
                'type'        => 'string',
            ],
            'fileTypes' => [
                'title'       => 'Supported file types',
                'description' => 'File extensions separated by commas (,) or star (*) to allow all types.',
                'default'     => '*',
                'type'        => 'string',
            ],
            'deferredBinding' => [
                'title'       => 'Use deferred binding',
                'description' => 'If checked the associated model must be saved for the upload to be bound.',
                'type'        => 'checkbox',
            ],
        ];

        return array_merge(
            $properties,
            [
                'is_delete' => [
                    'label' => 'Disable delete',
                ],
                'is_edit' => [
                    'label' => 'Enable edit',
                ],
                'isMultiPage' => [
                    'label' => 'Enable when more then one component on the page',
                ],
                'docType' => [
                    'label' => 'doc type',
                ],
                'byLot' => [
                    'label' => 'by lot',
                ],
                'exclude' => [
                    'label' => 'exclude',
                ],
            ]
        );
    }

    public function fileSize($size)
    {
        if(stripos($size, 'MB') === FALSE) {
            $size = explode(" ", $size);

            if($size[0] > 0) {
                $size = number_format($size[0] / 1024, 2) . ' MB';
            } else {
                $size = '0 MB';
            }
        }

        return $size;
    }

    public function onRun()
    {
        $this->addCss('assets/css/uploader.css');
        $this->addJs('assets/vendor/dropzone/dropzone.js');
        $this->addJs('assets/js/uploader.js' . ($this->isMultiPage ? '?_multi=1' : '') );

        if ($result = $this->checkUploadAction()) {
            return $result;
        }

        $this->autoPopulate();
    }

    public function init()
    {
        $this->fileTypes = $this->processFileTypes(true);
        $this->maxSize = $this->property('maxSize');
        $this->byLot = $this->property('byLot', null);
        $this->docType = $this->property('docType', 1);
        $this->isEdit = $this->property('is_edit');
        $this->isDelete = $this->property('is_delete');
        $this->isMultiPage = $this->property('isMultiPage');
        $this->exclude = $this->property('exclude');
    }

    public function onEditAttachment()
    {
        $old_file_id = post('file_id');

        if(!$old_file_id) {
            return false;
        }

        switch (post('document_type')) {
            case 'tender':
                $old_file = TenderFile::where('system_file_id', '=', $old_file_id)->first();
                break;
            case 'application':
                $old_file = ApplicationFile::where('system_file_id', '=', $old_file_id)->first();
                break;
            case 'contract':
                $old_file = ContractFile::where('system_file_id', '=', $old_file_id)->first();
                break;
            case 'change':
                $old_file = ChangeFile::where('system_file_id', '=', $old_file_id)->first();
                break;
            case 'qualification':
                $old_file = QualificationFile::where('system_file_id', '=', $old_file_id)->first();
                break;
        }

        if (!$old_file) {
            return false;
        }

        //$old_file->delete();
        //$new_file_id = Session::get('uploader.new_file');

        if (!empty(post('new_file_id'))) {
            $old_file->change_system_file_id=post('new_file_id');//Session::get('uploader.new_file');
            $old_file->save();

            $object = in_array(Input::get('document_type'), ['contract', 'change']) ? 'contract' : 'tender';
            $log[] = Input::get('document_type').' - '.$old_file->file_name;
            $log[] = 'user - '.Auth::getUser()->id.'('.Auth::getUser()->email.', '.Auth::getUser()->username.')';
            $log[] = Input::has('tenderID') ? ($object . ' - ' . Input::get('tenderID')) : '';
            \IntegerLog::info('uploader.file.edit: '.implode('; ', $log));

            return Response::json(['edit' => $old_file->id], 200);
        }
    }

    public function onRemoveAttachment()
    {
        if (($file_id = post('file_id')) && ($file = File::find($file_id))) {

            $object = in_array(Input::get('document_type'), ['contract', 'change']) ? 'contract' : 'tender';
            $log[] = Input::get('document_type').' - '.$file->file_name;
            $log[] = 'user - '.Auth::getUser()->id.'('.Auth::getUser()->email.', '.Auth::getUser()->username.')';
            $log[] = Input::has('tenderID') ? ($object . ' - ' . Input::get('tenderID')) : '';
            \IntegerLog::info('uploader.file.remove: '.implode('; ', $log));

            $this->model->{$this->attribute}()->remove($file);
            Response::json(['delete' => $file_id], 200);
        }
    }

    protected function checkUploadAction()
    {
        if (!($uniqueId = Request::header('X-OCTOBER-FILEUPLOAD')) || $uniqueId != $this->alias) {
            return;
        }

        try {
            if (!Input::hasFile('file_data')) {
                throw new ApplicationException('File missing from request');
            }

            $uploadedFile = Input::file('file_data');
            $validationRules = ['max:'.$this->maxSize * 1024];

            if ($fileTypes = $this->processFileTypes()) {
                $validationRules[0] .= '|extensions:'.$fileTypes;
            }

            $validation = Validator::make(
                ['file_upload' => $uploadedFile],
                ['file_upload' => $validationRules],
                ValidationMessages::generateCustomMessages($this->customMessages, 'file_upload')
            );

            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            if (!$uploadedFile->isValid()) {
                throw new ApplicationException(sprintf('File %s is not valid.', $uploadedFile->getClientOriginalName()));
            }


            $fileModel = $this->model->getRelationDefinition($this->attribute)[0];

            $file = new $fileModel;
            $file->data = $uploadedFile;
            $file->is_public = true;
            $file->lot_id = $this->byLot;
            $file->doc_type = !is_array($this->docType) ? $this->docType : null;
            $file->user_id = Auth::getUser()->id;
            $file->save();

            $this->model->{$this->attribute}()->add($file, $this->getSessionKey());

            $file = $this->decorateFileAttributes($file);

            $result = [
                'id' => $file->id,
                'thumb' => $file->thumbUrl,
                'path' => $file->pathUrl
            ];

            $object = in_array(Input::get('document_type'), ['contract', 'change']) ? 'contract' : 'tender';
            $log[] = Input::get('document_type').' - '.$file->file_name;
            $log[] = 'user - '.Auth::getUser()->id.'('.Auth::getUser()->email.', '.Auth::getUser()->username.')';
            $log[] = Input::has('tenderID') ? ($object . ' - ' . Input::get('tenderID')) : '';
            \IntegerLog::info('uploader.file.upload: '.implode('; ', $log));

            return Response::json($result, 200);

        }
        catch (Exception $ex) {
            return Response::json($ex->getMessage(), 400);
        }
    }

    public function getFileList()
    {
        if (!is_string($this->attribute)) {
            throw new ApplicationException(sprintf('Attribute name must be a string, %s was passed.', gettype($this->attribute)));
        }

        /*
         * Use deferred bindings
         */
        if ($sessionKey = $this->getSessionKey()) {
            $list = $deferredQuery = $this->model
                ->{$this->attribute}()
                ->withDeferred($sessionKey)
                ->where('lot_id', $this->byLot)
                ->whereIn('doc_type', !is_array($this->docType) ? [$this->docType] : $this->docType)
                ->where('user_id', Auth::getUser()->id)
                ->orderBy('id', 'desc')
                ->get();
        }
        else {
            $list = $this->model
                ->{$this->attribute}()
                ->where('lot_id', $this->byLot)
                ->whereIn('doc_type', !is_array($this->docType) ? [$this->docType] : $this->docType)
                ->where('user_id', Auth::getUser()->id)
                ->orderBy('id', 'desc')
                ->get();
        }

        if (!$list) {
            $list = new Collection;
        } else {
            if($this->model->bidDocuments) {
                $_changed_docs = $this->model->bidDocuments->filter(function($doc, $dkey) {
                    return $doc->change_system_file_id > 0;
                });

                $sort_key = 0;

                foreach($list AS $lk => $doc) {
                    $file = array_first($_changed_docs, function($cdoc, $dkey) use($doc) {
                        return $cdoc->change_system_file_id > 0 && $cdoc->system_file_id == $doc->id;
                    });

                    if($file) {
                        $sort_key++;
                        $sort_key2 = null;
                        $doc->for_change = $sort_key;
                        $doc->changed = true;

                        foreach($list AS $dkey => $cdoc) {
                            if($file->change_system_file_id == $cdoc->id) {
                                $sort_key++;
                                $sort_key2 = $dkey;
                                break;
                            }
                        }

                        if($sort_key2 !== null) {
                            $list[$sort_key2]->not_replace = true;
                            $list[$sort_key2]->for_change = $sort_key;
                        }

                    } else {
                        $doc->for_change = !$doc->for_change ? 0 : $doc->for_change;
                    }
                }

                if($_changed_docs) {
                    $list = $list->sortByDesc(function ($l) {
                        return $l->for_change;
                    });
                }
            }
        }

        /*
         * Decorate each file with thumb
         */
        $list->each(function($file) {
            $this->decorateFileAttributes($file);
        });

        return $list;
    }
}
