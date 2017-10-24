$.noty.defaults = {
  layout: 'top',
  theme: 'defaultTheme',
  type: 'error',
  text: '',

  dismissQueue: true,
  force: false,
  maxVisible: 1,

  template: '<div class="noty_message"><span class="noty_text"></span><div class="noty_close"></div></div>',

  timeout: 5000,
  progressBar: true,

  animation: {
    open: {opacity: 'toggle'},
    close: {opacity: 'toggle'},
    easing: 'swing',
    speed: 500
  },
  closeWith: ['click', 'backdrop'],

  modal: false,
  killer: false,

  callback: {
    onShow: function() {},
    afterShow: function() {},
    onClose: function() {},
    afterClose: function() {},
    onCloseClick: function() {},
  },

  buttons: false
};


$( document ).ajaxError(function( event, jqxhr, settings, thrownError ) {
    event.preventDefault();

    if(typeof settings == 'string'){

        $('#curl-error-3').hide();
        $('#curl-error-18').hide();

        if(settings.indexOf('cURL error 18') > -1) {
            $('#curl-error-18').show();
            $('#overlay2').fadeIn(100);
            $('#welcome-modal').addClass('show');
        }
        else if(settings.indexOf('cURL error 3') > -1) {
            $('#curl-error-3').show();
            $('#overlay2').fadeIn(100);
            $('#welcome-modal').addClass('show');
        } else {
            noty({
                text: settings
            });
        }
    }
    else if(typeof settings !== 'string'){
        var json=settings;

        var message = json.message;

        if(message) {
            if(json.message.indexOf('OCTOBER') == -1) {
                noty({
                    text: message
                });
            }
        }
    }
});
