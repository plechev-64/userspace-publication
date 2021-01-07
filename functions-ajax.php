<?php

//удаление фото приложенных к публикации через загрузчик плагина
usp_ajax_action( 'uspp_ajax_delete_post', true );
function uspp_ajax_delete_post() {
    global $user_ID;

    $user_id = ($user_ID) ? $user_ID : $_COOKIE['PHPSESSID'];

    $temps    = get_site_option( 'uspp_tempgallery' );
    $temp_gal = $temps[$user_id];

    if ( $temp_gal ) {

        foreach ( ( array ) $temp_gal as $key => $gal ) {
            if ( $gal['ID'] == $_POST['post_id'] )
                unset( $temp_gal[$key] );
        }
        foreach ( ( array ) $temp_gal as $t ) {
            $new_temp[] = $t;
        }

        if ( $new_temp )
            $temps[$user_id] = $new_temp;
        else
            unset( $temps[$user_id] );
    }

    update_site_option( 'uspp_tempgallery', $temps );

    $post = get_post( intval( $_POST['post_id'] ) );

    if ( ! $post ) {
        $log['success']   = __( 'Material successfully removed!', 'usp-publication' );
        $log['post_type'] = 'attachment';
    } else {

        $res = wp_delete_post( $post->ID );

        if ( $res ) {
            $log['success']   = __( 'Material successfully removed!', 'usp-publication' );
            $log['post_type'] = $post->post_type;
        } else {
            $log['error'] = __( 'Deletion failed!', 'usp-publication' );
        }
    }

    return $log;
}

//вызов быстрой формы редактирования публикации
usp_ajax_action( 'uspp_get_edit_postdata', false );
function uspp_get_edit_postdata() {
    global $user_ID;

    $post_id = intval( $_POST['post_id'] );
    $post    = get_post( $post_id );

    if ( $user_ID ) {
        $log['result']  = 100;
        $log['content'] = "
        <form id='uspp-edit-form' method='post'>
                <label>" . __( "Name", 'usp-publication' ) . ":</label>
                 <input type='text' name='post_title' value='$post->post_title'>
                 <label>" . __( "Description", 'usp-publication' ) . ":</label>
                 <textarea name='post_content' rows='10'>$post->post_content</textarea>
                 <input type='hidden' name='post_id' value='$post_id'>
        </form>";
    } else {
        $log['error'] = __( 'Failed to get the data', 'usp-publication' );
    }

    return $log;
}

//сохранение изменений в быстрой форме редактирования
usp_ajax_action( 'uspp_edit_postdata', false );
function uspp_edit_postdata() {
    global $wpdb;

    $post_array                 = array();
    $post_array['post_title']   = sanitize_text_field( $_POST['post_title'] );
    $post_array['post_content'] = esc_textarea( $_POST['post_content'] );

    $post_array = apply_filters( 'uspp_pre_edit_post', $post_array );

    $result = $wpdb->update(
        $wpdb->posts, $post_array, array( 'ID' => intval( $_POST['post_id'] ) )
    );

    if ( ! $result ) {
        return array(
            'error' => __( 'Changes to be saved not found', 'usp-publication' )
        );
    }

    return array(
        'success' => __( 'Publication updated', 'usp-publication' ),
        'dialog'  => array( 'close' )
    );
}

function uspp_edit_post() {
    $edit = new USPP_Edit_Post();
    $edit->update_post();
}

//выборка меток по введенным значениям
add_action( 'wp_ajax_uspp_get_like_tags', 'uspp_get_like_tags', 10 );
add_action( 'wp_ajax_nopriv_uspp_get_like_tags', 'uspp_get_like_tags', 10 );
function uspp_get_like_tags() {

    if ( ! $_POST['query'] ) {
        return array( array( 'id' => '' ) );
    };

    $query    = $_POST['query'];
    $taxonomy = $_POST['taxonomy'];

    $terms = get_terms( $taxonomy, array( 'hide_empty' => false, 'name__like' => $query ) );

    $tags = array();
    foreach ( $terms as $key => $term ) {
        $tags[$key]['id']   = $term->name;
        $tags[$key]['name'] = $term->name;
    }

    wp_send_json( $tags );
}

add_filter( 'uspp_preview_post_content', 'usp_add_registered_scripts' );

