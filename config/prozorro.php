<?php

return [

	'page_limit' => 10,
    'buttons'=>[
//        'tender'=>'query,cpv,dkpp,tid,date,edrpou,region,status,procedure_t',
        'tender'=>'query,cpv,tid,edrpou,region,status',
//        'plan'=>'query,cpv,dkpp,pid,edrpou,region,dateplan,procedure_p'
        'plan'=>'query,cpv,pid,region',
    ]

];