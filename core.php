<?php

/** @noinspection PhpUnused */
function uspp_get_postslist( $post_type ) {
	global $user_LK;

	uspp_postlist_style();

	$manager = new USPP_Author_Postlist( [
		'post_author' => $user_LK,
		'post_type'   => $post_type,
	] );

	$content = '<div id="uspp-post-list-' . $post_type . '" class="uspp-post-list">';
	$content .= $manager->get_manager();
	$content .= '</div>';

	return $content;
}

function uspp_tab_postform() {
	return do_shortcode( '[uspp-public-form form_id="' . usp_get_option( 'uspp_id_public_form', 1 ) . '"]' );
}

function uspp_edit_post_button_html( $post_id ) {
	return '<p class="post-edit-button">'
	       . '<a title="' . __( 'Edit', 'userspace-publication' ) . '" object-id="none" href="' . get_edit_post_link( $post_id ) . '">'
	       . '<i class="uspi fa-edit"></i>'
	       . '</a>'
	       . '</p>';
}

function uspp_is_limit_editing( $post_date ) {
	$time_limit = apply_filters( 'uspp_time_editing', usp_get_option( 'uspp_time_editing' ) );

	if ( $time_limit ) {
		$hours = ( strtotime( current_time( 'mysql' ) ) - strtotime( $post_date ) ) / 3600;

		if ( $hours > $time_limit ) {
			return true;
		}
	}

	return false;
}

function uspp_get_custom_fields_edit_box( $post_id, $post_type = false, $form_id = 1 ) {
	$post = get_post( $post_id );

	$usppForm = new USPP_Public_Form( [
		'post_type' => $post->post_type,
		'post_id'   => $post_id,
		'form_id'   => $form_id,
	] );

	$fields = $usppForm->get_custom_fields();

	if ( $usppForm->is_active_field( 'post_uploader' ) ) {

		$postUploader = $usppForm->get_field( 'post_uploader' );
		$postUploader->set_prop( 'fix_editor', 'content' );

		$fields = $fields ? [ 'post_uploader' => $postUploader ] + $fields : [ 'post_uploader' => $postUploader ];
	}

	if ( ! $fields ) {
		return false;
	}

	uspp_publication_scripts();

	$content = '<div id="uspp-post-fields-admin-box">';

	foreach ( $fields as $field_id => $field ) {

		if ( ! isset( $field->slug ) ) {
			continue;
		}

		$content .= $usppForm->get_field_form( $field_id );
	}

	$content .= '</div>';

	return $content;
}

function uspp_update_post_custom_fields( $post_id, $id_form = false ) {
	require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
	require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
	require_once( ABSPATH . "wp-admin" . '/includes/media.php' );

	$post = get_post( $post_id );

	$formFields = new USPP_Public_Form_Fields( $post->post_type, [
		'form_id' => $id_form,
	] );

	$fields = $formFields->get_custom_fields();

	if ( $fields ) {
		$POST = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );

		foreach ( $fields as $field_id => $field ) {

			$value = isset( $POST[ $field_id ] ) ? $POST[ $field_id ] : false;

			if ( 'file' == $field->type ) {

				$attach_id = get_post_meta( $post_id, $field_id, 1 );

				if ( $value != $attach_id ) {
					wp_delete_attachment( $attach_id );
				}
			}

			if ( 'checkbox' == $field->type ) {
				$field_values = [];

				$count_field = count( $field->values );

				if ( $value && is_array( $value ) ) {
					foreach ( $value as $val ) {
						for ( $a = 0; $a < $count_field; $a ++ ) {
							if ( $field->values[ $a ] == $val ) {
								$field_values[] = $val;
							}
						}
					}
				}

				if ( $field_values ) {
					update_post_meta( $post_id, $field_id, $field_values );
				} else {
					delete_post_meta( $post_id, $field_id );
				}
			} else {

				if ( $value || 0 == $value ) {
					update_post_meta( $post_id, $field_id, $value );
				} else {
					if ( get_post_meta( $post_id, $field_id, 1 ) ) {
						delete_post_meta( $post_id, $field_id );
					}
				}
			}

			if ( $value ) {

				if ( 'uploader' == $field->type ) {
					foreach ( $value as $val ) {
						usp_delete_temp_media( $val );
					}
				} else if ( 'file' == $field->type ) {
					usp_delete_temp_media( $value );
				}
			}
		}
	}

	//support of uploader in admin
	if ( is_admin() && isset( $_POST['post_uploader'] ) && sanitize_text_field( wp_unslash( $_POST['post_uploader'] ) ) ) {
		global $user_ID;

		$editPost = new USPP_Edit_Post();

		$editPost->add_attachments_from_temps( $user_ID );

		$editPost->update_post_gallery();

		usp_delete_temp_media_by_args( [
			'user_id'         => $user_ID,
			'uploader_id__in' => [ 'post_uploader', 'post_thumbnail' ],
		] );
	}
}

