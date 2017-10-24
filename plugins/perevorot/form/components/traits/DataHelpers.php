<?php

namespace Perevorot\Form\Components\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Perevorot\Form\Classes\Api;
use Perevorot\Rialtotender\Models\ComplaintPeriod;
use Perevorot\Rialtotender\Models\Qualification;
use Perevorot\Rialtotender\Models\Question;
use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;
use Perevorot\Rialtotender\Classes\ValidationMessages;
use Illuminate\Support\Facades\Session;
use Perevorot\Rialtotender\Models\Tender;

trait DataHelpers
{
    use ContractsValidator;

    public function getQualifications() {

        $qualifications = Qualification::getData([
            'user_id' => $this->user->id,
            'is_test' => $this->user->is_test,
            'tender_id' => $this->user_tender->tender_system_id,
            'lot_id' => $this->lot ? $this->lot->id : null,
        ]);

        $_qualifications = [];

        if($this->lot) {
            $__qualifications = $this->lot->__qualifications;
        } else {
            $__qualifications = $this->item->__qualifications;
        }

        if(count($qualifications) > 0) {
            foreach($qualifications AS $qualification) {
                $_key = null;
                $item_q = array_first($__qualifications, function ($__q, $key) use ($qualification, &$_key) {
                    $_key = $key;
                    return $__q->id != $qualification->qualification_id;
                });

                if($item_q) {
                    $_qualifications[] = $item_q;
                    unset($__qualifications[$_key]);
                }
            }
        }

        if(count($__qualifications) > 0) {
            $_qualifications = array_merge($_qualifications, $__qualifications);
        }

        foreach($_qualifications AS $_q) {
            $qualification = Qualification::getData([
                'qid' => $_q->id,
                'user_id' => $this->user->id,
                'is_test' => $this->user->is_test,
                'tender_id' => $this->user_tender->tender_system_id,
                'lot_id' => $this->lot ? $this->lot->id : null,
                'limit' => 1,
            ]);

            if(!$qualification instanceof Qualification) {
                $q = new Qualification();
                $q->user_id = $this->user->id;
                $q->is_test = $this->user->is_test;
                $q->tender_id = $this->user_tender->tender_system_id;
                $q->qualification_id = $_q->id;
                $q->status = $_q->status;
                $q->lot_id = isset($_q->lotID) ? $_q->lotID : null;
                $q->save();
            }
        }

        if(count($_qualifications) > 0) {
            $qualifications = Qualification::getData([
                'user_id' => $this->user->id,
                'is_test' => $this->user->is_test,
                'tender_id' => $this->user_tender->tender_system_id,
                'lot_id' => $this->lot ? $this->lot->id : null,
            ]);
        }

        return $qualifications;
    }

    public function onUpdateTender() {
        $api = new Api($this->gos_tender);

        if($api->updateTender($this->user_tender, 'active.pre-qualification.stand-still')) {
            $this->user_tender->is_close_qualification = 1;
            $this->user_tender->save();

            return [
                '#qualification_submit_msg' => $this->renderPartial('@messages/qualification_next_success'),
            ];
        }
    }

    public function onUpdateQualification() {
        $data = post();

        if(!empty($data)) {
            switch ($data['type']) {
                case 1:
                    $status = 'active';
                    break;
                case 2:
                    $status = 'unsuccessful';
                    break;
                case 3:
                case 4:
                    $status = 'cancelled';
                    break;
            }
        }

        if(isset($status) && isset($data['id'])) {

            $api = new Api($this->gos_tender);
            $q = Qualification::getData([
                'qid' => $data['id'],
                'user_id' => $this->user->id,
                'is_test' => $this->user->is_test,
                'tender_id' => $this->user_tender->tender_system_id,
                'lot_id' => $this->lot ? $this->lot->id : null,
                'limit' => 1,
            ]);

            if ($q instanceof Qualification && $api->submitQualification($this->user_tender, $q, $status)) {

                $qualification = array_first($this->item->__qualifications, function($qv, $qk) use($data) {
                    return $qv->id == $data['id'];
                });

                Event::fire('perevorot.form.qualification_chose', [
                    'tender' => $this->user_tender,
                    'status' => $status,
                    'qualification' => $qualification,
                ], true);

                return [
                    'id' => $data['id'],
                    'q_success' => 1,
                    '#qualification_submit_msg' => $this->renderPartial('@messages/qualification_success'),
                ];
            }
        }

        return [
            'id' => $data['id'],
            'q_success' => 0,
            '#qualification_submit_msg' => $this->renderPartial('@messages/qualification_error'),
        ];
    }

