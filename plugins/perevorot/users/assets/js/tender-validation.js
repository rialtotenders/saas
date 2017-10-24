;(function ($, window) {
    var TenderValidation = function () {
        var form = null;
        var self = this;

        this.methods = {
            init: function () {
                $('[data-tender]').on('ajaxError', function (e, context, messages, status, jqXHR) {
                    e.preventDefault();

                    self.methods.validate($(this), jqXHR);
                });
            },
            validate: function ($self, jqXHR) { //console.log(JSON.parse(JSON.parse(jqXHR.responseText).message).X_OCTOBER_ERROR_FIELDS);

                if(jqXHR.responseJSON == undefined) {
                    return false;
                }

                var fields = jqXHR.responseJSON.X_OCTOBER_ERROR_FIELDS;
                form = $self;

                form.find('[data-validation]').text('');
                form.find('[data-validation]').hide();

                for (var key in fields) {
                    self.methods.validateField(key, fields[key]);
                }

                return true;
            },
            validateField: function (field, messages) {
                form.find('[data-validation="'+ field + '"]').text(messages[0]);
            }
        };

        this.methods.init();

        return this.methods;
    };

    $(document).ready(function () {
       new TenderValidation();
    });

    window.TenderValidation = TenderValidation;
})(window.jQuery, window);