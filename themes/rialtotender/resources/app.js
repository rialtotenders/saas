/*!
 *
 * rialtotender.com v1.0.0
 *
 * MIT License
 * Author: Lanko Andrey (lanko@perevorot.com)
 *
 * ©2015
 *
 */
(function (window, undefined) {
    "use strict";

    var APP = (function () {
        return {
            js: {
                scroll_bookmark: function(_self) {

                   _self.on('click', function(){
                       var target_block = _self.attr('href');
                       var active_class = 'is-active';

                       _self.parent().parent().find('a').removeClass(active_class);
                       _self.addClass(active_class);

                       $('body, html').stop(true).animate({
                           scrollTop: $(target_block).closest('h2').offset().top - 25
                       }, 700)

                   });
                },
                jsLangSelector: function(_self) {
                    var curLang = $('.sb-lang-selector__button'),
                        langList = $('[js-lang-list]');

                    curLang.html($('.jsLangListItemActive').text());

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
                }
             }
        };
    }());

    $(function () {
        $("[data-js]").each(function () {
            var self = $(this);
            if (typeof APP.js[self.data("js")] === "function") {
                APP.js[self.data("js")](self, self.data());
            } else {
                console.log("No `" + self.data("js") + "` function in app.js");
            }
        });
    });
})(window);




$(document).ready(function(){
    
    $('.js-partners-slider').slick({
        infinite: true,
        dots: false,
        arrows: true,
        slidesToShow: 4,
        slidesToScroll: 1
    });
    
    $('.js-slidecards-slider').slick({
        infinite: true,
        dots: false,
        arrows: true,
        slidesToShow: 4,
        slidesToScroll: 2,
        responsive: [{
            breakpoint: 768,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1
            }
        }]
    });

    var fh = $('.c-footmenu').height();

    $(".is-sticky").sticky({
        topSpacing: 50,
        bottomSpacing: 500 + fh
    });

});