/*
 * File upload form field control
 *
 * Data attributes:
 * - data-control="fileupload" - enables the file upload plugin
 * - data-unique-id="XXX" - an optional identifier for multiple uploaders on the same page, this value will 
 *   appear in the postback variable called X_OCTOBER_FILEUPLOAD
 * - data-template - a Dropzone.js template to use for each item
 *
 * JavaScript API:
 * $('div').fileUploader()
 *
 * Dependancies:
 * - Dropzone.js
 */
+function ($) { "use strict";

    var scripts = document.getElementsByTagName('script'),
        _parameter = scripts[scripts.length-1].src.replace(/^.*\?/,'');

    // FILEUPLOAD CLASS DEFINITION
    // ============================

    var FileUpload = function (element, options) {
        this.$el = $(element)
        this.options = options || {}

        this.init()
    }

    FileUpload.prototype.init = function() {
        if (this.options.isMulti === null) {
            this.options.isMulti = this.$el.hasClass('is-multi')
        }

        if (this.options.isPreview === null) {
            this.options.isPreview = this.$el.hasClass('is-preview')
        }

        this.$uploadButton = $('.upload-button', this.$el)
        this.$filesContainer = $('.upload-files-container', this.$el)

        this.uploaderOptions = {}

        //this.$el.on('click', '.upload-object.is-success', $.proxy(this.onClickSuccessObject, this))
        this.$el.on('click', '.upload-object.is-error', $.proxy(this.onClickErrorObject, this))
        this.$el.on('click', '[data-replace]', function (e) {
            $(this).closest('.upload-object').parent().parent().find('.upload-button').trigger('click');
            $(this).closest('.upload-object').addClass('old-file');
        })

        // Stop here for preview mode
        if (this.options.isPreview)
            return

        this.$el.on('click', '.upload-remove-button', $.proxy(this.onRemoveObject, this))

        this.bindUploader()
    }

    FileUpload.prototype.dispose = function() {

       // this.$el.off('click', '.upload-object.is-success', $.proxy(this.onClickSuccessObject, this))
        this.$el.off('click', '.upload-object.is-error', $.proxy(this.onClickErrorObject, this))
        this.$el.off('click', '.upload-remove-button', $.proxy(this.onRemoveObject, this))

        this.$el.removeData('oc.fileUpload')

        this.$el = null
        this.$uploadButton = null
        this.$filesContainer = null
        this.uploaderOptions = null

        // In some cases options could contain callbacks, 
        // so it's better to clean them up too.
        this.options = null
    }

    //
    // Uploading
    //

    FileUpload.prototype.bindUploader = function() {
        this.uploaderOptions = {
            url: this.options.url,
            paramName: this.options.paramName,
            clickable: this.$uploadButton.get(0),
            previewsContainer: this.$filesContainer.get(0),
            maxFiles: !this.options.isMulti ? 1 : null,
            headers: {}
        }

        if (this.options.fileTypes) {
            this.uploaderOptions.acceptedFiles = this.options.fileTypes
        }

        if (this.options.template) {
            this.uploaderOptions.previewTemplate = $(this.options.template).html()
        }

        if (this.options.uniqueId) {
            this.uploaderOptions.headers['X-OCTOBER-FILEUPLOAD'] = this.options.uniqueId
        }

        this.uploaderOptions.thumbnailWidth = this.options.thumbnailWidth
            ? this.options.thumbnailWidth : null

        this.uploaderOptions.thumbnailHeight = this.options.thumbnailHeight
            ? this.options.thumbnailHeight : null

        this.uploaderOptions.resize = this.onResizeFileInfo

        /*
         * Add CSRF token to headers
         */
        var token = $('meta[name="csrf-token"]').attr('content')
        if (token) {
            this.uploaderOptions.headers['X-CSRF-TOKEN'] = token
        }

        this.dropzone = new Dropzone(this.$el.get(0), this.uploaderOptions)
        this.dropzone.on('addedfile', $.proxy(this.onUploadAddedFile, this))
        this.dropzone.on('sending', $.proxy(this.onUploadSending, this))
        this.dropzone.on('success', $.proxy(this.onUploadSuccess, this))
        this.dropzone.on('error', $.proxy(this.onUploadError, this))
    }

    FileUpload.prototype.onResizeFileInfo = function(file) {
        var info,
            targetWidth,
            targetHeight

        if (!this.options.thumbnailWidth && !this.options.thumbnailWidth) {
            targetWidth = targetHeight = 100
        }
        else if (this.options.thumbnailWidth) {
            targetWidth = this.options.thumbnailWidth
            targetHeight = this.options.thumbnailWidth * file.height / file.width
        }
        else if (this.options.thumbnailHeight) {
            targetWidth = this.options.thumbnailHeight * file.height / file.width
            targetHeight = this.options.thumbnailHeight
        }

        // drawImage(image, srcX, srcY, srcWidth, srcHeight, trgX, trgY, trgWidth, trgHeight) takes an image, clips it to
        // the rectangle (srcX, srcY, srcWidth, srcHeight), scales it to dimensions (trgWidth, trgHeight), and draws it
        // on the canvas at coordinates (trgX, trgY).
        info = {
            srcX: 0,
            srcY: 0,
            srcWidth: file.width,
            srcHeight: file.height,
            trgX: 0,
            trgY: 0,
            trgWidth: targetWidth,
            trgHeight: targetHeight
        }

        return info
    }

    FileUpload.prototype.onUploadAddedFile = function(file) {
        var $object = $(file.previewElement).data('dzFileObject', file)

        // Remove any exisiting objects for single variety
        // if (!this.options.isMulti) {
        //     this.removeFileFromElement($object.siblings())
        // }

        this.evalIsPopulated()
    }

    FileUpload.prototype.onUploadSending = function(file, xhr, formData) {
        this.addExtraFormData(formData)
    }

    FileUpload.prototype.onUploadSuccess = function(file, response) {
        var $preview = $(file.previewElement),
            $img = $('.image img', $preview)

        if(response.id > 0) {

            $preview.find('.size').each(function(index, element) {
                if($(element).text().indexOf('MB') == -1) {
                    var _text = $(element).text().split(' ');

                    if(parseFloat(_text[0]) > 0) {
                        _text = Number((parseFloat(_text[0]) / 1024).toFixed(2)) + ' MB';
                    } else {
                        _text = _text[0] + ' MB';
                    }

                    $(element).html(_text);
                }
            });

            $preview.find('.app-lots.no-done').each(function(index, element) {

                    $(element).attr('name', 'lot_docs[' + response.id + ']');
                    $(element).removeClass('no-done');
                    $(element).addClass('is-done');

            });
            $preview.find('.doc-confidentiality.no-done').each(function(index, element) {

                    var name = $(element).find('input').attr('name');
                    $(element).find('input').attr('name', name + '[' + response.id + ']');
                    $(element).removeClass('no-done');
                    $(element).addClass('is-done');
                    $(element).find('input').attr('id', name + response.id );
                    $(element).find('label').attr('for', name + response.id );

                    var name = $(element).next().find('textarea').attr('name');
                    $(element).next().find('textarea').attr('name', name + '[' + response.id + ']');

            });
            $preview.find('.choose-doc-type.no-done').each(function(index, element) {
                    var name = $(element).find('select').attr('name');
                    $(element).find('select').attr('name', name + '[' + response.id + ']');
                    $(element).removeClass('no-done');
                    $(element).addClass('is-done');
                    $(element).find('select').attr('id', name + response.id );
                    $(element).find('label').attr('for', name + response.id );
            });
            $preview.find('.choose-contract-document-type.no-done').each(function(index, element) {
                var name = $(element).find('select').attr('name');
                $(element).find('select').attr('name', name + '[' + response.id + ']');
                $(element).removeClass('no-done');
                $(element).addClass('is-done');
                $(element).find('select').attr('id', name + response.id );
                $(element).find('label').attr('for', name + response.id );
            });
            $preview.find('.choose-tender-document-type.no-done').each(function(index, element) {
                var name = $(element).find('select').attr('name');
                $(element).find('select').attr('name', name + '[' + response.id + ']');
                $(element).removeClass('no-done');
                $(element).addClass('is-done');
                $(element).find('select').attr('id', name + response.id );
                $(element).find('label').attr('for', name + response.id );
            });
            $preview.find('.list_checkbox.no-done').each(function(index, element) {

                    $(element).find('.checkbox').each(function(_index, _element) {
                        var name = $(_element).find('input').attr('name');
                        var value = $(_element).find('input').attr('value');
                        $(_element).find('input').attr('name', name + '[' + response.id + ']');
                        $(_element).find('input').attr('id', name + value + response.id );
                        $(_element).find('label').attr('for', name + value + response.id );
                        if(value == 'tender') {
                            $(_element).find('input').prop('checked', true);
                        }
                    });
                    $(element).removeClass('no-done');
                    $(element).addClass('is-done');
            });

            $preview.find('.upload-remove-button').attr('data-request-data', 'file_id: '+response.id);
        }

        var _container = this.$filesContainer;

        if(_container.find('.old-file').length){
            $preview.find('.upload-remove-button').remove();
            $preview.find('.list_checkbox').hide();
        } else {
            $preview.find('.list_checkbox').show();
        }

        var _attr = _container.find('.old-file .replace-button').attr('data-request-data');
        _attr = _attr + ', new_file_id: '+response.id;
        _container.find('.old-file .replace-button').attr('data-request-data', _attr);
        _container.find('.old-file .replace-button').trigger('click');
        _container.find('.old-file').replaceWith($preview);

        $preview.addClass('is-success')

        if (response.id) {
            $preview.data('id', response.id)
            $preview.data('path', response.path)
            $('.replace-button', $preview).data('request-data', { file_id: response.id })
            $img.attr('src', response.thumb)
        }

        /*
         * Trigger change event (Compatability with october.form.js)
         */
        this.$el.closest('[data-field-name]').trigger('change.oc.formwidget');

        this.$filesContainer.find('[data-control="fileupload"]').fileUploader()
    }

    FileUpload.prototype.onUploadError = function(file, error) {
        var $preview = $(file.previewElement)
        $preview.addClass('is-error')
    }

    FileUpload.prototype.addExtraFormData = function(formData) {
        if (this.options.extraData) {
            $.each(this.options.extraData, function (name, value) {
                formData.append(name, value);
            })
        }

        var $form = this.$el.closest('form')
        if ($form.length > 0) {
            $.each($form.serializeArray(), function (index, field) {
                formData.append(field.name, field.value);
            })
        }
    }

    FileUpload.prototype.removeFileFromElement = function($element) {
        var self = this

        $element.each(function() {
            var $el = $(this),
                obj = $el.data('dzFileObject')

            if (obj) {
                self.dropzone.removeFile(obj)
            }
            else {
                $el.remove()
            }
        })
    }

    //
    // User interaction
    //

    FileUpload.prototype.onRemoveObject = function(ev) {
        var self = this,
            $object = $(ev.target).closest('.upload-object')

        $(ev.target)
            .closest('.upload-remove-button')
            .one('ajaxPromise', function(){
                $object.addClass('is-loading')
            })
            .one('ajaxDone', function(){
                self.removeFileFromElement($object)
                self.evalIsPopulated()
            })
            .request()

        ev.stopPropagation()
    }

    FileUpload.prototype.onClickSuccessObject = function(ev) {
        if ($(ev.target).closest('.meta').length) return

        var $target = $(ev.target).closest('.upload-object')
        window.open($target.data('path'))
    }

    FileUpload.prototype.onClickErrorObject = function(ev) {
        var
            self = this,
            $object = $(ev.target).closest('.upload-object'),
            errorMsg = $('[data-dz-errormessage]', $object).text()

        alert(errorMsg)

        this.removeFileFromElement($object)
        self.evalIsPopulated()
    }

    //
    // Helpers
    //

    FileUpload.prototype.evalIsPopulated = function() {
        var isPopulated = !!$('.upload-object', this.$filesContainer).length
        this.$el.toggleClass('is-populated', isPopulated)

        // Reset maxFiles counter
        if (!isPopulated) {
            this.dropzone.removeAllFiles()
        }
    }

    FileUpload.DEFAULTS = {
        url: window.location,
        uniqueId: null,
        extraData: {},
        paramName: 'file_data',
        fileTypes: null,
        template: null,
        isMulti: null,
        isPreview: null,
        thumbnailWidth: 120,
        thumbnailHeight: 120
    }

    // FILEUPLOAD PLUGIN DEFINITION
    // ============================

    var old = $.fn.fileUploader

    $.fn.fileUploader = function (option) {
        return this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.fileUpload')
            var options = $.extend({}, FileUpload.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.fileUpload', (data = new FileUpload(this, options)))
            if (typeof option == 'string') data[option].call($this)
        })
    }

    $.fn.fileUploader.Constructor = FileUpload

    // FILEUPLOAD NO CONFLICT
    // =================

    $.fn.fileUploader.noConflict = function () {
        $.fn.fileUpload = old
        return this
    }

    // FILEUPLOAD DATA-API
    // ===============
    $(document).render(function () {
        $('[data-control="fileupload"]').fileUploader()
    })

}(window.jQuery);