    public function onAwardSubmit()
    {
        $award_id = post('id');

        switch (post('type'))
        {
            case 1:
                $status = 'active';
                $_status = 'pending';
                break;
            case 2:
                $status = 'unsuccessful';
                $_status = 'pending';
                break;
            case 3:
                $status = 'cancelled';
                $_status = 'active';
                break;
        }

        if($status && $award_id) {

            $api = new Api($this->gos_tender);

            if($api->submitAward($this->user_tender, post(), $status)) {

                $award = array_first($this->item->awards, function($v, $k) use($award_id) {
                    return $v->id == $award_id;
                });

                Event::fire('perevorot.form.award_chose', [
                    'tender' => $this->user_tender,
                    'status' => $status,
                    'award' => $award,
                ], true);

                if(!$this->lot) {
                    Session::put("tender.{$this->user_tender->tender_system_id}.award_id", $award_id);
                    Session::put("tender.{$this->user_tender->tender_system_id}.award_status", $_status);
                } else {
                    Session::put("tender.{$this->user_tender->tender_system_id}.lot.{$this->lot->id}.award_id", $award_id);
                    Session::put("tender.{$this->user_tender->tender_system_id}.lot.{$this->lot->id}.award_status", $_status);
                }
            }
        }
    }

    public function onQuestion()
    {
        if($this->user)
        {
            $data = [
                'question' => post('question'),
                'title' => post('title'),
                'tender_id' => post('tender_id'),
            ];

            foreach($data AS $k => $v)
            {
                if(!$v)
                {
                    $errors[] = $k;
                }
            }

            if(!empty(post('q_type')) && post('q_type') == 'lot' && !empty(post('q_lot'))) {
                $data['lot'] = post('q_lot');
            } else {
                $data['lot'] = null;
            }

            if (isset($errors) && !empty($errors))
            {
                return ['error_field' => $errors, 'result' => 0];
            }
            else
            {
                $q = new Question();
                $q->title = $data['title'];
                $q->question = $data['question'];
                $q->tender_id = $this->item->id;
                $q->lot_id = $data['lot'];
                $q->user_id = $this->user->id;
                $q->created_at = Carbon::now();

                if ($q->save())
                {
                    $api = new Api($this->gos_tender);

                    if ($r = $api->sendQuestion($q, $this->item))
                    {
                        $t = explode('/', $r);

                        if ($qid = end($t))
                        {
                            $q->qid = $qid;
                            $q->save();

                            if($q->tender instanceof Tender) {
                                Event::fire('perevorot.form.question_created', [
                                    'question' => $q,
                                ], true);
                            }
                        }
                    }

                    return [
                        'question_submit' => $this->renderPartial('@messages/question_success')
                    ];
                }

            }
        }

        return [
            'question_submit' => $this->renderPartial('@messages/question_error')
        ];
    }

    /**
     * @param $step
     * @return mixed
     */
    private function getText($step)
    {
        return ($this->messages ? $this->messages->{'step' . $step} : '');
    }

    /**
     * @param $step
     * @return mixed
     */
    private function getHeader($step)
    {
        return ($this->messages ? $this->messages->{'header' . $step} : '');
    }

