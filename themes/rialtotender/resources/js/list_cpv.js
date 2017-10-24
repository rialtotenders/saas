/**
 * Created by ira on 17.14.2.
 */
(function () {
    $.fn.listCPVPlugin = function(){
        
        var storage = {};
        var methods = {
            init: function(){
                //$('.list_cpv div').children(".check").removeClass('check');
                $('.list_cpv input[type="radio"], .list_cpv input[type="checkbox"]').each(function () {
                    if ($(this).is(':checked')){
                        //console.log('444');
                        var $this = $(this);
                        methods.if_checked($this);
                    }
                });

            },

            if_checked: function($this){
                
                //var $this = $(this);
                var $parentItem = $this.closest('.parent.list_item');
                //console.log($parentItem);
                
                methods.newClass($parentItem);
                
                //
                //$('.list_cpv div').children(".check").removeClass('check');
                /*if ($(this).is(':checked')) {
                    

                }*/
            },
            newClass: function ($parentItem) {
                $parentItem.addClass('open');
                //console.log($parentItem);
                methods.if_parent($parentItem);
                
            },
            if_parent: function($parentItem){
                if ($parentItem.hasClass('js-parent-list-cpv')){
                    return false;
                } else{
                    //console.log('1');
                    var $NewParentItem = $parentItem.parent().parent();
                    //console.log($NewParentItem);
                    methods.newClass($NewParentItem);
                }
            }

        };
        methods.init();

    }

})(jQuery);