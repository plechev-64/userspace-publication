<?php

// deleting photos attached to the publication via the plugin loader
usp_ajax_action( 'uspp_ajax_delete_post', true );
function uspp_ajax_delete_post() {
	usp_verify_ajax_nonce();

	global $user_ID;

	$user_id = ( $user_ID ) ? $user_ID : ( ! empty( $_COOKIE['PHPSESSID'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['PHPSESSID'] ) ) : 0 );

	$post_id = ( isset( $_POST['post_id'] ) ) ? intval( $_POST['post_id'] ) : false;

	if ( ! $post_id ) {
		return;
	}

	if ( ! current_user_can( 'delete_posts', $post_id ) ) {
		$log['error'] = __( 'Deletion failed!', 'userspace-publication' );

		wp_send_json( $log );
	}

	$temps    = get_site_option( 'uspp_tempgallery' );
	$temp_gal = $temps[ $user_id ];

	if ( $temp_gal ) {
		$new_temp = false;
		foreach ( ( array ) $temp_gal as $key => $gal ) {
			if ( $gal['ID'] == $post_id ) {
				unset( $temp_gal[ $key ] );
			}
		}
		foreach ( ( array ) $temp_gal as $t ) {
			$new_temp[] = $t;
		}

		if ( $new_temp ) {
			$temps[ $user_id ] = $new_temp;
		} else {
			unset( $temps[ $user_id ] );
		}
	}

	update_site_option( 'uspp_tempgallery', $temps );

	$post = get_post( $post_id );

	if ( ! $post ) {
		$log['success']   = __( 'Material successfully removed!', 'userspace-publication' );
		$log['post_type'] = 'attachment';
	} else {

		$res = wp_delete_post( $post->ID );

		if ( $res ) {
			$log['success']   = __( 'Material successfully removed!', 'userspace-publication' );
			$log['post_type'] = $post->post_type;
		} else {
			$log['error'] = __( 'Deletion failed!', 'userspace-publication' );
		}
	}

	wp_send_json( $log );
}

// calling a quick form for editing a publication
usp_ajax_action( 'uspp_get_edit_post_data' );
function uspp_get_edit_post_data() {
	usp_verify_ajax_nonce();

	$post_id = ( isset( $_POST['post_id'] ) ) ? intval( $_POST['post_id'] ) : false;

	if ( $post_id && current_user_can( 'edit_post', $post_id ) ) {
		$post = get_post( $post_id );

		$log['result']  = 100;
		$log['content'] = "
        <form id='uspp-edit-form' method='post'>
                <label>" . __( "Name", 'userspace-publication' ) . ":</label>
                 <input type='text' name='post_title' value='$post->post_title'>
                 <label>" . __( "Description", 'userspace-publication' ) . ":</label>
                 <textarea name='post_content' rows='10'>$post->post_content</textarea>
                 <input type='hidden' name='post_id' value='$post_id'>
        </form>";
	} else {
		$log['error'] = __( 'Failed to get the data', 'userspace-publication' );
	}

	wp_send_json( $log );
}

