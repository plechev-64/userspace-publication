var rcl_public_form = {
	required: new Array()
};

jQuery( document ).ready( function( $ ) {

	if ( USPUploaders.isset( 'post_thumbnail' ) ) {

		USPUploaders.get( 'post_thumbnail' ).appendInGallery = function( file ) {

			jQuery( '#usp-upload-gallery-' + this.uploader_id ).html( '' ).append( file.thumbnail.html ).animateCss( 'flipInX' );
			jQuery( '#usp-upload-gallery-post_uploader' ).append( file.postmedia );
			jQuery( '#usp-upload-gallery-post_uploader div' ).last().animateCss( 'flipInX' );

		};

		if ( USPUploaders.isset( 'post_uploader' ) ) {

			USPUploaders.get( 'post_thumbnail' ).filterErrors = function( errors, files, uploader ) {

				var postUploader = USPUploaders.get( 'post_uploader' );

				var inGalleryNow = jQuery( '#usp-upload-gallery-post_uploader .gallery-attachment' ).length + 1;

				if ( inGalleryNow > postUploader.options.max_files ) {
					errors.push( USP.errors.file_max_num + '. Max: ' + postUploader.options.max_files );
				}

				return errors;
			};

		}

	}

	$( '.uspp-public-form #insert-media-button' ).click( function( e ) {

		var editor = $( this ).data( 'editor' );

		var parent_id = ( ( rcl_url_params['rcl-post-edit'] ) ) ? rcl_url_params['rcl-post-edit'] : 0;

		wp.media.model.settings.post.id = parent_id;

		wp.media.featuredImage.set = function( thumbnail_id ) {

			rcl_get_post_thumbnail_html( thumbnail_id );

		};

		wp.media.editor.open( editor );

		return false;

	} );

	jQuery( '#uspp-delete-post .delete-toggle' ).click( function() {
		jQuery( this ).next().toggle( 'fast' );
		return false;
	} );

	jQuery( 'form[name="public_post"] input[name="rcl-edit-post"],form[name="public_post"] input[name="add_new_task"]' ).click( function() {
		var error = 0;
		jQuery( 'form[name="public_post"]' ).find( ':input' ).each( function() {
			for ( var i = 0; i < field.length; i++ ) {
				if ( jQuery( this ).attr( 'name' ) == field[i] ) {
					if ( jQuery( this ).val() == '' ) {
						jQuery( this ).attr( 'style', 'border:1px solid red !important' );
						error = 1;
					} else {
						jQuery( this ).attr( 'style', 'border:1px solid #E6E6E6 !important' );
					}
				}
			}
		} );
		if ( error == 0 )
			return true;
		else
			return false;
	} );

} );

usp_add_action( 'uspp_init_public_form', 'rcl_setup_async_upload' );
function rcl_setup_async_upload() {

	if ( typeof wp == 'undefined' || !wp.Uploader )
		return false;

	jQuery.extend( wp.Uploader.prototype, {
		success: function( attachment ) {
			if ( attachment.attributes.uploadedTo )
				return false;
			usp_ajax( {
				data: {
					action: 'rcl_save_temp_async_uploaded_thumbnail',
					attachment_id: attachment.id,
					attachment_url: attachment.attributes.url
				}
			} );
		}
	} );

}

usp_add_action( 'usp_init', 'uspp_init_click_post_thumbnail' );
function uspp_init_click_post_thumbnail() {
	jQuery( ".uspp-public-form" ).on( 'click', '.thumb-foto', function() {
		jQuery( ".uspp-public-form .thumb-foto" ).removeAttr( "checked" );
		jQuery( this ).attr( "checked", 'checked' );
	} );
}

function rcl_get_post_thumbnail_html( thumbnail_id ) {

	usp_preloader_show( jQuery( '.uspp-public-form' ) );

	usp_ajax( {
		data: {
			action: 'rcl_get_post_thumbnail_html',
			thumbnail_id: thumbnail_id
		},
		success: function( result ) {
			jQuery( '#uspp-thumbnail-post .thumbnail-image' ).html( result['thumbnail_image'] ).animateCss( 'flipInX' );
			jQuery( '#uspp-thumbnail-post .thumbnail-id' ).val( thumbnail_id );
		}
	} );

}