    public function onContractSubmit()
    {
        if(!empty(post())) {
            if(post('type') == 2) {
                if($complaintPeriod = ComplaintPeriod::where('procurement', $this->item->procurementMethodType)->first()) {

                    if($this->lot) {
                        $item_complaintPeriod = $this->lot->__active_award->complaintPeriod->endDate;
                    } else {
                        $item_complaintPeriod = $this->item->__active_award->complaintPeriod->endDate;
                    }

                    if(Carbon::now()->timestamp < Carbon::createFromTimestamp(strtotime($item_complaintPeriod))->addDays($complaintPeriod->days)->timestamp) {
                        return [
                            '#contract-error' => $this->renderPartial('@messages/contract_access_activate_error')
                        ];
                    }
                }

                if(!$this->user_contract) {
                    return [
                        '#contract-error' => $this->renderPartial('@messages/contract_access_activate_error')
                    ];
                }

            } else {

                $validator = Validator::make(post(), $this->rules, ValidationMessages::generateCustomMessages($this->customMessages, 'contract'));

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }
            }

            $api = new Api($this->gos_tender);
            $post = post();
            $post['lot_id'] = $this->lot ? $this->lot->id : null;

            if($api->submitContract($this->user_tender, $this->user_contract, $post)) {

                if($this->user_contract->status == 'active') {
                    Event::fire('perevorot.form.tender_contract', [
                        'tender' => $this->user_tender,
                        'type' => 'activated',
                        'contract' => $this->lot ? array_shift($this->lot->__contracts) : array_shift($this->item->__contracts),
                    ], true);
                } else {
                    Event::fire('perevorot.form.tender_contract', [
                        'tender' => $this->user_tender,
                        'type' => 'updated',
                        'contract' => $this->lot ? array_shift($this->lot->__contracts) : array_shift($this->item->__contracts),
                    ], true);
                }

                return true;
            }
        }

