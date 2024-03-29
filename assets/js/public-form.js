/* global wp, USPUploaders, USP, USPForm, tinyMCE, ssi_modal */
var uspp_public_form = {
    required: new Array()
};

jQuery(document).ready(function ($) {

    if (USPUploaders.isset('post_thumbnail')) {

        USPUploaders.get('post_thumbnail').appendInGallery = function (file) {

            jQuery('#usp-media-' + this.uploader_id).html('').append(file.thumbnail.html).animateCss('flipInX');
            jQuery('#usp-media-post_uploader').append(file.postmedia);
            jQuery('#usp-media-post_uploader div').last().animateCss('flipInX');

        };

        if (USPUploaders.isset('post_uploader')) {
            USPUploaders.get('post_thumbnail').filterErrors = function (errors) {
                let postUploader = USPUploaders.get('post_uploader');

                let inGalleryNow = jQuery('#usp-media-post_uploader .usp-media__item').length + 1;

                if (inGalleryNow > postUploader.options.max_files) {
                    errors.push(USP.errors.file_max_num + '. Max: ' + postUploader.options.max_files);
                }

                return errors;
            };

        }

    }

    $('.uspp-public-form #insert-media-button').on('click', function (e) {
        let editor = $(this).data('editor');

        wp.media.model.settings.post.id = ((usp_url_params['uspp-post-edit'])) ? usp_url_params['uspp-post-edit'] : 0;

        wp.media.featuredImage.set = function (thumbnail_id) {
            uspp_get_post_thumbnail_html(thumbnail_id);
        };

        wp.media.editor.open(editor);

        return false;
    });

    jQuery('.uspp-delete-toggle').on('click', function () {
        jQuery(this).next().toggle('fast');
        return false;
    });

    jQuery('form[name="public_post"] input[name="uspp-edit-post"],form[name="public_post"] input[name="add_new_task"]').on('click', function () {
        let error = 0;
        jQuery('form[name="public_post"]').find(':input').each(function () {
            for (let i = 0; i < field.length; i++) {
                if (jQuery(this).attr('name') == field[i]) {
                    if (jQuery(this).val() == '') {
                        jQuery(this).attr('style', 'border:1px solid red !important');
                        error = 1;
                    } else {
                        jQuery(this).attr('style', 'border:var(--uspLine200) !important');
                    }
                }
            }
        });
        if (error == 0)
            return true;
        else
            return false;
    });

});

usp_add_action('uspp_init_public_form', 'uspp_setup_async_upload');

function uspp_setup_async_upload() {
    if (typeof wp == 'undefined' || !wp.Uploader)
        return false;

    jQuery.extend(wp.Uploader.prototype, {
        success: function (attachment) {
            if (attachment.attributes.uploadedTo)
                return false;
            usp_ajax({
                data: {
                    action: 'uspp_save_temp_async_uploaded_thumbnail',
                    attachment_id: attachment.id,
                    attachment_url: attachment.attributes.url
                }
            });
        }
    });
}

function uspp_get_post_thumbnail_html(thumbnail_id) {
    usp_preloader_show(jQuery('.uspp-public-form'));

    usp_ajax({
        data: {
            action: 'uspp_get_post_thumbnail_html',
            thumbnail_id: thumbnail_id
        },
        success: function (result) {
            jQuery('#uspp-thumbnail-post .thumbnail-image').html(result['thumbnail_image']).animateCss('flipInX');
            jQuery('#uspp-thumbnail-post .thumbnail-id').val(thumbnail_id);
        }
    });
}

function uspp_remove_post_thumbnail() {
    jQuery('#uspp-thumbnail-post .thumbnail-image').animateCss('flipOutX', function (e) {
        jQuery(e).empty();
    });

    jQuery('#uspp-thumbnail-post .thumbnail-id').val('0');
}

function uspp_delete_post(element) {
    usp_preloader_show(jQuery(element).parents('li'));

    let objectData = {
        action: 'uspp_ajax_delete_post',
        post_id: jQuery(element).data('post')
    };

    usp_ajax({
        data: objectData,
        success: function (data) {
            jQuery('#' + data['post_type'] + '-' + objectData.post_id).animateCss('flipOutX', function (e) {
                jQuery(e).remove();
            });

            data.post_id = objectData.post_id;

            usp_do_action('uspp_delete_post', data);
        }
    });

    return false;
}

usp_add_action('uspp_delete_post', 'uspp_delete_thumbnail_attachment');