function rcl_remove_post_thumbnail() {
	jQuery( '#uspp-thumbnail-post .thumbnail-image' ).animateCss( 'flipOutX', function( e ) {
		jQuery( e ).empty();
	} );
	jQuery( '#uspp-thumbnail-post .thumbnail-id' ).val( '0' );
}

function uspp_delete_post( element ) {

	usp_preloader_show( jQuery( element ).parents( 'li' ) );

	var objectData = {
		action: 'rcl_ajax_delete_post',
		post_id: jQuery( element ).data( 'post' )
	};

	usp_ajax( {
		data: objectData,
		success: function( data ) {

			jQuery( '#' + data['post_type'] + '-' + objectData.post_id ).animateCss( 'flipOutX', function( e ) {
				jQuery( e ).remove();
			} );

			data.post_id = objectData.post_id;

			usp_do_action( 'uspp_delete_post', data );

		}
	} );

	return false;
}

usp_add_action( 'uspp_delete_post', 'uspp_delete_thumbnail_attachment' );
function uspp_delete_thumbnail_attachment( data ) {

	if ( data['post_type'] != 'attachment' )
		return false;

	if ( jQuery( '#uspp-thumbnail-post' ).length ) {

		var currentThumbId = jQuery( '#uspp-thumbnail-post .thumbnail-id' ).val();

		if ( currentThumbId == data['post_id'] )
			rcl_remove_post_thumbnail();
	}

}

function rcl_edit_post( element ) {

	usp_preloader_show( jQuery( '#lk-content' ) );

	usp_ajax( {
		data: {
			action: 'rcl_get_edit_postdata',
			post_id: jQuery( element ).data( 'post' )
		},
		success: function( data ) {

			if ( data['result'] == 100 ) {

				ssi_modal.show( {
					title: USP.local.edit_box_title,
					className: 'rcl-edit-post-form',
					sizeClass: 'small',
					buttons: [ {
							label: USP.local.save,
							closeAfter: false,
							method: function() {

								usp_preloader_show( '#rcl-popup-content form' );

								usp_ajax( {
									data: 'action=rcl_edit_postdata&' + jQuery( '#rcl-popup-content form' ).serialize()
								} );

							}
						}, {
							label: USP.local.close,
							closeAfter: true
						} ],
					content: '<div id="rcl-popup-content">' + data['content'] + '</div>'
				} );
			}
		}
	} );

}

function rcl_preview( e ) {

	var submit = jQuery( e );
	var formblock = submit.parents( 'form' );
	var post_type = formblock.data( 'post_type' );

	if ( !rcl_check_required_fields( formblock ) )
		return false;

	usp_preloader_show( formblock );

	var iframe = jQuery( "#post_content_ifr" ).contents().find( "#tinymce" ).html();
	if ( iframe ) {
		tinyMCE.triggerSave();
		formblock.find( 'textarea[name="post_content"]' ).html( iframe );
	}

	var button_draft = formblock.find( 'input[name="button-draft"]' ).val();
	var button_delete = formblock.find( 'input[name="button-delete"]' ).val();
	var button_preview = formblock.find( 'input[name="button-preview"]' ).val();

	usp_ajax( {
		data: 'action=rcl_preview_post&publish=0&' + formblock.serialize(),
		error: function( data ) {
			submit.attr( 'disabled', false ).val( USP.local.preview );
		},
		success: function( data ) {

			if ( data['content'] ) {

				var buttons = [ ];

				buttons[0] = {
					className: 'btn btn-primary',
					label: USP.local.edit,
					closeAfter: true,
					method: function() {
						submit.attr( 'disabled', false ).val( USP.local.preview );
					}
				};

				if ( button_draft ) {
					buttons[1] = {
						className: 'btn btn-danger',
						label: USP.local.save_draft,
						closeAfter: false,
						method: function() {
							rcl_save_draft();
						}
					};
				}

				var i = buttons.length;
				buttons[i] = {
					className: 'btn btn-danger',
					label: USP.local.publish,
					closeAfter: false,
					method: function() {
						uspp_publish();
					}
				};

				ssi_modal.show( {
					sizeClass: 'small',
					title: USP.local.preview,
					className: 'uspp-preview-post',
					buttons: buttons,
					content: '<div id="rcl-preview">' + data['content'] + '</div>'
				} );

				return true;
			}

		}
	} );

	return false;

}

