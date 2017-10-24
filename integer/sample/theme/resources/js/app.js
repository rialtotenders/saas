var APP,
    LANG,
    SEARCH_TYPE,

    IS_MAC = /Mac/.test(navigator.userAgent),
    IS_HISTORY = (window.History ? window.History.enabled : false),

    KEY_BACKSPACE = 8,
    KEY_UP = 38,
    KEY_DOWN = 40,
    KEY_ESC = 27,
    KEY_RETURN = 13,
    KEY_CMD = IS_MAC ? 91 : 17,

    spin_options={
        color:'#fff',
        lines: 15,
        width: 2
    },

    spin_options_light={
        color:'#fff',
        lines: 15,
        width: 2
    };

(function(window, undefined){

    'use strict';

    var suggest_opened,
        suggest_current;

    APP = (function(){

        var viewport = function () {
            var e = window, a = 'inner';
            if (!('innerWidth' in window )) {
                a = 'client';
                e = document.documentElement || document.body;
            }
            return {width: e[a + 'Width'], height: e[a + 'Height']};
        };

        return {
            common: function(){
                $('html').removeClass('no-js');

                $('a.registration').bind('click', function(event){
                    event.preventDefault();
                    $('.startpopup').css('display', 'block');
                });

                $('.close-startpopup').bind('click', function(event){
                    event.preventDefault();
                    $('.startpopup').css('display', 'none');
                });

                $('a.document-link').click(function(e){
                    e.preventDefault();

                    $(this).closest('.container').find('.tender--offers.documents').hide();
                    $(this).closest('.container').find('.tender--offers.documents[data-id='+$(this).data('id')+']').show();

                    $(this).closest('.container').find('.overlay-documents').addClass('open');
                });

                $('.overlay-close').click(function(e){
                    e.preventDefault();

                    $('.overlay').removeClass('open');
                });

                $(document).keydown(function(e){
                    if($('.overlay').is('.open')){
                        switch (e.keyCode){
                            case KEY_ESC:
                                $('.overlay-close').click();
                                return;
                            break;
                        }
                    }
                });

                $('.js-partners-slider').slick({
                    infinite: true,
                    dots: false,
                    arrows: true,
                    slidesToShow: 4,
                    slidesToScroll: 1,
                    responsive: [{
                        breakpoint: 830,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 1
                        }
                    },
                    {
	                    breakpoint: 680,
                        settings: {
	                        slidesToShow: 2,
	                        slidesToScroll: 1
                        }
                    },
                    {
	                    breakpoint: 530,
                        settings: {
	                        slidesToShow: 1,
	                        slidesToScroll: 1
                        }
                    }

                    ]
                });

                $('.js-slidecards-slider').slick({
                    infinite: true,
                    dots: false,
                    arrows: true,
                    slidesToShow: 4,
                    slidesToScroll: 2,
                    responsive: [{
                        breakpoint: 980,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 1
                        }
                    },
                    {
	                    breakpoint: 780,
                        settings: {
	                        slidesToShow: 2,
	                        slidesToScroll: 1
                        }
                    },
                    {
	                    breakpoint: 530,
                        settings: {
	                        slidesToShow: 1,
	                        slidesToScroll: 1
                        }
                    }

                    ]
                });

                var fh = $('.c-footmenu').height();

                $(".is-sticky").sticky({
                    topSpacing: 50,
                    bottomSpacing: 500 + fh
                });
            },

            js: {
                jsLangSelector: function(_self) {

                    var curLang = $('.sb-lang-selector__button'),
                        langList = $('[js-lang-list]');

                    curLang.html($('.jsLangListItemActive').text());
					$('.jsLangListItemActive').on('click', function(e) {
						e.preventDefault();
					});
                    _self.on('click', function() {
                        _self.addClass('sb-lang-selector--is-active');
                        langList.show();
                    });

                    $(window).on('click', function(e){
                        if(!$(e.target).closest('[data-js="jsLangSelector"]').length){
                            langList.hide();
                            _self.removeClass('sb-lang-selector--is-active');
                        }
                    });
                },
                tabs: function(_self){
                    var tabs=_self.find('[tab]'),
                        content=$('[tab-content]');

                    tabs.click(function(){
                        var self=$(this);

                        tabs.removeClass('active');
                        content.removeClass('active');
                        self.addClass('active');

                        content.addClass('none');
                        content.eq(self.index()).removeClass('none');
                    });
                },
                lot_tabs: function(_self){
                    var tabs_content=$('.'+_self.data('tab-class')),
                        tabs=_self.find('a');

                    tabs.click(function(e){
                        e.preventDefault();

                        tabs_content.removeClass('active');
                        tabs_content.eq($(this).parent().index()).addClass('active');
                    });
                },
                nav_button: function(_self) {
	                 _self.on('click', function(){
                         $('body').toggleClass('is-active');
                     });
                },

                search_result: function(_self){
                    _self.on('click', '.search-form--open', function(e){
                        e.preventDefault();
                        $(this).closest('.description-wr').toggleClass('open');
                    });

                    _self.on('click', '.show-more', function(e){
                        e.preventDefault();

                        $('.show-more').addClass('loading').spin(spin_options_light);

                        var url =  (LANG != '' ? ('/' + LANG) : '') + '/' + SEARCH_TYPE + '/form/search';
                        var vars = [], hash;

                        if (vars.length <= 0) {
                            var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
                            for (var i = 0; i < hashes.length; i++) {
                                hash = hashes[i].split('=');
                                vars[i] = hash[0] + '=' + hash[1];
                            }
                        }

                        var is_gov = $('.show-more').data('gov');

                        $.ajax({
                            url: url + (is_gov ? '/gov' : ''),
                            data: {
                                query: vars,
                                start: $('.show-more').data('start')
                            },
                            method: 'post',
                            headers: APP.utils.csrf(),
                            dataType: "html",
                            success: function(response){
                                $('.show-more').remove();

                                if(response){
                                    $('#result_container').show();
                                    $('#result').append(response);

                                    if($('#banner_register').get(0)) {
                                        var register = $('#banner_register').tmpl();
                                        var result = $('#search_result').find('.sb-table-list-item.banner_register:last');

                                        result.next().next().next().next().next().next('.sb-table-list-item').after(register.clone());
                                        result.next().next().next().next().next().next().next().next().next().next().next().next().next('.sb-table-list-item').after(register.clone());
                                    }
                                }
                            }
                        });
                    });
                },
                form: function(_self){
                    SEARCH_TYPE=_self.data('type');
                    LANG=_self.data('lang');
                }

            },
            utils: {
                get_query: function(){
                    var out=[];

                    $('.block').each(function(){
                        var self=$(this),
                            block=self.data('block'),
                            type=block.prefix;

                        if(typeof block.result === 'function'){
                            var result=block.result();

                            if(typeof result === 'object'){
                                out.push(result.join('&'));
                            }else if(result){
                                out.push(type+'='+result);
                            }
                        }
                    });

                    return out;
                },

                csrf: function(){
                    return {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    };
                }
            }
        };
    }());

    APP.common();

    $(function (){
        $('[data-js]').each(function(){
            var self = $(this);

            if (typeof APP.js[self.data('js')] === 'function'){
                APP.js[self.data('js')](self, self.data());
            } else {
                console.log('No `' + self.data('js') + '` function in app.js');
            }
        });
    });

})(window);