// saving changes in a quick edit form
usp_ajax_action( 'uspp_edit_post_data' );
function uspp_edit_post_data() {
	usp_verify_ajax_nonce();

	$post_id                    = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
	$post_array                 = [];
	$post_array['post_title']   = isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : '';
	$post_array['post_content'] = isset( $_POST['post_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['post_content'] ) ) : '';

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json( [ 'error' => __( 'Error', 'userspace-publication' ) ] );
	}

	$post_array = apply_filters( 'uspp_pre_edit_post', $post_array );

	global $wpdb;

	// phpcs:ignore
	$result = $wpdb->update(
		$wpdb->posts, $post_array, [ 'ID' => $post_id ]
	);

	if ( ! $result ) {
		wp_send_json( [
			'error' => __( 'Changes to be saved not found', 'userspace-publication' ),
		] );
	}

	wp_send_json( [
		'success' => __( 'Publication updated', 'userspace-publication' ),
		'dialog'  => [ 'close' ],
	] );
}

function uspp_edit_post() {
	$edit = new USPP_Edit_Post();
	$edit->update_post();
}

// select tags based on the entered values
add_action( 'wp_ajax_uspp_get_like_tags', 'uspp_get_like_tags', 10 );
add_action( 'wp_ajax_nopriv_uspp_get_like_tags', 'uspp_get_like_tags', 10 );
function uspp_get_like_tags() {
	usp_verify_ajax_nonce();

	if ( empty( $_POST['query'] ) || empty( $_POST['taxonomy'] ) ) {
		wp_send_json( [ [ 'id' => '' ] ] );
	}

	$query    = sanitize_text_field( wp_unslash( $_POST['query'] ) );
	$taxonomy = sanitize_key( $_POST['taxonomy'] );

	$terms = get_terms( $taxonomy, [ 'hide_empty' => false, 'name__like' => $query ] );

	$tags = [];
	foreach ( $terms as $key => $term ) {
		$tags[ $key ]['id']   = $term->name;
		$tags[ $key ]['name'] = $term->name;
	}

	wp_send_json( $tags );
}

add_filter( 'uspp_preview_post_content', 'usp_add_registered_scripts' );

usp_ajax_action( 'uspp_preview_post', true );
function uspp_preview_post() {
	usp_verify_ajax_nonce();

	usp_reset_wp_dependencies();

	$data_post_id    = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$data_user_email = ! empty( $_POST['email-user'] ) ? sanitize_email( wp_unslash( $_POST['email-user'] ) ) : '';
	$data_user_login = ! empty( $_POST['name-user'] ) ? sanitize_user( wp_unslash( $_POST['name-user'] ) ) : '';
	$data_post_type  = ! empty( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : '';
	$data_post_title = ! empty( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : '';
	$data_form_id    = ! empty( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 1;

	if ( $data_post_id ) {
		if ( ! current_user_can( 'administrator' ) ) {
			$post = get_post( $data_post_id );
			if ( ! $post || get_current_user_id() != $post->post_author ) {
				wp_send_json( [ 'error' => __( 'Error', 'userspace-publication' ) ] );
			}
		}
	}

	if ( ! usp_get_option( 'public_access' ) && ! get_current_user_id() ) {
		if ( ! $data_user_email ) {
			wp_send_json( [ 'error' => __( 'Enter your e-mail!', 'userspace-publication' ) ] );
		}

		if ( ! $data_user_login ) {
			wp_send_json( [ 'error' => __( 'Enter your name!', 'userspace-publication' ) ] );
		}

		if ( ! $data_user_email || ! is_email( $data_user_email ) ) {
			wp_send_json( [ 'error' => __( 'You have entered an invalid email!', 'userspace-publication' ) ] );
		}

		if ( ! $data_user_login || ! validate_username( $data_user_login ) ) {
			wp_send_json( [ 'error' => __( 'You have entered an invalid name!', 'userspace-publication' ) ] );
		}

		if ( email_exists( $data_user_email ) ) {
			wp_send_json( [ 'error' => __( 'This email is already used!', 'userspace-publication' ) . '<br>' . __( 'If this is your email, then log in and publish your post', 'userspace-publication' ) ] );
		}

		if ( username_exists( $data_user_login ) ) {
			wp_send_json( [ 'error' => __( 'This name is already used!', 'userspace-publication' ) ] );
		}
	}

	if ( ! $data_post_type || ! $data_form_id ) {
		wp_send_json( [ 'error' => __( 'Error', 'userspace-publication' ) ] );
	}

	$formFields = new USPP_Public_Form_Fields( $data_post_type, [
		'form_id' => $data_form_id,
	] );

	foreach ( $formFields->fields as $field ) {
		if ( 'runner' == $field->type ) {
			$value = isset( $_POST[ $field->id ] ) && is_numeric( $_POST[ $field->id ] ) ? sanitize_key( $_POST[ $field->id ] ) : 0;
			$min   = $field->value_min;
			$max   = $field->value_max;

			if ( $value < $min || $value > $max ) {
				wp_send_json( [ 'error' => __( 'Incorrect values of some fields, enter the correct values!', 'userspace-publication' ) ] );
			}
		}
	}

	if ( $formFields->is_active_field( 'post_thumbnail' ) ) {
		$thumbnail_id = ( isset( $_POST['post_thumbnail'] ) ) ? absint( $_POST['post_thumbnail'] ) : 0;

		$field = $formFields->get_field( 'post_thumbnail' );

		if ( $field->get_prop( 'required' ) && ! $thumbnail_id ) {
			wp_send_json( [ 'error' => __( 'Upload or specify an image as a thumbnail', 'userspace-publication' ) ] );
		}
	}

	$post_content = '';

	if ( $formFields->is_active_field( 'post_content' ) ) {
		$postContent = isset( $_POST['post_content'] ) ? wp_kses_post( wp_unslash( $_POST['post_content'] ) ) : '';

		$field = $formFields->get_field( 'post_content' );

		if ( $field->get_prop( 'required' ) && ! $postContent ) {
			wp_send_json( [ 'error' => __( 'Add contents of the publication!', 'userspace-publication' ) ] );
		}

		$post_content = wpautop( do_shortcode( $postContent ) );
	}

	do_action( 'uspp_preview_post', [
		'post_id'   => $data_post_id,
		'post_type' => $data_post_type,
		'form_id'   => $data_form_id,
	] );

	if ( ! empty( $_POST['publish'] ) ) {
		wp_send_json( [
			'submit' => true,
		] );
	}

	$customFields = $formFields->get_custom_fields();
	if ( usp_get_option( 'uspp_custom_fields', 1 ) && $customFields ) {

		$types = usp_get_option( 'uspp_cf_post_types' );

		if ( ! $types || in_array( $data_post_type, $types ) ) {

			$fieldsBox = '<div class="usp-custom-fields">';

			foreach ( $customFields as $field_id => $field ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$field->set_prop( 'value', isset( $_POST[ $field_id ] ) ? wp_unslash( $_POST[ $field_id ] ) : false );
				$fieldsBox .= $field->get_field_value( true );
			}

			$fieldsBox .= '</div>';

			if ( usp_get_option( 'uspp_cf_place' ) == 1 ) {
				$post_content .= $fieldsBox;
			} else {
				$post_content = $fieldsBox . $post_content;
			}
		}
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$postGallery = ! empty( $_POST['uspp-post-gallery'] ) ? wp_unslash( $_POST['uspp-post-gallery'] ) : false;
	if ( $postGallery ) {
		$gallery = [];

		$postGallery = array_unique( $postGallery );
		foreach ( $postGallery as $attachment_id ) {
			$attachment_id = intval( $attachment_id );
			if ( $attachment_id ) {
				$gallery[] = $attachment_id;
			}
		}

		if ( $gallery ) {
			$post_content = '<div id="primary-preview-gallery">' . uspp_get_post_gallery( 'preview', $gallery ) . '</div>' . $post_content;
		}
	}

	$preview = apply_filters( 'uspp_preview_post_content', $post_content );

	$preview .= usp_get_notice( [
		'text' => __( 'If everything is correct – publish it! If not, you can go back to editing.', 'userspace-publication' ),
	] );

	do_action( 'uspp_pre_send_preview_post', [
		'post_id'   => $data_post_id,
		'post_type' => $data_post_type,
		'form_id'   => $data_form_id,
	] );

	wp_send_json( [
		'title'   => $data_post_title,
		'content' => $preview,
	] );
}

usp_ajax_action( 'uspp_set_post_thumbnail', true );
function uspp_set_post_thumbnail() {
	usp_verify_ajax_nonce();

	$thumbnail_id = isset( $_POST['thumbnail_id'] ) ? intval( $_POST['thumbnail_id'] ) : 0;
	$parent_id    = isset( $_POST['parent_id'] ) ? intval( $_POST['parent_id'] ) : 0;
	$form_id      = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
	$post_type    = isset( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : '';

	if ( $parent_id && ! current_user_can( 'administrator' ) ) {
		$post = get_post( $parent_id );
		if ( ! $post || get_current_user_id() != $post->post_author ) {
			wp_send_json( [ 'error' => __( 'Error', 'userspace-publication' ) ] );
		}
	}

	$formFields = new USPP_Public_Form_Fields( $post_type, [
		'form_id' => $form_id ? $form_id : 1,
	] );

	if ( ! $formFields->is_active_field( 'post_thumbnail' ) ) {
		wp_send_json( [
			'error' => __( 'The field of the thumbnail is inactive!', 'userspace-publication' ),
		] );
	}

	if ( $parent_id ) {
		update_post_meta( $parent_id, '_thumbnail_id', $thumbnail_id );
	}

	$field = $formFields->get_field( 'post_thumbnail' );

	$field->set_prop( 'uploader_props', [
		'post_parent' => $parent_id,
		'form_id'     => $form_id,
		'post_type'   => $post_type,
		'multiple'    => 0,
		'crop'        => 1,
	] );

	$result = [
		'html' => $field->get_uploader()->gallery_attachment( $thumbnail_id ),
		'id'   => $thumbnail_id,
	];

	wp_send_json( $result );
}

add_action( 'usp_upload', 'uspp_upload_post_thumbnail', 10, 2 );
function uspp_upload_post_thumbnail( $uploads, $uploader ) {

	if ( 'post_thumbnail' != $uploader->uploader_id ) {
		return false;
	}

	$thumbnail_id = $uploads['id'];

	if ( $uploader->post_parent ) {
		update_post_meta( $uploader->post_parent, '_thumbnail_id', $thumbnail_id );
	} else {
		usp_add_temp_media( [
			'media_id'    => $thumbnail_id,
			'uploader_id' => $uploader->uploader_id,
		] );
	}

	do_action( 'uspp_upload_post_thumbnail', $thumbnail_id, $uploader );

	$uploader->uploader_id  = 'post_uploader';
	$uploader->input_attach = 'post_uploader';
	$uploader->multiple     = 1;

	wp_send_json( [
		'thumbnail' => $uploads,
		'postmedia' => $uploader->gallery_attachment( $thumbnail_id ),
	] );
}
