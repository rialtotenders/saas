<?php
    return [
        'locale_picker'=>[
            'component'=>[
                'name'=>'Языки',
                'description'=>'Вывод переключателя со списком языков'
            ],
            'properties'=>[
                'view_type'=>[
                    'name'=>'Вид переключателя языка',
                    'description'=>'Возможность вывести переключатель в виде выпадающего списка или ссылками',
                    'options'=>[
                        'inline'=>'Гиперссылки',
                        'select'=>'Выпадающий список',
                    ]
                ],
                'is_current_hidden'=>[
                    'name'=>'Скрывать текущий язык',
                    'description'=>'Не показывать в переключателе языка текущий установленный язык',
                ],
                'is_switch_same_url'=>[
                    'name'=>'Переключаться на',
                    'description'=>'Куда будет переадресован пользователь после смены языка',
                    'options'=>[
                        'index'=>'Главную страницу',
                        'current'=>'Текущую страницу',
                    ]
                ],
            ]
        ],
        'menu'=>[
            'component'=>[
                'name'=>'Главное меню',
                'description'=>'Вывод блока с меню сайта выбранному виду',
            ],
            'properties'=>[
                'menu'=>[
                    'name'=>'Группа меню',
                    'description'=>'Выбор группы меню',
                    'required'=>'Выберите группу меню',
                ],
                'depthMax'=>[
                    'name'=>'Максимальная глубина',
                    'description'=>'Максимальное количество уровней меню которое необходимо вывести',
                    'all'=>'Все уровни'
                ],
            ]
        ],
        'submenu'=>[
            'component'=>[
                'name'=>'Меню раздела',
                'description'=>'Вывод блока с меню текущего раздела сайта',
            ],
            'properties'=>[
                'depthStart'=>[
                    'name'=>'Начальный уровень',
                    'description'=>'Начальный уровень с которого нужно выводить меню',
                    'top'=>'Самый верхний'
                ],
                'type' => [
                    'name' => 'Тип подменю',
                    'description' => 'Тип подменю'
                ]
            ]
        ]
    ];