usp_ajax_action( 'uspp_save_temp_async_uploaded_thumbnail', true );
function uspp_save_temp_async_uploaded_thumbnail() {

	$attachment_id  = isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0;
	$attachment_url = isset( $_POST['attachment_url'] ) ? sanitize_text_field( wp_unslash( $_POST['attachment_url'] ) ) : '';

	if ( ! $attachment_id || ! $attachment_url ) {
		return [
			'error' => __( 'Error', 'userspace-publication' ),
		];
	}

	uspp_update_tempgallery( $attachment_id, $attachment_url );

	return [
		'save' => true,
	];
}

function uspp_update_tempgallery( $attach_id, $attach_url ) {
	global $user_ID;

	$user_id = ( $user_ID ) ? $user_ID : ( ! empty( $_COOKIE['PHPSESSID'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['PHPSESSID'] ) ) : '' );

	$temp_gal = get_site_option( 'uspp_tempgallery' );

	if ( ! $temp_gal ) {
		$temp_gal = [];
	}

	$temp_gal[ $user_id ][] = [
		'ID'  => $attach_id,
		'url' => $attach_url,
	];

	update_site_option( 'uspp_tempgallery', $temp_gal );

	return $temp_gal;
}

function uspp_get_attachment_box( $attachment_id, $mime = 'image', $addToClick = true ) {

	if ( 'image' == $mime ) {

		$small_url = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );

		$image = '<img alt="" src="' . $small_url[0] . '">';

		if ( $addToClick ) {
			$default = usp_get_option( 'uspp_default_thumb' );
			if ( $default ) {
				$sizes = wp_get_attachment_image_src( $attachment_id, $default );
			} else {
				$sizes = $small_url;
			}

			$full_url  = wp_get_attachment_image_src( $attachment_id, 'full' );
			$act_sizes = wp_constrain_dimensions( $full_url[1], $full_url[2], $sizes[1], $sizes[2] );

			// uspp_add_image_in_form not exists js function
			return '<a onclick="uspp_add_image_in_form(this,\'<a href=' . $full_url[0] . '><img height=' . $act_sizes[1] . ' width=' . $act_sizes[0] . ' class=usps__text-center  src=' . $full_url[0] . '></a>\');return false;" href="#">' . $image . '</a>';
		} else {
			return $image;
		}
	} else {

		$image = wp_get_attachment_image( $attachment_id, [ 100, 100 ], true );

		if ( $addToClick ) {

			$_post = get_post( $attachment_id );

			$url = wp_get_attachment_url( $attachment_id );

			// uspp_add_image_in_form not exists js function
			return '<a href="#" onclick="uspp_add_image_in_form(this,\'<a href=' . $url . '>' . $_post->post_title . '</a>\');return false;">' . $image . '</a>';
		} else {
			return $image;
		}
	}
}

// todo: need this?
function uspp_get_html_attachment( $attach_id, $mime_type, $addToClick = true ) {

	$mime = explode( '/', $mime_type );

	$content = "<li id='attachment-$attach_id' class='post-attachment attachment-$mime[0]' data-mime='$mime[0]' data-attachment-id='$attach_id'>";
	$content .= uspp_button_fast_delete_post( $attach_id );
	$content .= sprintf( "<label>%s</label>", apply_filters( 'uspp_post_attachment_html', uspp_get_attachment_box( $attach_id, $mime[0], $addToClick ), $attach_id, $mime ) );
	$content .= "</li>";

	return $content;
}

/**
 * Get button to fast edit post
 *
 * @param int $post_id id of the post to edit.
 *
 * @return string html button.
 * @since 1.0
 *
 */
function uspp_button_fast_edit_post( $post_id ) {
	if ( ! $post_id ) {
		return false;
	}

	usp_dialog_scripts();
	uspp_publication_scripts();

	$args = [
		'class'   => 'uspp-edit-post uspp-service-button',
		'data'    => [ 'post' => $post_id ],
		'onclick' => 'uspp_edit_post(this); return false;',
		'icon'    => 'fa-edit',
	];

	return usp_get_button( $args );
}

/**
 * Get button to fast delete post with confirm
 *
 * @param int $post_id id of the post to delete.
 *
 * @return string html button.
 * @since 1.0
 *
 */
function uspp_button_fast_delete_post( $post_id ) {
	if ( ! $post_id ) {
		return false;
	}

	usp_dialog_scripts();
	uspp_publication_scripts();

	$args = [
		'class'   => 'uspp-delete-post uspp-service-button',
		'data'    => [ 'post' => $post_id ],
		'onclick' => 'return confirm("' . __( 'Are you sure?', 'userspace-publication' ) . '")? uspp_delete_post(this): false;',
		'icon'    => 'fa-trash',
	];

	return usp_get_button( $args );
}