function rcl_save_draft( e ) {

	if ( !e )
		e = jQuery( '#rcl-draft-post' );

	if ( !rcl_check_publish( e ) )
		return false;

	jQuery( e ).after( '<input type="hidden" name="save-as-draft" value=1>' );

	jQuery( 'form.uspp-public-form' ).submit();
}

function rcl_check_publish( e ) {

	var submit = jQuery( e );
	var formblock = submit.parents( 'form' );

	if ( !rcl_check_required_fields( formblock ) )
		return false;

	return true;
}

function uspp_publish( e ) {

	var formblock = ( e ) ? jQuery( e ).parents( 'form' ) : jQuery( 'form.uspp-public-form' );

	var post_type = formblock.data( 'post_type' );

	if ( !rcl_check_required_fields( formblock ) )
		return false;

	usp_preloader_show( formblock );

	var iframe = jQuery( "#post_content_ifr" ).contents().find( "#tinymce" ).html();
	if ( iframe ) {
		tinyMCE.triggerSave();
		formblock.find( 'textarea[name="post_content"]' ).html( iframe );
	}

	usp_ajax( {
		data: 'action=rcl_preview_post&publish=1&' + formblock.serialize(),
		success: function( data ) {

			if ( data.submit ) {
				usp_preloader_show( formblock );
				jQuery( 'form.uspp-public-form' ).submit();
			}

			usp_do_action( 'uspp_publish', data, e );

		}
	} );

}

function rcl_check_required_fields( form ) {

	var rclFormFactory = new USPForm( form );

	rclFormFactory.addChekForm( 'checkCats', {
		isValid: function() {
			var valid = true;
			if ( this.form.find( 'input[name="cats[]"]' ).length > 0 ) {
				if ( form.find( 'input[name="cats[]"]:checked' ).length == 0 ) {
					this.shake( form.find( 'input[name="cats[]"]' ) );
					this.addError( 'checkCats', USP.errors.cats_important );
					valid = false;
				} else {
					this.noShake( form.find( 'input[name="cats[]"]' ) );
				}
			}
			return valid;
		}

	} );

	return rclFormFactory.validate();

}

function rcl_get_prefiew_content( formblock, iframe ) {
	formblock.find( 'textarea[name="post_content"]' ).html( iframe );
	return formblock.serialize();
}

function rcl_preview_close( e ) {
	ssi_modal.close();
}

