
function cpvInit(_this) {
    var tree = _this,
        selected_item = _this.attr('data-selected'),
        input_name = _this.attr('data-input-name'),
        id_prefix = _this.attr('data-id-prefix'),
        template = $('[cpv-tree-template]'),
        items = [],
        items_controls,
        search_items = [],
        search_timeout,
        search_input = _this.parent().find('.search_cpv'),
        search_input_closed = _this.parent().find('[cpv-search-closed]'),
        search_input_open_a = _this.parent().find('.open_list'),
        search_input_closed_a = _this.parent().find('.close_list'),
        search_term,
        search_results,
        spinner_options = {
            lines: 17,
            length: 8,
            width: 2,
            radius: 13,
            scale: 1,
            corners: 1,
            color: '#888888',
            opacity: 0.25,
            rotate: 0,
            direction: 1,
            speed: 1,
            trail: 60,
            fps: 20,
            zIndex: 2e9,
            className: 'spinner',
            top: '50%',
            left: '50%',
            shadow: false,
            hwaccel: false,
            position: 'absolute'
        },
        spinner = new Spinner(spinner_options).spin(tree.prev()[0]),
        data={},
        json,
        level_select=_this.data('level-select'),
        level_select_not=_this.data('level-select-not');

    if(typeof level_select_not == 'string')
        level_select_not=false;
    
    if(_this.data('level-json')){
        data.level=_this.data('level-json');
    }

    if(_this.data('parent-item')){
        data.parent=_this.data('parent-item');
    }

    $.post(tree.data('json'), data, function (response) {
        if (response) {
            var tree_html = '';

            json=response;

            for (var i = 0; i < response.length; i++) {
                var html = template.clone().html();

                if(selected_item == response[i].id) {
                    html = html.replace(/\{checked\}/, 'checked');
                }

                html = html.replace(/\{id\}/g, id_prefix + '-' + response[i].id);
                html = html.replace(/\{input_name\}/, input_name);
                html = html.replace(/\{name\}/, response[i].name);
                html = html.replace(/\{code\}/g, response[i].id);
                html = html.replace(/\{level\}/, response[i].level);
                html = html.replace(/\{margin\}/, response[i].level * 20);

                tree_html += html;
            }

            tree.html(tree_html);
            tree.prev().remove();

            init(_this);
        }
    });

    var init = function (_this) {
        var levels = [],
            visible = [],
            branch_element;

        items_controls = _this.find('[cpv-tree-control]');
        items = _this.find('[cpv-tree-item]');

        items.on('click', 'label', function(e){
            e.stopPropagation();

            search_input_closed.val($(this).text());
        });

        items.each(function (i) {
            var self = $(this);

            search_items.push(self.find('label').text().toLowerCase().replace(/^\s+|\s+$/g, ''));

            self.data('index', i);

            if (self.data('level') == 0) {
                visible.push(i);
            } else {
                self.hide();
            }

            if(level_select && level_select!='last' && self.data('level') < level_select-1){
                self.find('input').remove();
            }

            if(level_select_not!==false && self.data('level')==level_select_not){
                self.find('input').remove();
            }
        });

        search_input.click(function(e){
            e.preventDefault();

            if(tree.is(':hidden')){
                tree.slideDown();
            }
        });

        search_input.keyup(function (e) {
            if (search_timeout) {
                clearTimeout(search_timeout);
            }

            search_timeout = setTimeout(function () {
                search_term = search_input.val().toLowerCase();
                search_results = [];
                search_found = false;

                items.removeClass('opened');

                if (search_term) {
                    for (var i = 0; i < search_items.length; i++) {
                        items[i].style.display = 'none';

                        if (search_items[i].indexOf(search_term) >= 0) {
                            items[i].style.display = 'block';

                            for (var c = 0; c < levels[i].branch.length; c++) {
                                branch_element = items[levels[i].branch[c]];

                                branch_element.style.display = 'block';
                                branch_element.className += ' opened';

                                visible.push(i);
                            }
                        }
                    }
                } else {
                    visible = [];

                    items.hide();

                    items.filter('[data-level="0"]').each(function (i) {
                        var self = $(this);
                        self.show();
                        visible.push(self.data('index'));
                    });

                }
            }, 500);
        });

        items.each(function (i, item) {
            var self = $(item),
                level = self.data('level'),
                children = self.nextUntil('[data-level=' + level + ']').filter(function () {
                    return $(this).data('level') >= level;
                }),
                elements = children.filter(function () {
                    return $(this).data('level') == level + 1;
                }),
                branch = json[i].branch,
                branch_level = level - 1,
                to = i + children.length;
            
            /*
            self.prevAll().each(function () {
                if (branch_level >= 0) {
                    var _self = $(this);

                    if (_self.data('level') == branch_level) {
                        branch.push(_self.data('index'));
                        branch_level--;
                    }
                }
            });
            */

            levels.push({
                from: i + 1,
                to: to,
                elements: elements,
                branch: branch
            });
        });

        for (var i = 0; i < levels.length; i++) {
            if (!levels[i].elements.length) {
                $(items[i]).find('.minus, .plus').remove();
            }
        }

        for (var i = 0; i < levels.length; i++) {
            if (!levels[i].elements.length) {
                $(items[i]).find('.minus, .plus').remove();
            }else{
                if(level_select=='last'){
                    $(items[i]).find('input').remove();
                }
            }
        }
        
        items_controls.click(function (e) {
            e.preventDefault();

            var self = $(this),
                item = self.closest('[cpv-tree-item]'),
                index = item.data('index'),
                level = levels[index],
                key,
                is_closed;

            if (level.elements.length) {
                is_opened = (visible.indexOf(level.from) >= 0);
                item[!is_opened ? 'addClass' : 'removeClass']('opened');

                if (!is_opened) {
                    level.elements.each(function () {
                        var i = $(this).data('index');

                        if (visible.indexOf(i) == -1) {
                            visible.push(i);
                            items[i].style.display = 'block';
                        }
                    });
                } else {
                    for (var i = level.from; i <= level.to; i++) {
                        key = visible.indexOf(i);

                        if (key >= 0) {
                            visible.splice(key, 1);
                            items[i].style.display = 'none';
                        }
                    }
                }
            }
        });

        search_input_closed.click(function(e){
            search_input_open_a.click();
            search_input_closed.hide();
            search_input.focus();
        });
        
        var checked=tree.find('input:checked');
        
        if(checked.length){
            var value;

            search_input.hide();
            search_input_closed.show();

            checked.each(function(){
                var item=$(this).closest('.list_item'),
                    i=item.data('index');

                for (var c = 0; c < levels[i].branch.length; c++) {
                    items_controls[levels[i].branch[c]].click();
                }

                value=item.find('label').html();
            });

            search_input_closed.val(value);
            tree.hide();

            search_input_open_a.show();
            search_input_closed_a.hide();
        }else{
            search_input.hide();
            search_input_closed.show();
            search_input_open_a.show();
            search_input_closed_a.hide();
        }
    };
}