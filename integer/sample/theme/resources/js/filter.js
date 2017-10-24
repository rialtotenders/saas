;(function ($, LANG, window) {
    'use strict';

    if(LANG === undefined) {
        LANG = $('#query').data('lang');
    }

    /**
     * Работа с адресной строкой
     * @type {Function}
     */
    var Search = (function () {
        var search = [];
        var opts = { lines: 17, length: 8, width: 2, radius: 13, scale: 1, corners: 1, color: '#dd4899', opacity: 0.25, rotate: 0, direction: 1, speed: 1, trail: 60, fps: 20, zIndex: 2e9, className: 'spinner', top: '50%', left: '50%', shadow: false, hwaccel: false, position: 'fixed'};

        var methods = {
            /**
             * Инициалицая скрипта
             */
            init: function () {
                methods.parseSearch();
            },
            /**
             * Парсинга search строки с url
             */
            parseSearch: function () {
                search = window.location.search;

                if (search == undefined) {
                    return;
                }

                search = search.slice(1);

                var result = search.split('&');
                search = [];

                for (var item in result) {

                    var parameters = result[item].split('=');

                    if (!(parameters[0] in search)) {
                        search[parameters[0]] = []
                    }

                    search[parameters[0]].push(parameters[1]);
                }
            },
            /**
             * Метод для вытягивания search данных
             * @returns {Array}
             */
            getSearch: function () {
                return search;
            },
            /**
             * Обновление адресной строки
             * @param parameters
             */
            setSearch: function (parameters) {
                var str = '';
                var state = {};

                for (var key in parameters) {
                    var stateItems = [];

                    if (typeof parameters[key] == 'string') {
                        str += key + '=' + parameters[key] + '&';
                        continue;
                    }

                    for (var item in parameters[key]) {
                        str += key + '=' + parameters[key][item].key + '&';
                        stateItems.push(parameters[key][item].key);
                    }

                    state[key] = stateItems;
                }

                /**
                 * for search by group
                 */
                var group = $('#simple-form-filter').find('select[name=group] option:selected');

                if(group != undefined) {
                    if(group.val()) {
                        var param = 'group=' + group.val() + '&';
                        state[param] = [group.val()];
                        str += param;
                    }
                }

                var tab_type = $('#simple-form-filter').find('input[name=tab_type]');

                if(tab_type != undefined) {
                    if(tab_type.val()) {
                        var param = 'tab_type=' + tab_type.val() + '&';
                        state[param] = [tab_type.val()];
                        str += param;
                    }
                }

                /**
                 * for search by dates
                 */

                var date_from = $('#date_from').val();
                var date_to = $('#date_to').val();

                if(date_from != undefined) {
                    var param = 'date_from=' + date_from + '&';
                    state[param] = [date_from];
                    str += param;
                }

                if(date_to != undefined) {
                    var param = 'date_to=' + date_to + '&';
                    state[param] = [date_to];
                    str += param;
                }

                str = str.substring(0, str.length - 1);

                History.pushState(state, window.document.title, "?" + str);
                methods.getContent();
            },
            /**
             * Вытягивает результат поиска с другой страницы, и вставляет в текущую
             */
            getContent: function () {
                var el = document.getElementById('search_result');
                var spinner = new Spinner(opts).spin(el);
                $("#overlay").show();


                $.get(window.location.href, function (content) {
                    $('#result').html($(content).find('#result').html());
                    spinner.stop();
                    $("#overlay").hide();

                    if($('#banner_register').get(0)) {
                        var result = $('#search_result');
                        var register = $('#banner_register').tmpl();

                        if (result.find('.sb-table-list-item:first') !== undefined) {
                            result.find('.sb-table-list-item:first').after(register.clone());
                        }
                        if (result.find('.sb-table-list-item:eq(7)') !== undefined) {
                            result.find('.sb-table-list-item:eq(7)').after(register.clone());
                        }
                    }
                });
            }
        };

        methods.init();

        return methods;
    });

    /**
     * Класс для фильтрации
     * @returns {{init: methods.init, render: methods.render}}
     * @constructor
     */
    var Filter = function () {
        var selected = {},
            regions,
            statuses,
            buyers,
            query,
            categories,
            template = $('#selected-values-template').html(),
            search;

        var methods = {
            /**
             * Инициализация
             */
            init: function () {
                search = new Search();

                regions = new Regions(search);
                statuses = new Statuses(search);
                buyers = new Buyers(search);
                query = new Query(search);
                categories = new Categories(search);

                /**
                 * Обсервер, который отмечает нужные записи, обновляет search url
                 */
                $(document).on('change', '.filter-checkboxes', methods.processFilter);

                $('#simple-form-filter').on('submit', function (e) {
                    e.preventDefault();

                    methods.processFilter();

                    methods.render(selected);

                    if($('#banner_register').get(0)) {
                        var result = $('#search_result');
                        var register = $('#banner_register').tmpl();

                        if (result.find('.sb-table-list-item:first') !== undefined) {
                            result.find('.sb-table-list-item:first').after(register.clone());
                        }
                        if (result.find('.sb-table-list-item:eq(7)') !== undefined) {
                            result.find('.sb-table-list-item:eq(7)').after(register.clone());
                        }
                    }
                });

                /**
                 * Ивент для полной очистки фильтра
                 */
                $('#clear-filter').on('click', function () {
                    regions.uncheck();
                    statuses.uncheck();
                    buyers.uncheck();
                    categories.uncheck();

                    $('#selected-values').addClass('hide');
                    $('#selected-values ul li').remove();

                    search.setSearch({});
                });

                /**
                 * Ивент для очистки одной записи с фильтра
                 */
                $('#selected-values').on('click', '.sb-f__checked-wrap ul .sb-f__checked-close', function () {
                    var type = $(this).closest('li').data('type');
                    var key = $(this).closest('li').data('key');

                    switch (type) {
                        case 'regions': {
                            regions.uncheck($(this), key);
                        }
                            break;

                        case 'statuses': {
                            statuses.uncheck($(this), key);
                        }
                            break;

                        case 'buyers': {
                            buyers.uncheck($(this), key);
                        }
                            break;

                        case 'categories': {
                            categories.uncheck($(this), key);
                        }
                            break;
                    }
                });

                /**
                 * Скрывает все тултипчики
                 */
                $(document).on('click', function () {
                    $('.sb-f__input-list-wrap-holder').addClass('hide');
                })
            },
            processFilter: function () {
                selected = regions.getSelected();
                selected = $.extend({}, statuses.getSelected(), selected);
                selected = $.extend({}, buyers.getSelected(), selected);
                selected = $.extend({}, categories.getSelected(), selected);

                if (Object.keys(selected).length > 0) {
                    $('#selected-values').removeClass('hide')
                } else {
                    $('#selected-values').addClass('hide')
                }

                if (regions.isInit() && statuses.isInit()) {
                    search.setSearch({
                        'region': regions.getSelected(),
                        'status': statuses.getSelected(),
                        'edrpou': buyers.getSelected(),
                        'query': query.getSelected(),
                        'cpv': categories.getSelected(),
                    });
                }

                methods.render(selected);
            },

            /**
             * Рендер шаблона выбраных фильтров
             * @param selected
             */
            render: function (selected) {
                var result = '';

                $.each(selected, function (index, value) {
                    if(template !== undefined) {
                        result += template.replace('{type}', value.type).replace('{key}', value.key.replace('.', '-')).replace('{key}', value.key.replace('.', '-')).replace('{value}', value.text);
                    }
                });

                $('#selected-values .sb-f__checked-wrap ul').html(result);
            }
        };

        methods.init();

        return methods;
    };

    /**
     * @param search
     * @returns {{init: methods.init, getSelected: methods.getSelected}}
     * @constructor
     */
    var Query = function (search) {
        var query = '',
            querySearchInput = $('.sb-search-input__input-field');

        /**
         * @type {{init: methods.init, getSelected: methods.getSelected}}
         */
        var methods = {
            /**
             * Инициализация скрипта
             */
            init: function () {
                if (search.getSearch()['query'] != undefined) {
                    query = search.getSearch()['query'][0];

                    methods.checkSelected();
                }

                querySearchInput.on('keyup', methods.filter);

            },
            checkSelected: function () {
                $('.sb-search-input__bottom .query_input').val(decodeURIComponent(query));
            },
            /**
             * @returns {Array}
             */
            filter: function () {
                query = $(this).val();
            },
            /**
             * Возвращает выбраные запросы
             * @returns {{}}
             */
            getSelected: function () {
                if (!query) {
                    return null;
                }

                return query;
            }
        };

        methods.init();

        return methods;
    };
    
    /**
     * Фильтрация регионов
     * @param search
     * @returns {{init: methods.init, load: methods.load, checkSelected: methods.checkSelected, uncheck: methods.uncheck, filter: methods.filter, render: methods.render, getSelected: methods.getSelected, isInit: methods.isInit}}
     * @constructor
     */
    var Regions = function (search) {
        const REGION_URL = '/form/data/region';
        const OPEN_BUTTON = 'Показать все';
        const CLOSE_BUTTON = 'Скрыть все';
        const HEIGHT_OFFSET = 400;

        var regions = {},
            init = false,
            regionsFiltered = {},
            regionsFilterBlock = $('#regions-filter'),
            template = $('#regions-template').html();

        var methods = {
            /**
             * Инициализация скрипта
             */
            init: function () {
                var selectedRegions = search.getSearch()['region'];
                methods.load(selectedRegions);

                regionsFilterBlock.on('keyup', '.autocomplete', methods.filter);
                regionsFilterBlock.on('click', '.show-all', methods.toggleTooltip);
            },
            /**
             *
             * @param e
             */
            toggleTooltip: function (e) {
                e.preventDefault();

                var tooltip = regionsFilterBlock.find('.sb-f__ckeckbox-list');

                if (tooltip.hasClass('opened')) {
                    $(this).text(OPEN_BUTTON);

                    tooltip.css({
                        'max-height': '190px'
                    }).removeClass('opened');
                } else {
                    var height = 0;

                    $(this).text(CLOSE_BUTTON);

                    regionsFilterBlock.find('.sb-f__ckeckbox-list .sb-f__checkbox-item-wrap').each(function () {
                        height += $(this).height();
                    });

                    tooltip.css({
                        'max-height': (height + HEIGHT_OFFSET) + 'px'
                    }).addClass('opened');
                }
            },
            /**
             * Загрузка регионов с json
             * @param selectedRegions
             */
            load: function (selectedRegions) {
                $.ajax({
                    url: REGION_URL,
                    method: 'POST',
                    success: function (data) {
                        regions = data;

                        methods.render(regions);
                        methods.checkSelected(selectedRegions);
                    }
                });
            },
            /**
             * Выбор уже выбраных фильтров (при парсинге с url)
             * @param regions
             */
            checkSelected: function (regions) {
                for (var key in regions) {
                    var selector = '[data-region-search=' + regions[key] + ']';

                    $(selector).trigger('click');
                }

                init = true;
            },
            /**
             * Метод, который снимает check
             * @param _self
             * @param key
             */
            uncheck: function (_self, key) {
                if (_self) {
                    _self.closest('li').remove();
                    regionsFilterBlock.find('.regions-' + key).find('input[type=checkbox]').trigger('click');
                } else {
                    regionsFilterBlock.find('.filter-checkboxes').prop('checked', false);
                }
            },
            /**
             * Фильтрация регионов по запросу @var query
             */
            filter: function () {
                var query = $(this).val();

                regionsFiltered = regions.filter(function (item) {
                    return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
                });

                $('.regions-checkbox').addClass('hide');

                $.each(regionsFiltered, function (index, value) {
                    $('.regions-' + value.id).removeClass('hide');
                });
            },
            /**
             * Рендер регионов по шаблону
             * @param items
             */
            render: function (items) {
                var result = '';

                $.each(items, function (index, value) {
                    if(template !== undefined) {
                        result += template.replace('{key}', value.id).replace('{key-search}', value.id).replace('{key-class}', value.id).replace('{key-value}', value.id).replace('{value}', value.name);
                    }
                });

                regionsFilterBlock.find('.sb-f__ckeckbox-list').html(result);
            },
            /**
             * Возвращает выбраные регионы
             * @returns {{}}
             */
            getSelected: function () {
                var regions = {};

                $('.regions-checkbox input[type=checkbox]:checked').each(function (index) {
                    regions[index] = {
                        key: $(this).val().toString(),
                        text: $(this).closest('label').find('p').text(),
                        type: 'regions'
                    };
                });

                return regions;
            },
            /**
             * Проверка, заинитился ли скрипт
             * @returns {boolean}
             */
            isInit: function () {
                return init;
            }
        };

        methods.init();

        return methods;
    };

    /**
     * Фильтрация статусов
     * @param search
     * @returns {{init: methods.init, load: methods.load, uncheck: methods.uncheck, checkSelected: methods.checkSelected, filter: methods.filter, render: methods.render, getSelected: methods.getSelected, isInit: methods.isInit}}
     * @constructor
     */
    var Statuses = function (search) {
        const STATUS_URL = '/form/data/status';

        var statuses = {},
            statusesFiltered = {},
            statusesFilterBlock = $('#statuses-filter'),
            init = false,
            template = $('#statuses-template').html();

        var methods = {
            /**
             * Конструктор
             */
            init: function () {
                var selectedStatuses = search.getSearch()['status'];
                methods.load(selectedStatuses);

                statusesFilterBlock.on('keyup', '.autocomplete', methods.filter);
            },
            /**
             * Загрузка файла со статусами
             * @param selectedStatuses
             */
            load: function (selectedStatuses) {
                $.ajax({
                    url: STATUS_URL,
                    method: 'POST',
                    data: {lang: LANG},
                    success: function (data) {
                        statuses = data;

                        methods.render(statuses);
                        methods.checkSelected(selectedStatuses);
                    }
                });
            },
            /**
             * Снятия check с фильтра
             * @param _self
             * @param key
             */
            uncheck: function (_self, key) {
                if (_self) {
                    _self.closest('li').remove();
                    statusesFilterBlock.find('.statuses-' + key.replace('.', '-')).find('input[type=checkbox]').prop('checked', true).trigger('click');
                } else {
                    statusesFilterBlock.find('.filter-checkboxes').prop('checked', false);
                }
            },
            /**
             * Отмечает выбранные фильтры (срабатывает после перезагрузки страницы)
             * @param statuses
             */
            checkSelected: function (statuses) {
                for (var key in statuses) {
                    var selector = '[data-status-search="' + statuses[key].replace('.', '-') + '"]';

                    $(selector).trigger('click');
                }

                init = true;
            },
            /**
             * Метод для фильтрации
             */
            filter: function () {
                var query = $(this).val();

                statusesFiltered = statuses.filter(function (item) {
                    return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
                });

                $('.statuses-checkbox').addClass('hide');

                $.each(statusesFiltered, function (index, value) {
                    $('.statuses-' + value.id.replace('.', '-')).removeClass('hide');
                });
            },
            /**
             * Отображения выбраных чекбоксов
             * @param items
             */
            render: function (items) {
                var result = '';

                $.each(items, function (index, value) {
                    if(template !== undefined) {
                        result += template.replace('{key}', value.id.replace('.', '-')).replace('{key-search}', value.id.replace('.', '-')).replace('{key-class}', value.id.replace('.', '-')).replace('{key-value}', value.id).replace('{value}', value.name);
                    }
                });

                statusesFilterBlock.find('.sb-f__ckeckbox-list').html(result);
            },
            /**
             * Возвращает отмеченные елементы
             * @returns {{}}
             */
            getSelected: function () {
                var statuses = {};

                $('.statuses-checkbox input[type=checkbox]:checked').each(function (index) {
                    statuses[$(this).val().toString()] = {
                        key: $(this).val().toString(),
                        text: $(this).closest('label').find('p').text(),
                        type: 'statuses'
                    };
                });

                return statuses;
            },
            isInit: function () {
                return init;
            }
        };

        methods.init();

        return methods;
    };

    /**
     * Фильтрация закупщиков
     * @param search
     * @returns {{init: methods.init, load: methods.load, checkSelected: methods.checkSelected, uncheck: methods.uncheck, filter: methods.filter, render: methods.render, getSelected: methods.getSelected, isInit: methods.isInit}}
     * @constructor
     */
    var Buyers = function (search) {
        const STATUS_URL = '/form/data/edrpou';

        var buyers = {},
            buyersFiltered = [],
            init = false,
            buyersFilterBlock = $('#buyers-filter'),
            empty_buyers_template = $('#empty-template').html(),
            template = $('#buyers-template').html(),
            xhr;

        var methods = {
            /**
             * Конструктор
             */
            init: function () {
                /**
                 * Получение выбраных закупщиков
                 */
                var selectedBuyers = search.getSearch()['edrpou'];
                methods.loadNames(selectedBuyers);

                /**
                 * Фильтрация ввода
                 */
                buyersFilterBlock.on('keyup', '.autocomplete', methods.filterLive);

                /**
                 * Ивент, при выборе нужного закупщика
                 */
                buyersFilterBlock.on('click', '.buyers-item', function () {
                    $(this).closest('.buyers-wrapper').find('.filter-checkboxes').trigger('click');

                    $(this).toggleClass('active');
                    $(this).closest('.buyers-wrapper').toggleClass('selected');

                    buyersFiltered.push({
                        key: $(this).data('key').toString(),
                        text: $(this).find('span').text(),
                        type: 'buyers'
                    });

                    buyersFilterBlock.find('.sb-f__input-list-wrap-holder').addClass('hide');

                    buyersFilterBlock.find('.autocomplete').val('');
                });

                /**
                 * Работа с модалками
                 */
                buyersFilterBlock.find('.sb-f__input-search-wrap').on('mouseenter', function () {
                    if (buyersFilterBlock.find('.sb-f__input-list-wrap ul li').length > 0) {
                        buyersFilterBlock.find('.sb-f__input-list-wrap').removeClass('hide');
                    }
                });

                buyersFilterBlock.find('.sb-f__input-search-wrap').on('mouseleave', function () {
                    buyersFilterBlock.find('.sb-f__input-list-wrap').addClass('hide');
                });
            },
            /**
             * Выделение выбраных объяктов (срабатывает при загрузке страницы)
             * @param selectedBuyers
             */
            checkSelected: function (selectedBuyers) {
                buyersFiltered = [];

                if (!selectedBuyers) {
                    init = true;

                    return;
                }

                for (var key in buyers) {
                    if (selectedBuyers.indexOf(buyers[key].id) == -1) {
                        continue;
                    }

                    buyersFiltered.push(buyers[key]);
                }

                methods.render(buyersFiltered, true);

                for (var key in selectedBuyers) {
                    var selector = '[data-buyer-search=' + selectedBuyers[key].replace('.', '-') + ']';

                    $(selector).trigger('click');

                    $('.buyers-item-' + selectedBuyers[key].replace('.', '-')).closest('.buyers-wrapper').removeClass('hide');
                }

                init = true;
            },
            /**
             * Метод для снятия выдиления
             * @param _self
             * @param key
             */
            uncheck: function (_self, key) {
                if (_self) {
                    _self.closest('li').remove();
                    buyersFilterBlock.find('.buyers-item-' + key).removeClass('active');
                    buyersFilterBlock.find('.buyers-item-' + key).closest('.buyers-wrapper').find('.filter-checkboxes').click();
                    buyersFilterBlock.find('.buyers-item-' + key).closest('.buyers-wrapper').removeClass('selected');
                } else {
                    buyersFilterBlock.find('li.active').removeClass('active');
                    buyersFilterBlock.find('.filter-checkboxes').prop('checked', false);
                    buyersFilterBlock.find('.filter-checkboxes').removeClass('selected');
                }
            },
            loadNames: function(selectedBuyers){
                var cnt=0;

                $.each(selectedBuyers, function(index, buyer){
                    $.ajax({
                        url: STATUS_URL,
                        method: 'POST',
                        data: {
                            query: buyer,
                            is_gov: window.location.href.indexOf('/gov'),
                        },
                        success: function (data) {
                            cnt++;
                            buyers[index] = data[0];

                            if(cnt==selectedBuyers.length){
                                methods.checkSelected(selectedBuyers);
                            }
                        }
                    });
                });
            },
            filterLive: function () {
                if(xhr){
                    xhr.abort();
                }

                xhr=$.ajax({
                    url: STATUS_URL,
                    method: 'POST',
                    data: {
                        query: $(this).val(),
                        is_gov: window.location.href.indexOf('/gov'),
                    },
                    success: function (data) {
                        buyersFiltered = data;

                        methods.filterShow();
                    }
                });
            },
            filterShow: function(){
                methods.render(buyersFiltered);

                buyersFilterBlock.find('.sb-f__input-list-wrap-holder').removeClass('hide');

                if (buyersFilterBlock.find('.sb-f__input-list-wrap ul .selected').length > 0 || buyersFiltered.length > 0) {
                    buyersFilterBlock.find('.sb-f__input-list-wrap').removeClass('hide');
                }
            },
            /**
             * Отрисовка елементов в дом-е
             * @param items
             * @param selected
             */
            render: function (items, selected) {
                var result = '';

                $.each(items, function (index, value) {
                    if(template !== undefined) {
                        result += template.replace('{key}', value.id).replace('{key-search}', value.id.replace('.', '-')).replace('{key-class}', value.id.replace('.', '-')).replace('{key-value}', value.id.replace('.', '-')).replace('{value}', value.name);
                    }
                });

                if (buyersFilterBlock.find('.sb-f__input-list-wrap ul .selected').length > 0) {
                    buyersFilterBlock.find('.sb-f__input-list-wrap ul > div').not($('.selected')).remove();
                    buyersFilterBlock.find('.sb-f__input-list-wrap ul .selected').after(result);
                } else {
                    buyersFilterBlock.find('.sb-f__input-list-wrap ul').html(result);
                }

                if ((buyersFilterBlock.find('.sb-f__input-list-wrap ul .selected').length < 1) && (items.length < 1)) {
                    buyersFilterBlock.find('.sb-f__input-list-wrap ul').html(empty_buyers_template);
                }

                if (selected) {
                    buyersFilterBlock.find('.sb-f__input-list-wrap ul > div').addClass('selected');
                }
            },
            /**
             * Возвращает все выбранные елементы
             * @returns {{}}
             */
            getSelected: function () {
                var buyers = {};

                $('.buyers-wrapper input[type=checkbox]:checked').each(function (index) {
                    buyers[$(this).val()] = {
                        key: $(this).val().toString(),
                        text: $(this).closest('.buyers-wrapper').find('li span').text(),
                        type: 'buyers'
                    };
                });

                return buyers;
            },
            isInit: function () {
                return init;
            }
        };

        methods.init();

        return methods;
    };

    /**
     * Класс Категорий
     * @param search
     * @returns {{init: methods.init, load: methods.load, checkSelected: methods.checkSelected, uncheck: methods.uncheck, filter: methods.filter, render: methods.render, getSelected: methods.getSelected, isInit: methods.isInit}}
     * @constructor
     */
    var Categories = function (search) {
        const STATUS_URL = '/form/data/cpv';

        var categories = {},
            categoriesFiltered = [],
            init = false,
            categoriesFilterBlock = $('#categories-filter'),
            empty_categories_template = $('#empty-template').html(),
            template = $('#categories-template').html();

        var methods = {
            /**
             * Конструктор
             */
            init: function () {

                var selectedCategories = search.getSearch()['cpv'];
                methods.load(selectedCategories);

                categoriesFilterBlock.on('keyup', '.autocomplete', methods.filter);

                categoriesFilterBlock.on('click', '.categories-item', function () {
                    $(this).closest('.categories-wrapper').find('.filter-checkboxes').trigger('click');

                    $(this).toggleClass('active');
                    $(this).closest('.categories-wrapper').toggleClass('selected');

                    categoriesFiltered.push({
                        key: $(this).data('key').toString(),
                        text: $(this).find('span').text(),
                        type: 'categories'
                    });

                    categoriesFilterBlock.find('.sb-f__input-list-wrap-holder').addClass('hide');

                    categoriesFilterBlock.find('.autocomplete').val('');
                });

                categoriesFilterBlock.find('.sb-f__input-search-wrap').on('mouseenter', function () {
                    if (categoriesFilterBlock.find('.sb-f__input-list-wrap ul li').length > 0) {
                        categoriesFilterBlock.find('.sb-f__input-list-wrap').removeClass('hide');
                    }
                });

                categoriesFilterBlock.find('.sb-f__input-search-wrap').on('mouseleave', function () {
                    categoriesFilterBlock.find('.sb-f__input-list-wrap').addClass('hide');
                });
            },
            /**
             * Загрузка категорий с файла
             * @param selectedCategories
             */
            load: function (selectedCategories) {
                $.ajax({
                    url: STATUS_URL,
                    method: 'POST',
                    data: {lang: LANG},
                    success: function (data) {
                        categories = data;

                        methods.checkSelected(selectedCategories);
                    }
                });
            },
            /**
             * Выбор отмеченных категорий
             * @param selectedCategories
             */
            checkSelected: function (selectedCategories) {
                categoriesFiltered = [];

                if (!selectedCategories) {
                    init = true;

                    return;
                }

                for (var key in categories) {
                    if (selectedCategories.indexOf(categories[key].id) == -1) {
                        continue;
                    }

                    categoriesFiltered.push(categories[key]);
                }

                methods.render(categoriesFiltered, true);

                for (var key in selectedCategories) {
                    var selector = '[data-category-search=' + selectedCategories[key].replace('.', '-') + ']';

                    $(selector).trigger('click');

                    $('.categories-item-' + selectedCategories[key].replace('.', '-')).closest('.categories-wrapper').removeClass('hide');
                }

                init = true;
            },
            /**
             * Метод, который снимает выдиление с нужных елементов
             * @param _self
             * @param key
             */
            uncheck: function (_self, key) {
                if (_self) {
                    _self.closest('li').remove();
                    categoriesFilterBlock.find('.categories-item-' + key).removeClass('active');
                    categoriesFilterBlock.find('.categories-item-' + key).closest('.categories-wrapper').find('.filter-checkboxes').click();
                    categoriesFilterBlock.find('.categories-item-' + key).closest('.categories-wrapper').removeClass('selected');
                } else {
                    categoriesFilterBlock.find('li.active').removeClass('active');
                    categoriesFilterBlock.find('.filter-checkboxes').prop('checked', false);
                    categoriesFilterBlock.find('.filter-checkboxes').removeClass('selected');
                }
            },
            /**
             * Метод, который фильтрует по нужному параметру в массиве данных
             */
            filter: function () {
                var query = $(this).val();

                categoriesFiltered = categories.filter(function (item) {
                    if(parseInt(query) >= 0)
                    {
                        return item.id.indexOf(query) == 0 || item.id.indexOf('0' + query) == 0;
                    }
                    else {
                        return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
                    }
                }).slice(0, 15);

                methods.render(categoriesFiltered);

                categoriesFilterBlock.find('.sb-f__input-list-wrap-holder').removeClass('hide');

                if (categoriesFilterBlock.find('.sb-f__input-list-wrap ul .selected').length > 0 || categoriesFiltered.length > 0) {
                    categoriesFilterBlock.find('.sb-f__input-list-wrap').removeClass('hide');
                }
            },
            /**
             * Генерация отображения елементов
             * @param items
             * @param selected
             */
            render: function (items, selected) {
                var result = '';

                $.each(items, function (index, value) {
                    if(template !== undefined) {
                        result += template.replace('{key}', value.id).replace('{key-search}', value.id.replace('.', '-')).replace('{key-class}', value.id.replace('.', '-')).replace('{key-value}', value.id.replace('.', '-')).replace('{value}', value.id + ' ' + value.name);
                    }
                });

                if (categoriesFilterBlock.find('.sb-f__input-list-wrap ul .selected').length > 0) {
                    categoriesFilterBlock.find('.sb-f__input-list-wrap ul > div').not($('.selected')).remove();
                    categoriesFilterBlock.find('.sb-f__input-list-wrap ul .selected').after(result);
                } else {
                    categoriesFilterBlock.find('.sb-f__input-list-wrap ul').html(result);
                }

                if ((categoriesFilterBlock.find('.sb-f__input-list-wrap ul .selected').length < 1) && (items.length < 1)) {
                    categoriesFilterBlock.find('.sb-f__input-list-wrap ul').html(empty_categories_template)
                }

                if (selected) {
                    categoriesFilterBlock.find('.sb-f__input-list-wrap ul > div').addClass('selected');
                }
            },
            /**
             * Метод, который возвращает выбранные елементы
             * @returns {{}}
             */
            getSelected: function () {
                var categories = {};

                $('.categories-wrapper input[type=checkbox]:checked').each(function (index) {
                    categories[$(this).val()] = {
                        key: $(this).val().toString(),
                        text: $(this).closest('.categories-wrapper').find('li span').text(),
                        type: 'categories'
                    };
                });

                return categories;
            },
            isInit: function () {
                return init;
            }
        };

        methods.init();

        return methods;
    };

    $('[data-filter]').each(function () {
        new Filter();
    });
})(window.jQuery, window.LANG, window);