        return [
            '#contract-error' => $this->renderPartial('@messages/contract_access_error')
        ];
    }

    public function onRefreshFiles()
    {
        $this->pageCycle();
    }

    public function onAnswering()
    {
        if($this->user_tender && !empty(post()))
        {
            $data['qid'] = post('qid');
            $data['answer'] = post('answer');

            $api = new Api($this->gos_tender);

            if ($r = $api->sendAnswer($data, $this->user_tender)) {
                $q = Question::getData(['lot_id' => (!empty(post('lot_id')) ? post('lot_id') : null), 'qid' => $data['qid'], 'tender_id' => $this->user_tender->tender_system_id]);

                if(count($q) > 0) {
                    $q = $q[0];
                    $q->is_answered = 1;
                    $q->save();

                    if($q->tender instanceof Tender) {
                        Event::fire('perevorot.form.answer_created', [
                            'question' => $q,
                        ], true);
                    }
                }

                return [
                    'qid' => $data['qid'],
                    'answer_success' => 1,
                    'answer_submit' => $this->renderPartial('@messages/answer_success'),
                ];
            }
        }

        return [
            'answer_success' => 0,
            'qid' => post('qid'),
            'answer_submit' => $this->renderPartial('@messages/answer_error'),
        ];
    }

    public function getQuestions($type = false)
    {
        $questions = Question::getData(['tender_id' => $this->item->id, 'lot_id' => ($type == 'lot' && $this->lot ? $this->lot->id : false)]);
        $_questions = [];

        if($this->lot && $type == 'lot') {
            $_item_q = isset($this->lot->__questions) ? $this->lot->__questions : [];
        } elseif($type == 'tender') {
            $_item_q = isset($this->item->__questions) ? $this->item->__questions : [];
        } else {
            $_item_q = isset($this->item->questions) ? $this->item->questions : [];
        }

        foreach ($questions AS $k => $q) {

            $_questions[$k] = new \stdClass();
            $_questions[$k]->title = $q->title;
            $_questions[$k]->description = $q->question;
            $_questions[$k]->date = $q->created_at;
            $_questions[$k]->_date = strtotime($q->created_at);
            $_questions[$k]->answer = $q->answer;
            $_questions[$k]->qid = $q->qid;
            $_questions[$k]->lot_id = $q->lot_id;
            $_questions[$k]->lot_title = '';
            $_questions[$k]->item_title = '';
            $_questions[$k]->is_answered = $q->is_answered;
            //$_questions[$k]->other = false;

            $none = false;

            if (!empty($_item_q)) {

                foreach ($_item_q as $qk => $q2) {

                        if ($q->qid == $q2->id && (($type == 'lot' && $this->lot) || ($type == false) || ($type == 'tender' && !$q->lot_id))) {
                            $none = false;
                            $_questions[$k]->author = isset($q2->author) ? $q2->author : null;
                            $_questions[$k]->qid = $q2->id;
                            $_questions[$k]->answer = isset($q2->answer) ? $q2->answer : false;
                            $_questions[$k]->dateAnswered = isset($q2->dateAnswered) ? $q2->dateAnswered : false;

                            if(isset($this->item->lots) && $type == false && isset($q2->relatedItem)) {

                                $qlot = array_first($this->item->lots, function($lot, $lkey) use($q2) {

                                    if($q2->questionOf == 'item') {
                                        $__q = array_first($this->item->items, function ($item, $ikey) use ($q2) {
                                            return $item->id == $q2->relatedItem;
                                        });

                                        if($__q && isset($__q->relatedLot)) {
                                            return $lot->id == $__q->relatedLot;
                                        }
                                    }
                                    elseif($q2->questionOf == 'lot') {
                                        return $lot->id == $q2->relatedItem;
                                    }
                                });

                                if($qlot) {
                                    $_questions[$k]->lot_title = $qlot->title;
                                }
                            }

                            unset($_item_q[$qk]);
                            break;
                        } elseif ($q->qid == $q2->id && ($type == 'lot' && $this->lot && $q->lot_id != $q2->relatedItem)) {
                            continue;
                        }

                        $none = true;
                    }
            } else {
                //unset($_questions[$k]);
                //continue;
                $none = true;
            }

            if ($none) {
                $_questions[$k]->qid = false;
                $_questions[$k]->answer = false;
            }
        }

        if (!empty($_item_q)) {

            $index = count($_questions);

            foreach ($_item_q as $qk => $q2) {

                if(($type == 'lot' && $this->lot && in_array($q2->questionOf, ['lot', 'item']) && isset($q2->relatedItem)) || ($type == false) || ($type == 'tender' && in_array($q2->questionOf, ['tender', 'item']))) {

                    $_questions[$index] = new \stdClass();
                    $_questions[$index]->author = isset($q2->author) ? $q2->author : null;
                    $_questions[$index]->title = $q2->title;
                    $_questions[$index]->description = $q2->description;
                    $_questions[$index]->date = $q2->date;
                    $_questions[$index]->_date = strtotime($q2->date);
                    $_questions[$index]->answer = isset($q2->answer) ? $q2->answer : false;
                    $_questions[$index]->is_answered = isset($q2->answer);
                    $_questions[$index]->dateAnswered = isset($q2->dateAnswered) ? $q2->dateAnswered : false;
                    $_questions[$index]->qid = $q2->id;
                    //$_questions[$index]->other = true;
                    $_questions[$index]->lot_title = '';
                    $_questions[$index]->item_title = '';
                    $_questions[$index]->lot_id = isset($q2->relatedItem) ? $q2->relatedItem : false;

                    if(isset($this->item->lots) && $type == false && isset($q2->relatedItem)) {

                        $qlot = array_first($this->item->lots, function($lot, $lkey) use($q2, $_questions, $index) {

                            if($q2->questionOf == 'item') {
                                $__q = array_first($this->item->items, function ($item, $ikey) use ($q2) {
                                    return $item->id == $q2->relatedItem;
                                });

                                if($__q && isset($__q->relatedLot)) {
                                    $_questions[$index]->lot_id = $__q->relatedLot;
                                    $_questions[$index]->item_title = $__q->description;
                                    return $lot->id == $__q->relatedLot;
                                }
                            }
                            elseif($q2->questionOf == 'lot') {
                                return $lot->id == $q2->relatedItem;
                            }
                        });

                        if($qlot) {
                            $_questions[$index]->lot_title = $qlot->title;
                        }
                    }

                    $index++;
                }
            }
        }

        if(!empty($_questions)) {
            $data_year = array();

            foreach ($_questions as $key => $arr) {
                $data_year[$key] = $arr->_date;
            }

            array_multisort($data_year, SORT_NUMERIC, $_questions);
        }

        return $_questions;
    }
}