function uspp_delete_thumbnail_attachment(data) {
    if (data['post_type'] != 'attachment')
        return false;

    if (jQuery('#uspp-thumbnail-post').length) {
        let currentThumbId = jQuery('#uspp-thumbnail-post .thumbnail-id').val();

        if (currentThumbId == data['post_id'])
            uspp_remove_post_thumbnail();
    }
}

// noinspection JSUnusedGlobalSymbols
function uspp_edit_post(element) {
    usp_preloader_show(jQuery('#lk-content'));

    usp_ajax({
        data: {
            action: 'uspp_get_edit_post_data',
            post_id: jQuery(element).data('post')
        },
        success: function (data) {
            if (data['result'] == 100) {
                ssi_modal.show({
                    title: USP.local.edit_box_title,
                    className: 'uspp-edit-post-form',
                    sizeClass: 'small',
                    buttons: [{
                        label: USP.local.save,
                        closeAfter: false,
                        method: function () {

                            usp_preloader_show('#usp-popup-content form');

                            usp_ajax({
                                data: 'action=uspp_edit_post_data&' + jQuery('#usp-popup-content form').serialize()
                            });

                        }
                    }, {
                        label: USP.local.close,
                        closeAfter: true
                    }],
                    content: '<div id="usp-popup-content">' + data['content'] + '</div>'
                });
            }
        }
    });
}

// noinspection JSUnusedGlobalSymbols
function uspp_preview(e) {
    let submit = jQuery(e);
    let formBlock = submit.parents('form');

    if (!uspp_check_required_fields(formBlock))
        return false;

    usp_preloader_show(formBlock);

    let iframe = jQuery("#post_content_ifr").contents().find("#tinymce").html();
    if (iframe) {
        tinyMCE.triggerSave();
        formBlock.find('textarea[name="post_content"]').html(iframe);
    }

    let button_draft = formBlock.find('input[name="button-draft"]').val();

    usp_ajax({
        data: 'action=uspp_preview_post&publish=0&' + formBlock.serialize(),
        error: function () {
            submit.attr('disabled', false).val(USP.local.preview);
        },
        success: function (data) {
            if (data['content']) {
                let buttons = [];

                buttons[0] = {
                    className: 'btn btn-primary',
                    label: USP.local.edit,
                    closeAfter: true,
                    method: function () {
                        submit.attr('disabled', false).val(USP.local.preview);
                    }
                };

                if (button_draft) {
                    buttons[1] = {
                        className: 'btn btn-danger',
                        label: USP.local.save_draft,
                        closeAfter: false,
                        method: function () {
                            uspp_save_draft();
                        }
                    };
                }

                let i = buttons.length;
                buttons[i] = {
                    className: 'btn btn-danger',
                    label: USP.local.publish,
                    closeAfter: false,
                    method: function () {
                        uspp_publish();
                    }
                };

                ssi_modal.show({
                    sizeClass: 'medium',
                    title: USP.local.preview,
                    className: 'uspp-preview-post',
                    buttons: buttons,
                    content: '<div id="usp-preview">' + data['content'] + '</div>'
                });

                return true;
            }

        }
    });

    return false;
}

function uspp_save_draft(e) {
    if (!e)
        e = jQuery('#uspp-draft-post');

    if (!uspp_check_publish(e))
        return false;

    jQuery(e).after('<input type="hidden" name="save-as-draft" value=1>');

    jQuery('form.uspp-public-form').submit();
}

function uspp_check_publish(e) {
    let submit = jQuery(e);
    let formBlock = submit.parents('form');

    if (!uspp_check_required_fields(formBlock))
        return false;

    return true;
}

function uspp_publish(e) {
    let formBlock = (e) ? jQuery(e).parents('form') : jQuery('form.uspp-public-form');

    if (!uspp_check_required_fields(formBlock))
        return false;

    usp_preloader_show(formBlock);

    let iframe = jQuery("#post_content_ifr").contents().find("#tinymce").html();
    if (iframe) {
        tinyMCE.triggerSave();
        formBlock.find('textarea[name="post_content"]').html(iframe);
    }

    usp_ajax({
        data: 'action=uspp_preview_post&publish=1&' + formBlock.serialize(),
        success: function (data) {
            if (data.submit) {
                usp_preloader_show(formBlock);
                jQuery('form.uspp-public-form').submit();
            }

            usp_do_action('uspp_publish', data, e);
        }
    });
}