usp_ajax_action( 'uspp_preview_post', true );
function uspp_preview_post() {
    global $user_ID;

    usp_reset_wp_dependencies();

    $log      = array();
    $postdata = $_POST;

    if ( ! usp_get_option( 'public_access' ) && ! $user_ID ) {

        $email_new_user = sanitize_email( $postdata['email-user'] );
        $name_new_user  = $postdata['name-user'];

        if ( ! $email_new_user ) {
            $log['error'] = __( 'Enter your e-mail!', 'usp-publication' );
        }
        if ( ! $name_new_user ) {
            $log['error'] = __( 'Enter your name!', 'usp-publication' );
        }

        $res_email    = email_exists( $email_new_user );
        $res_login    = username_exists( $email_new_user );
        $correctemail = is_email( $email_new_user );
        $valid        = validate_username( $email_new_user );

        if ( $res_login || $res_email || ! $correctemail || ! $valid ) {

            if ( ! $valid || ! $correctemail ) {
                $log['error'] .= __( 'You have entered an invalid email!', 'usp-publication' );
            }
            if ( $res_login || $res_email ) {
                $log['error'] .= __( 'This email is already used!', 'usp-publication' ) . '<br>'
                    . __( 'If this is your email, then log in and publish your post', 'usp-publication' );
            }
        }

        if ( $log['error'] ) {
            return $log;
        }
    }

    $formFields = new USPP_Public_Form_Fields( $postdata['post_type'], array(
        'form_id' => isset( $postdata['form_id'] ) ? $postdata['form_id'] : 1
        ) );

    foreach ( $formFields->fields as $field ) {

        if ( in_array( $field->type, array( 'runner' ) ) ) {

            $value = isset( $postdata[$field->id] ) ? $postdata[$field->id] : 0;
            $min   = $field->value_min;
            $max   = $field->value_max;

            if ( $value < $min || $value > $max ) {
                return array( 'error' => __( 'Incorrect values of some fields, enter the correct values!', 'usp-publication' ) );
            }
        }
    }

    if ( $formFields->is_active_field( 'post_thumbnail' ) ) {

        $thumbnail_id = (isset( $postdata['post-thumbnail'] )) ? $postdata['post-thumbnail'] : 0;

        $field = $formFields->get_field( 'post_thumbnail' );

        if ( $field->get_prop( 'required' ) && ! $thumbnail_id ) {
            return array( 'error' => __( 'Upload or specify an image as a thumbnail', 'usp-publication' ) );
        }
    }

    $post_content = '';

    if ( $formFields->is_active_field( 'post_content' ) ) {

        $postContent = $postdata['post_content'];

        $field = $formFields->get_field( 'post_content' );

        if ( $field->get_prop( 'required' ) && ! $postContent ) {
            return array( 'error' => __( 'Add contents of the publication!', 'usp-publication' ) );
        }

        $post_content = wpautop( do_shortcode( stripslashes_deep( $postContent ) ) );
    }

    do_action( 'uspp_preview_post', $postdata );

    if ( $postdata['publish'] ) {
        return [
            'submit' => true
        ];
    }

    if ( usp_get_option( 'uspp_custom_fields', 1 ) && $customFields = $formFields->get_custom_fields() ) {

        $types = usp_get_option( 'uspp_cf_post_types' );

        if ( ! $types || in_array( $postdata['post_type'], $types ) ) {

            $fieldsBox = '<div class="usp-custom-fields">';

            foreach ( $customFields as $field_id => $field ) {
                $field->set_prop( 'value', isset( $_POST[$field_id] ) ? $_POST[$field_id] : false  );
                $fieldsBox .= $field->get_field_value( true );
            }

            $fieldsBox .= '</div>';

            if ( usp_get_option( 'uspp_cf_place' ) == 1 )
                $post_content .= $fieldsBox;
            else
                $post_content = $fieldsBox . $post_content;
        }
    }

    if ( isset( $_POST['uspp-post-gallery'] ) && $postGallery = $_POST['uspp-post-gallery'] ) {

        $gallery = array();

        if ( $postGallery ) {
            $postGallery = array_unique( $postGallery );
            foreach ( $postGallery as $attachment_id ) {
                $attachment_id = intval( $attachment_id );
                if ( $attachment_id )
                    $gallery[]     = $attachment_id;
            }
        }

        if ( $gallery ) {
            $post_content = '<div id="primary-preview-gallery">' . uspp_get_post_gallery( 'preview', $gallery ) . '</div>' . $post_content;
        }
    }

    $preview = apply_filters( 'uspp_preview_post_content', $post_content );

    $preview .= usp_get_notice( [
        'text' => __( 'If everything is correct – publish it! If not, you can go back to editing.', 'usp-publication' )
        ] );

    do_action( 'uspp_pre_send_preview_post', $postdata );

    return array(
        'title'   => $postdata['post_title'],
        'content' => $preview
    );
}

usp_ajax_action( 'uspp_set_post_thumbnail', true );
function uspp_set_post_thumbnail() {

    $thumbnail_id = intval( $_POST['thumbnail_id'] );
    $parent_id    = intval( $_POST['parent_id'] );
    $form_id      = intval( $_POST['form_id'] );
    $post_type    = $_POST['post_type'];

    $formFields = new USPP_Public_Form_Fields( $post_type, array(
        'form_id' => $form_id ? $form_id : 1
        ) );

    if ( ! $formFields->is_active_field( 'post_thumbnail' ) )
        return [
            'error' => __( 'The field of the thumbnail is inactive!', 'usp-publication' )
        ];

    if ( $parent_id ) {
        update_post_meta( $parent_id, '_thumbnail_id', $thumbnail_id );
    }

    $field = $formFields->get_field( 'post_thumbnail' );

    $field->set_prop( 'uploader_props', array(
        'post_parent' => $parent_id,
        'form_id'     => $form_id,
        'post_type'   => $post_type,
        'multiple'    => 0,
        'crop'        => 1
    ) );

    $result = array(
        'html' => $field->get_uploader()->gallery_attachment( $thumbnail_id ),
        'id'   => $thumbnail_id
    );

    return $result;
}

add_action( 'usp_upload', 'uspp_upload_post_thumbnail', 10, 2 );
function uspp_upload_post_thumbnail( $uploads, $uploader ) {

    if ( $uploader->uploader_id != 'post_thumbnail' )
        return false;

    $thumbnail_id = $uploads['id'];

    if ( $uploader->post_parent ) {

        update_post_meta( $uploader->post_parent, '_thumbnail_id', $thumbnail_id );
    } else {

        usp_add_temp_media( array(
            'media_id'    => $thumbnail_id,
            'uploader_id' => $uploader->uploader_id
        ) );
    }

    do_action( 'uspp_upload_post_thumbnail', $thumbnail_id, $uploader );

    $uploader->uploader_id  = 'post_uploader';
    $uploader->input_attach = 'post_uploader';
    $uploader->multiple     = 1;

    wp_send_json( [
        'thumbnail' => $uploads,
        'postmedia' => $uploader->gallery_attachment( $thumbnail_id )
    ] );
}