function uspp_init_public_form( post ) {

	usp_do_action( 'uspp_init_public_form', post );

	var post_id = post.post_id;
	var post_type = post.post_type;
	var ext_types = post.ext_types;
	var size_files = parseInt( post.size_files, 10 );
	var max_files = parseInt( post.max_files, 10 );
	var post_status = 'new';

	if ( post.post_status )
		post_status = post.post_status;

	jQuery( 'form.uspp-public-form' ).find( ':required' ).each( function() {
		var i = rcl_public_form.required.length;
		rcl_public_form.required[i] = jQuery( this ).attr( 'name' );
	} );

	var maxsize = size_files * 1024 * 1024;

	usp_add_dropzone( '#rcl-public-dropzone-' + post_type );

	jQuery( '#upload-public-form-' + post_type ).fileupload( {
		dataType: 'json',
		type: 'POST',
		dropZone: jQuery( '#rcl-public-dropzone-' + post_type ),
		url: USP.ajaxurl,
		formData: {
			action: 'rcl_imagepost_upload',
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
		send: function( e, data ) {
			var error = false;
			usp_preloader_show( 'form.uspp-public-form' );
			var cnt_now = jQuery( '#temp-files-' + post_type + ' li' ).length;
			jQuery.each( data.files, function( index, file ) {
				cnt_now++;
				if ( cnt_now > max_files ) {
					usp_notice( USP.local.allowed_downloads + ' ' + max_files, 'error', 10000 );
					error = true;
				}
				if ( file['size'] > maxsize ) {
					usp_notice( USP.local.upload_size_public + ' ' + size_files + ' MB', 'error', 10000 );
					error = true;
				}
			} );
			if ( error ) {
				usp_preloader_hide();
				return false;
			}
		},
		done: function( e, data ) {

			usp_preloader_hide();

			jQuery.each( data.result, function( index, file ) {
				if ( data.result['error'] ) {
					usp_notice( data.result['error'], 'error', 10000 );
					usp_preloader_hide();
					return false;
				}

				if ( file['string'] ) {
					jQuery( '#temp-files-' + post_type ).append( file['string'] );
					jQuery( '#temp-files-' + post_type + ' li' ).last().animateCss( 'flipInX' );
				}
			} );

		}
	} );
}

function rcl_init_thumbnail_uploader( e, options ) {

	var form = jQuery( e ).parents( 'form' );

	var post_id = form.data( 'post_id' );
	var post_type = form.data( 'post_type' );
	var ext_types = 'jpg,png,jpeg';
	var maxsize_mb = options.size;

	var maxsize = maxsize_mb * 1024 * 1024;

	jQuery( '#uspp-thumbnail-uploader' ).fileupload( {
		dataType: 'json',
		type: 'POST',
		url: USP.ajaxurl,
		formData: {
			action: 'rcl_imagepost_upload',
			post_type: post_type,
			post_id: post_id,
			ext_types: ext_types,
			ajax_nonce: USP.nonce
		},
		singleFileUploads: true,
		autoUpload: true,
		send: function( e, data ) {

			var error = false;

			usp_preloader_show( 'form.uspp-public-form' );

			jQuery.each( data.files, function( index, file ) {

				if ( file['size'] > maxsize ) {
					usp_notice( USP.local.upload_size_public + ' ' + maxsize_mb + ' MB', 'error', 10000 );
					error = true;
				}

			} );

			if ( error ) {
				usp_preloader_hide();
				return false;
			}

		},
		done: function( e, data ) {
			jQuery.each( data.result, function( index, file ) {

				usp_preloader_hide();

				if ( data.result['error'] ) {
					usp_notice( data.result['error'], 'error', 10000 );
					return false;
				}

				if ( file['string'] ) {
					jQuery( '#temp-files-' + post_type ).append( file['string'] );
					jQuery( '#temp-files-' + post_type + ' li' ).last().animateCss( 'flipInX' );
					jQuery( '#uspp-thumbnail-post .thumbnail-image' ).html( file['thumbnail_image'] ).animateCss( 'flipInX' );
					jQuery( '#uspp-thumbnail-post .thumbnail-id' ).val( file['attachment_id'] );
				}
			} );


		}
	} );

}

function rcl_set_post_thumbnail( attach_id, parent_id, e ) {

	usp_preloader_show( jQuery( '.gallery-attachment-' + attach_id ) );

	usp_ajax( {
		data: {
			action: 'rcl_set_post_thumbnail',
			thumbnail_id: attach_id,
			parent_id: parent_id,
			form_id: jQuery( 'form.uspp-public-form input[name="form_id"]' ).val(),
			post_type: jQuery( 'form.uspp-public-form input[name="post_type"]' ).val()
		},
		success: function( result ) {
			jQuery( '#usp-upload-gallery-post_thumbnail' ).html( result.html ).animateCss( 'flipInX' );
		}
	} );

}

function rcl_switch_attachment_in_gallery( attachment_id, e ) {

	var button = jQuery( '.rcl-switch-gallery-button-' + attachment_id );

	if ( button.children( 'i' ).hasClass( 'fa-toggle-off' ) ) {
		button.children( 'input' ).val( attachment_id );
	} else {
		button.children( 'input' ).val( '' );
	}

	button.children( 'i' ).toggleClass( 'fa-toggle-off fa-toggle-on' );

}