function uspp_check_required_fields(form) {
    let usppFormFactory = new USPForm(form);

    usppFormFactory.addChekForm('checkCats', {
        isValid: function () {
            let valid = true;
            if (this.form.find('input[name="cats[]"]').length > 0) {
                if (form.find('input[name="cats[]"]:checked').length == 0) {
                    this.shake(form.find('input[name="cats[]"]'));
                    this.addError('checkCats', USP.errors.cats_important);
                    valid = false;
                } else {
                    this.noShake(form.find('input[name="cats[]"]'));
                }
            }
            return valid;
        }

    });

    return usppFormFactory.validate();
}

// noinspection JSUnusedGlobalSymbols
function uspp_get_preview_content(formBlock, iframe) {
    formBlock.find('textarea[name="post_content"]').html(iframe);

    return formBlock.serialize();
}

// noinspection JSUnusedGlobalSymbols
function uspp_preview_close() {
    ssi_modal.close();
}

function uspp_init_public_form(post) {
    usp_do_action('uspp_init_public_form', post);

    let post_id = post.post_id;
    let post_type = post.post_type;
    let ext_types = post.ext_types;
    let size_files = parseInt(post.size_files, 10);
    let max_files = parseInt(post.max_files, 10);
    let post_status = 'new';

    if (post.post_status)
        post_status = post.post_status;

    jQuery('form.uspp-public-form').find(':required').each(function () {
        let i = uspp_public_form.required.length;
        uspp_public_form.required[i] = jQuery(this).attr('name');
    });

    let maxsize = size_files * 1024 * 1024;

    usp_add_dropzone('#uspp-public-dropzone-' + post_type);

    jQuery('#upload-public-form-' + post_type).fileupload({
        dataType: 'json',
        type: 'POST',
        dropZone: jQuery('#uspp-public-dropzone-' + post_type),
        url: USP.ajaxurl,
        formData: {
            action: 'uspp_image_post_upload',
            post_type: post_type,
            post_id: post_id,
            form_id: post.form_id,
            ext_types: ext_types,
            size_files: size_files,
            max_files: max_files,
            ajax_nonce: USP.nonce
        },
        singleFileUploads: false,
        autoUpload: true,
        send: function (e, data) {
            let error = false;
            usp_preloader_show('form.uspp-public-form');
            let cnt_now = jQuery('#temp-files-' + post_type + ' li').length;
            jQuery.each(data.files, function (index, file) {
                cnt_now++;
                if (cnt_now > max_files) {
                    usp_notice(USP.local.allowed_downloads + ' ' + max_files, 'error', 10000);
                    error = true;
                }
                if (file['size'] > maxsize) {
                    usp_notice(USP.local.upload_size_public + ' ' + size_files + ' MB', 'error', 10000);
                    error = true;
                }
            });
            if (error) {
                usp_preloader_hide();
                return false;
            }
        },
        done: function (e, data) {
            usp_preloader_hide();

            jQuery.each(data.result, function (index, file) {
                if (data.result['error']) {
                    usp_notice(data.result['error'], 'error', 10000);
                    usp_preloader_hide();
                    return false;
                }

                if (file['string']) {
                    jQuery('#temp-files-' + post_type).append(file['string']);
                    jQuery('#temp-files-' + post_type + ' li').last().animateCss('flipInX');
                }
            });
        }
    });
}

// noinspection JSUnusedGlobalSymbols
function uspp_set_post_thumbnail(attach_id, parent_id) {
    usp_preloader_show(jQuery('.usp-media__item-' + attach_id));

    usp_ajax({
        data: {
            action: 'uspp_set_post_thumbnail',
            thumbnail_id: attach_id,
            parent_id: parent_id,
            form_id: jQuery('form.uspp-public-form input[name="form_id"]').val(),
            post_type: jQuery('form.uspp-public-form input[name="post_type"]').val()
        },
        success: function (result) {
            jQuery('#usp-media-post_thumbnail').html(result.html).animateCss('flipInX');
        }
    });
}

// noinspection JSUnusedGlobalSymbols
function uspp_switch_attachment_in_gallery(attachment_id) {
    let button = jQuery('.uspp-switch-gallery-button-' + attachment_id);

    if (button.children('i').hasClass('fa-toggle-off')) {
        button.children('input').val(attachment_id);
    } else {
        button.children('input').val('');
    }

    button.children('i').toggleClass('fa-toggle-off fa-toggle-on');
}