$(document).ready(function(){
    $(".js-menu").on('click', function () {
        $(this).next(".menu-holder").slideToggle();
    });

    $('.c-tender').on('click', ".js-change-document", function () {
        $(this).parent().parent().parent().find('.js-change-document.change-'+$(this).attr('data-change-id')).show();
    });
    $('.c-tender').on('click', ".js-contract-document", function () {
        $(this).closest(".contact-table").find('.js-contract-document').show();
    });
    $('.c-tender').on('click', ".js-award-document", function () {
        $(this).closest(".sb-table__table-row, .table__table-row").next('.js-award-document').slideToggle();
    });
    $('.c-tender').on('click', ".js-tender-document", function () {
        $(this).closest(".sb-table__table-row, .table__table-row").next('.js-tender-document').slideToggle();
    });
    $('.c-tender').on('click', ".js-tender-document-public", function () {
        $(this).closest(".table__table-row").next().next('.js-tender-document-public').slideToggle();
    });
    $('.c-tender').on('click', ".js-tender-document-confident", function () {
        $(this).closest(".table__table-row").next().next().next('.js-tender-document-confident').slideToggle();
    });
    $('.c-tender').on('click', ".js-qualification-bid-documents", function () {
        $(this).closest(".table__table-row").next('.js-qualification-bid-documents').slideToggle();
    });
    $('.c-tender').on('click', ".js-qualification-documents", function () {
        $(this).closest(".table__table-row").next('.js-qualification-documents').slideToggle();
        $(this).closest(".table__table-row").next().next('.js-qualification-documents').slideToggle();
    });
    $('.c-tender').on('click', ".js-bid-documents", function () {
        $(this).closest(".table__table-row").next('.js-bid-documents').slideToggle();
    });
    $('.c-tender').on('click', ".js-show-cancel-documents", function () {
        $(this).closest(".contact-table").find('.js-cancel-documents').slideToggle();
    });

    $('.c-tender').on('click', ".js-qualification-bid-documents .close, .js-cancel-documents .close, .js-qualification-bid-documents .close, .js-qualification-documents .close, .js-bid-documents .close, .js-change-document .close, .js-award-document .close, .js-contract-document .close, .js-tender-document .close, .tender-document-public .close, .js-tender-document-confident .close", function () {
        $(this).parent().slideToggle();
    });

    $(document).ready(function() {
        windowWidth = $(window).width();
        $('.c-main.background-absolute').css('width',windowWidth);
    });

    $(window).resize(function(){
        windowWidth = $(window).width();
        $('.c-main.background-absolute').css('width',windowWidth);
    });

    $('#tender-content').on('click', '.new-lot-block .js-slide-item, .new-item-block .js-slide-item', function () {
        $(this).closest(".lot-block, .item-block").toggleClass('hide-class').find('.form-item').slideToggle();
    });
    $('#tender-content, .c-tender').on('click', ".form-question-title", function () {
        $(this).addClass('hide').next(".form-question-wrap").slideToggle();
    });

    $('#tender-application-content, #tender-content, .c-tender').on('click', '.button-more .more-open', function () {
        $(this).closest('.question_text').addClass('open');
    });
    $('#tender-application-content, #tender-content, .c-tender').on('click', ".button-more .more-hide", function () {
        $(this).closest('.question_text').removeClass('open');
    });
    $('.block-button-tender .button-more,#lot-container .button-more').on('click', '.more-open', function () {
        $(this).hide().next().css('display','inline-block');
        $(this).parent('.button-more').prev().addClass('open');
    });
    $('.block-button-tender .button-more, #lot-container .button-more').on('click', ".more-hide", function () {
        $(this).parent('.button-more').prev().removeClass('open');
        $(this).hide().prev().css('display','inline-block');
    });

    $('select[name="payer"] option:first-child').attr('disabled','disabled');

    if (jQuery(window).width() < 959) {
        $(".input-group.date input").focus(function () {
            console.log('focus');
            $('html, body').animate({
                scrollTop: $(this).offset().top
            }, 1000);
        });
    }

    $('.sb-f__title-wrap').on('click', function () {
        $(this).toggleClass('sb-f__title-wrap--is-active');
    });

    $("select[name='group']").on('change', function () {

        var group = $(this).val();

        if(group) {
            $('.tabs-3').find('a').each(function(index, element) {
                var href = $(element).attr('href');

                if(href.indexOf('group=') == -1) {
                    $(element).attr('href', href+'&group='+group);
                }
            });
        }

        $('#simple-form-filter').submit();
    });

    // file close lot
    $('.item-lot').on('click', ".link_up", function () {
        $(this).parent('.item-lot').find('.item-wrap').slideToggle();

    });
    // file all lots close
    $('.js_link_collapse_all_lots').on('click', function () {
            $('.item-lot .item-wrap').slideUp();
    });

    // file checkbox open textarea
    $("#tender-application-content").on('change', ".upload-object input[type='checkbox']", function () {
        if($(this).is( ":checked" )) {
            $(this).closest('.js-doc-confidentiality').next('.js-doc-confidentiality-textarea').slideDown().find('textarea').attr('required', 'required');
        } else {
            $(this).closest('.js-doc-confidentiality').next('.js-doc-confidentiality-textarea').slideUp().find('textarea').removeAttr('required');
        }
    });

    $("#tender-application-content").on('click', '.edit_input', function () {
        $(this).next().removeAttr('disabled');
    });

    $('#tender-content ').on('click', ".criteria-tender-block .item-criteria .main_value_criteria", function () {
         $(this).parent('.value_criteria').find('.list_value_criteria').slideToggle();
    });

    //modal form
    var overlay = $('#overlay2');
    var modal_main_wrap = $('body');
    var modal = $('.modal_div');

    if($(modal).hasClass('show')){
        overlay.fadeIn(100,
            function(){
                modal_main_wrap.addClass('no-scroll');
            });
    }

    modal_main_wrap.on('click', '.modal_close, #overlay2', function(){
        modal.animate({opacity: 0}, 100,
            function(){
                $(this).css('display', 'none');

                if($(this).parent().data('form-modal') !== undefined) {
                    $(this).parent().css('display', 'none');
                }

                overlay.fadeOut(100);
                $(".message_modal").removeClass("show");
                $(".modal_div.show").removeClass("show");
                modal_main_wrap.removeClass('no-scroll');
            }
        );
    });

});

