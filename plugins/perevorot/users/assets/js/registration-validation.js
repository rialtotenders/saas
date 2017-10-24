;(function ($, window) {
    var RegistrationValidation = function () {
        var form = null;
        var self = this;

        this.methods = {
            init: function () {
                $('[data-registration]').on('ajaxError', function (e, context, messages, status, jqXHR) {
                    e.preventDefault();

                    self.methods.validate($(this), jqXHR);
                });
            },
            validate: function ($self, jqXHR) {

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
       new RegistrationValidation();
    });

    window.RegistrationValidation = RegistrationValidation;
})(window.jQuery, window);