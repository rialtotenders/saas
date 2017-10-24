
/**
 * Created by ira on 17.14.2.
 */
(function () {
    $.fn.searchCPVPlugin = function(){
        return;

        var storage = {};
        var methods = {
            init: function(){

                $('.search_cpv').each(function () {
                    //this input search
                    storage.inputSearch = $(this);
                    //next list cpv
                    storage.list_cpv= $(storage.inputSearch).next('.list_cpv');


                    // if checked radio
                    storage.list_cpv.find('input[type="radio"]').change( function () {
                        // if radio checked
                        if (storage.list_cpv.find('input[type="radio"]').is(':checked')){

                            //value checked
                            var valueRadio = $(this).parent().attr('data-value');
                            //rewrite value input search
                            $(this).closest('.form-holder').find('.search_cpv').val(valueRadio);
                            // list cpv slideup
                            $(storage.list_cpv).slideUp();
                            // change button open/close list cpv
                            $(storage.list_cpv).next('.list_cpv_button').find('.open_list').show().next('.close_list').hide();
                        }
                    });


                    // if text in input search
                    $(this).keyup(function () {

                        //save value in input search
                        storage.valSearch = $(storage.inputSearch).val();
                        //console.log('step_');

                        //slidedown list cpv
                        $(storage.list_cpv).slideDown();

                        // change button open/close list cpv
                        $(storage.list_cpv).next('.list_cpv_button').find('.open_list').hide().next('.close_list').show();

                        //search cpv
                        methods.search();
                        
                        
                       
                    });

                    
                     

                });
                

            },
            search: function () {
                //find item in list cpv
                $(storage.list_cpv).find('.item').each(function () {

                    //save item value
                    var dataValue = $(this).attr('data-value');
                    console.log('step0');
                    //console.log(dataValue);


                    //compare item and search value
                    if(dataValue.toLowerCase().indexOf(storage.valSearch) + 1){
                        
                        console.log('step1');
                        
                        //show item
                        $(this).show();
                        // if parent add class open, need show item
                        if ($(this).parent().parent().hasClass('parent')){
                            //console.log('step2');
                            $(this).addClass('open');
                            $(this).find('.item').each(function () {
                                console.log('step3');
                                $(this).show();
                            });
                            
                        } else{
                            console.log('step4');
                            //if parent js-parent-list-cpv addClass and stop
                            if($(this).parent('.js-parent-list-cpv')){
                                $(this).parent('.js-parent-list-cpv').addClass('open');
                                
                            } else{
                                methods.parentClass($(this));
                            }
                            
                            
                        }
                    } else{
                        
                        //delete class open parent
                        if ($(this).parent().hasClass('parent')){
                            console.log('step5');
                            $(this).removeClass('open');
                           
                            //methods.removeparentClass($(this));
                        }
                        
                        //if parent > item visible , show item in parent
                        if($(this).parent().parent('.parent').find('> .item').is(":visible")){
                            console.log('step6');
                            $(this).show();
                            methods.parentClass($(this));
                        } else{
                            console.log('step7');
                            $(this).hide();
                            
                            
                        }
                        
                        
                    }
                });
              

            },

            // find parent
            parentClass: function ($this) {
                var $parentItem = $this.parent().parent();

                if ($parentItem.hasClass('overflow-list-cpv') || $parentItem.hasClass('list_cpv')){
                    console.log('stop');
                    $parentItem.find('.js-parent-list-cpv');
                    

                } else{
                    //$parentItem.addClass('open');
                    //console.log('parentClass '+$parentItem);
                    console.log('step8');
                    methods.newClass($parentItem);
                    
                    
                }
                

            },
            
            // addClass parent
            newClass: function ($parentItem) {
                console.log('step9');
                $parentItem.addClass('open');
                //console.log('newClass '+$parentItem);
                methods.parentClass2($parentItem);
                

            },
            
            //find parent
            parentClass2: function ($parentItem) {
                var $NewparentItem = $parentItem.parent().parent();

                if ($NewparentItem.hasClass('overflow-list-cpv') || $NewparentItem.hasClass('list_cpv')){
                    console.log('stop');
                    $NewparentItem.find('.js-parent-list-cpv');
                    
                    return false;

                } else{
                    //$parentItem.addClass('open');
                    //console.log('parentClass '+$parentItem);
                    console.log('step10');
                    methods.newClass($NewparentItem);
                    
                }


            }
           

            

        };
        methods.init();

    }

})(jQuery);