;(function ($, window) {
    var Select = function ($self) {
        this.methods = {
            init: function () {
                $self.on('change', function () {
                    if ($(this).val() == 1) {
                        $('#payer-code').removeClass('hide');
                    } else {
                        $('#payer-code').addClass('hide');
                    }
                });
            }
        };

        this.methods.init();
    };

    $('[data-payer-select]').each(function () {
        new Select($(this));
    });

    window.Select = Select;
})(window.jQuery, window);