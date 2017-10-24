<?php

namespace Perevorot\Rialtotender\Controllers;

use Backend\Facades\BackendAuth;
use Illuminate\Routing\Controller;
use October\Rain\Exception\ApplicationException;
use Perevorot\Rialtotender\Classes\BBApi;
use Excel;
use Request;
class GitReport extends Controller
{
    public $api;
    public $ws_tasks;

    public function __construct()
    {
        $this->api = new BBApi();
    }

    public function get()
    {
        if(!BackendAuth::getUser()) {
            abort(404, 'error!');
        }

        $commits = [];
        $page = 1;
        $data = $this->api->getDiffDevMaster($page);
        $hashes = [];
        $pr_hashes = [];

        while(isset($data->values) && sizeof($data->values) > 0) {
            foreach ($data->values AS $k => $value) {

                $hashes[] = $value->hash;

                if (stripos($value->message, 'pull request #') !== FALSE) {

                    preg_match('|[#]\d+?[)]|iU', $value->message, $result);

                    if (!isset($result[0])) {
                        $pr_id = null;
                    } else {
                        $pr_id = trim(strtr($result[0], ['#' => '', ')' => '']));
                    }

                    if ($pr_id && $pr = $this->api->getMergedPR($pr_id)) {

                        $pr_commits = $this->api->getMergedPR($pr_id, true);

                        foreach ($pr_commits->values AS $pr_commit) {

                            $pr_hashes[] = $pr_commit->hash;
                            $msg = trim($pr_commit->message);
                            $author = $pr_commit->author->raw;

                            if (stripos($msg, 'merge') !== FALSE) {
                                continue;
                            }

                            if (strpos($author, '<')) {
                                $author = trim(substr($author, 0, strpos($author, '<')));
                            }

                            if (stripos($msg, 'http') !== FALSE) {
                                preg_match('|http(.*)|i', $msg, $result);

                                if (isset($result[0])) {
                                    $link = trim($result[0]);
                                    $msg = trim(strtr($msg, [$link => '', ' -' => '']));
                                }
                            } else {
                                $link = '';
                            }

                            if(stripos($link, 'worksection') !== FALSE) {

                                if(empty($this->ws_tasks)) {
                                    $this->ws_tasks = $this->api->getWSTasks();
                                }

                                $task = $this->searchWSTask($link);
                                $task_name = $task['name'];
                                $task_tags = $task['tags'];
                                $task_status = $task['status'];
                            } else {
                                $task_name = '';
                                $task_tags = '';
                                $task_status = '';
                            }

                            $commits[$pr_commit->hash] = [
                                'author' => $author,
                                'commit' => $msg,
                                'branch' => $pr->source->branch->name,
                                'task_link' => $link,
                                'task_name' => $task_name,
                                'task_status' => $task_status,
                                'task_tags' => $task_tags,
                                'pr' => $pr->links->html->href,
                            ];
                        }
                    }
                }
            }

            $page++;
            $data = $this->api->getDiffDevMaster($page);
        }

        foreach($pr_hashes AS $pr_hash) {
            if(!array_first($hashes, function($hv, $hk) use($pr_hash) {
                return $pr_hash == $hv;
            })) {
                unset($commits[$pr_hash]);
            }
        }

        Excel::create('integer', function($excel) use($commits) {
            $excel->sheet('report', function($sheet) use($commits) {
                $sheet->fromArray($commits);
            });
        })->download('xls');
    }

    private function searchWSTask($link) {

        preg_match('|/project/\d+?/\d+?/|iU', $link, $result);

        if(!isset($result[0])) { return false; }

        $task = $result[0];

        $task = array_first($this->ws_tasks->data, function($value, $key) use($task) {
            return $value->page == $task;
        });

        if($task) {
            $data = [
                'name' => $task->name,
                'status' => isset($task->tags) && in_array('Готово для TEST', $task->tags) ? 'принято' : 'не принято',
                'tags' => isset($task->tags) ? implode(', ', $task->tags) : '',
            ];
        } else {
            $data = [
                'name' => '',
                'status' => '',
                'tags' => '',
            ];
        }

        return $data;
    }
}