function validation(_this) {

    var fields = $(_this).find('.error-holder:not(:empty)');

    fields.show();

    if(fields.offset() !== undefined)
    {
        $('html, body').animate({scrollTop: fields.offset().top - 25}, 500);
    }
}

(function(a){var r=a.fn.domManip,d="_tmplitem",q=/^[^<]*(<[\w\W]+>)[^>]*$|\{\{\! /,b={},f={},e,p={key:0,data:{}},i=0,c=0,l=[];function g(g,d,h,e){var c={data:e||(e===0||e===false)?e:d?d.data:{},_wrap:d?d._wrap:null,tmpl:null,parent:d||null,nodes:[],calls:u,nest:w,wrap:x,html:v,update:t};g&&a.extend(c,g,{nodes:[],parent:d});if(h){c.tmpl=h;c._ctnt=c._ctnt||c.tmpl(a,c);c.key=++i;(l.length?f:b)[i]=c}return c}a.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(f,d){a.fn[f]=function(n){var g=[],i=a(n),k,h,m,l,j=this.length===1&&this[0].parentNode;e=b||{};if(j&&j.nodeType===11&&j.childNodes.length===1&&i.length===1){i[d](this[0]);g=this}else{for(h=0,m=i.length;h<m;h++){c=h;k=(h>0?this.clone(true):this).get();a(i[h])[d](k);g=g.concat(k)}c=0;g=this.pushStack(g,f,i.selector)}l=e;e=null;a.tmpl.complete(l);return g}});a.fn.extend({tmpl:function(d,c,b){return a.tmpl(this[0],d,c,b)},tmplItem:function(){return a.tmplItem(this[0])},template:function(b){return a.template(b,this[0])},domManip:function(d,m,k){if(d[0]&&a.isArray(d[0])){var g=a.makeArray(arguments),h=d[0],j=h.length,i=0,f;while(i<j&&!(f=a.data(h[i++],"tmplItem")));if(f&&c)g[2]=function(b){a.tmpl.afterManip(this,b,k)};r.apply(this,g)}else r.apply(this,arguments);c=0;!e&&a.tmpl.complete(b);return this}});a.extend({tmpl:function(d,h,e,c){var i,k=!c;if(k){c=p;d=a.template[d]||a.template(null,d);f={}}else if(!d){d=c.tmpl;b[c.key]=c;c.nodes=[];c.wrapped&&n(c,c.wrapped);return a(j(c,null,c.tmpl(a,c)))}if(!d)return[];if(typeof h==="function")h=h.call(c||{});e&&e.wrapped&&n(e,e.wrapped);i=a.isArray(h)?a.map(h,function(a){return a?g(e,c,d,a):null}):[g(e,c,d,h)];return k?a(j(c,null,i)):i},tmplItem:function(b){var c;if(b instanceof a)b=b[0];while(b&&b.nodeType===1&&!(c=a.data(b,"tmplItem"))&&(b=b.parentNode));return c||p},template:function(c,b){if(b){if(typeof b==="string")b=o(b);else if(b instanceof a)b=b[0]||{};if(b.nodeType)b=a.data(b,"tmpl")||a.data(b,"tmpl",o(b.innerHTML));return typeof c==="string"?(a.template[c]=b):b}return c?typeof c!=="string"?a.template(null,c):a.template[c]||a.template(null,q.test(c)?c:a(c)):null},encode:function(a){return(""+a).split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;")}});a.extend(a.tmpl,{tag:{tmpl:{_default:{$2:"null"},open:"if($notnull_1){__=__.concat($item.nest($1,$2));}"},wrap:{_default:{$2:"null"},open:"$item.calls(__,$1,$2);__=[];",close:"call=$item.calls();__=call._.concat($item.wrap(call,__));"},each:{_default:{$2:"$index, $value"},open:"if($notnull_1){$.each($1a,function($2){with(this){",close:"}});}"},"if":{open:"if(($notnull_1) && $1a){",close:"}"},"else":{_default:{$1:"true"},open:"}else if(($notnull_1) && $1a){"},html:{open:"if($notnull_1){__.push($1a);}"},"=":{_default:{$1:"$data"},open:"if($notnull_1){__.push($.encode($1a));}"},"!":{open:""}},complete:function(){b={}},afterManip:function(f,b,d){var e=b.nodeType===11?a.makeArray(b.childNodes):b.nodeType===1?[b]:[];d.call(f,b);m(e);c++}});function j(e,g,f){var b,c=f?a.map(f,function(a){return typeof a==="string"?e.key?a.replace(/(<\w+)(?=[\s>])(?![^>]*_tmplitem)([^>]*)/g,"$1 "+d+'="'+e.key+'" $2'):a:j(a,e,a._ctnt)}):e;if(g)return c;c=c.join("");c.replace(/^\s*([^<\s][^<]*)?(<[\w\W]+>)([^>]*[^>\s])?\s*$/,function(f,c,e,d){b=a(e).get();m(b);if(c)b=k(c).concat(b);if(d)b=b.concat(k(d))});return b?b:k(c)}function k(c){var b=document.createElement("div");b.innerHTML=c;return a.makeArray(b.childNodes)}function o(b){return new Function("jQuery","$item","var $=jQuery,call,__=[],$data=$item.data;with($data){__.push('"+a.trim(b).replace(/([\\'])/g,"\\$1").replace(/[\r\t\n]/g," ").replace(/\$\{([^\}]*)\}/g,"{{= $1}}").replace(/\{\{(\/?)(\w+|.)(?:\(((?:[^\}]|\}(?!\}))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\}]|\}(?!\}))*?)\))?\s*\}\}/g,function(m,l,k,g,b,c,d){var j=a.tmpl.tag[k],i,e,f;if(!j)throw"Unknown template tag: "+k;i=j._default||[];if(c&&!/\w$/.test(b)){b+=c;c=""}if(b){b=h(b);d=d?","+h(d)+")":c?")":"";e=c?b.indexOf(".")>-1?b+h(c):"("+b+").call($item"+d:b;f=c?e:"(typeof("+b+")==='function'?("+b+").call($item):("+b+"))"}else f=e=i.$1||"null";g=h(g);return"');"+j[l?"close":"open"].split("$notnull_1").join(b?"typeof("+b+")!=='undefined' && ("+b+")!=null":"true").split("$1a").join(f).split("$1").join(e).split("$2").join(g||i.$2||"")+"__.push('"})+"');}return __;")}function n(c,b){c._wrap=j(c,true,a.isArray(b)?b:[q.test(b)?b:a(b).html()]).join("")}function h(a){return a?a.replace(/\\'/g,"'").replace(/\\\\/g,"\\"):null}function s(b){var a=document.createElement("div");a.appendChild(b.cloneNode(true));return a.innerHTML}function m(o){var n="_"+c,k,j,l={},e,p,h;for(e=0,p=o.length;e<p;e++){if((k=o[e]).nodeType!==1)continue;j=k.getElementsByTagName("*");for(h=j.length-1;h>=0;h--)m(j[h]);m(k)}function m(j){var p,h=j,k,e,m;if(m=j.getAttribute(d)){while(h.parentNode&&(h=h.parentNode).nodeType===1&&!(p=h.getAttribute(d)));if(p!==m){h=h.parentNode?h.nodeType===11?0:h.getAttribute(d)||0:0;if(!(e=b[m])){e=f[m];e=g(e,b[h]||f[h]);e.key=++i;b[i]=e}c&&o(m)}j.removeAttribute(d)}else if(c&&(e=a.data(j,"tmplItem"))){o(e.key);b[e.key]=e;h=a.data(j.parentNode,"tmplItem");h=h?h.key:0}if(e){k=e;while(k&&k.key!=h){k.nodes.push(j);k=k.parent}delete e._ctnt;delete e._wrap;a.data(j,"tmplItem",e)}function o(a){a=a+n;e=l[a]=l[a]||g(e,b[e.parent.key+n]||e.parent)}}}function u(a,d,c,b){if(!a)return l.pop();l.push({_:a,tmpl:d,item:this,data:c,options:b})}function w(d,c,b){return a.tmpl(a.template(d),c,b,this)}function x(b,d){var c=b.options||{};c.wrapped=d;return a.tmpl(a.template(b.tmpl),b.data,c,b.item)}function v(d,c){var b=this._wrap;return a.map(a(a.isArray(b)?b.join(""):b).filter(d||"*"),function(a){return c?a.innerText||a.textContent:a.outerHTML||s(a)})}function t(){var b=this.nodes;a.tmpl(null,null,null,this).insertBefore(b[0]);a(b).remove()}})(jQuery);