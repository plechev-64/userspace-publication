<?php

//deprecated
//function uspp_get_custom_post_meta( $post_id ) {
//
//    USP()->use_module( 'fields' );
//
//    $get_fields = uspp_get_custom_fields( $post_id );
//
//    if ( $get_fields ) {
//        $show_custom_field = '';
//        $cf                = new USPP_Custom_Fields();
//        foreach ( $get_fields as $custom_field ) {
//            $custom_field          = apply_filters( 'uspp_custom_post_meta', $custom_field );
//            if ( ! $custom_field || ! isset( $custom_field['slug'] ) || ! $custom_field['slug'] )
//                continue;
//            $custom_field['value'] = get_post_meta( $post_id, $custom_field['slug'], true );
//            $show_custom_field     .= USP_Field::setup( $custom_field )->get_field_value( 'title' );
//        }
//
//        return $show_custom_field;
//    }
//}

function uspp_get_postslist( $post_type, $type_name ) {
    global $user_LK;

    uspp_postlist_style();
//
//    if ( ! class_exists( 'USPP_Post_List' ) )
//        include_once USPP_PATH . 'classes/class-uspp-post-list';
//
//    $list = new USPP_Post_List( $user_LK, $post_type, $type_name );
//
//    return $list->get_postlist_block();


    $manager = new USPP_Author_Postlist( [
        'post_author' => $user_LK,
        'post_type'   => $post_type
        ] );

    $content = '<div id="uspp-postlist-' . $post_type . '" class="uspp-postlist">';
    $content .= $manager->get_manager();
    $content .= '</div>';

    return $content;
}

function uspp_tab_postform( $master_id ) {
    return do_shortcode( '[uspp-public-form form_id="' . usp_get_option( 'uspp_id_public_form', 1 ) . '"]' );
}

//Прикрепление новой миниатюры к публикации из произвольного места на сервере
/* function uspp_add_thumbnail_post( $post_id, $filepath ) {

  require_once(ABSPATH . "wp-admin" . '/includes/image.php');
  require_once(ABSPATH . "wp-admin" . '/includes/file.php');
  require_once(ABSPATH . "wp-admin" . '/includes/media.php');

  $filename	 = basename( $filepath );
  $file		 = explode( '.', $filename );
  $thumbpath	 = $filepath;

  //if($file[0]=='image'){
  $data	 = getimagesize( $thumbpath );
  $mime	 = $data['mime'];
  //}else $mime = mime_content_type($thumbpath);

  $cont	 = file_get_contents( $thumbpath );
  $image	 = wp_upload_bits( $filename, null, $cont );

  $attachment = array(
  'post_mime_type' => $mime,
  'post_title'	 => preg_replace( '/\.[^.]+$/', '', basename( $image['file'] ) ),
  'post_content'	 => '',
  'guid'			 => $image['url'],
  'post_parent'	 => $post_id,
  'post_status'	 => 'inherit'
  );

  $attach_id	 = wp_insert_attachment( $attachment, $image['file'], $post_id );
  $attach_data = wp_generate_attachment_metadata( $attach_id, $image['file'] );
  wp_update_attachment_metadata( $attach_id, $attach_data );

  $oldthumb = get_post_meta( $post_id, '_thumbnail_id', 1 );
  if ( $oldthumb )
  wp_delete_attachment( $oldthumb );

  update_post_meta( $post_id, '_thumbnail_id', $attach_id );
  } */

function uspp_edit_post_button_html( $post_id ) {
    return '<p class="post-edit-button">'
        . '<a title="' . __( 'Edit', 'userspace-publication' ) . '" object-id="none" href="' . get_edit_post_link( $post_id ) . '">'
        . '<i class="uspi fa-edit"></i>'
        . '</a>'
        . '</p>';
}

// don't used function
function uspp_get_editor_content( $post_content ) {
    global $uspp_box;

    remove_filter( 'the_content', 'add_button_bmk_in_content', 20 );
    remove_filter( 'the_content', 'get_notifi_bkms', 20 );

    // don't used uspp_get_edit_post_button
    remove_filter( 'the_content', 'uspp_get_edit_post_button', 999 );

    $content = apply_filters( 'the_content', $post_content );

    return $content;
}

function uspp_is_limit_editing( $post_date ) {

    $timelimit = apply_filters( 'uspp_time_editing', usp_get_option( 'uspp_time_editing' ) );

    if ( $timelimit ) {
        $hours = (strtotime( current_time( 'mysql' ) ) - strtotime( $post_date )) / 3600;
        if ( $hours > $timelimit )
            return true;
    }

    return false;
}

function uspp_get_custom_fields_edit_box( $post_id, $post_type = false, $form_id = 1 ) {

    $post = get_post( $post_id );

    $usppForm = new USPP_Public_Form( array(
        'post_type' => $post->post_type,
        'post_id'   => $post_id,
        'form_id'   => $form_id
        ) );

    $fields = $usppForm->get_custom_fields();

    if ( $usppForm->is_active_field( 'post_uploader' ) ) {

        $postUploader = $usppForm->get_field( 'post_uploader' );
        $postUploader->set_prop( 'fix_editor', 'content' );

        $fields = $fields ? [ 'post_uploader' => $postUploader ] + $fields : [ 'post_uploader' => $postUploader ];
    }

    if ( ! $fields )
        return false;

    uspp_publics_scripts();

    $content = '<div id="uspp-post-fields-admin-box">';

    foreach ( $fields as $field_id => $field ) {

        if ( ! isset( $field->slug ) )
            continue;

        $content .= $usppForm->get_field_form( $field_id );
    }

    $content .= '</div>';

    return $content;
}

function uspp_update_post_custom_fields( $post_id, $id_form = false ) {

    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');

    $post = get_post( $post_id );

    $formFields = new USPP_Public_Form_Fields( $post->post_type, array(
        'form_id' => $id_form
        ) );

    $fields = $formFields->get_custom_fields();

    if ( $fields ) {

        $POST = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );

        foreach ( $fields as $field_id => $field ) {

            $value = isset( $POST[$field_id] ) ? $POST[$field_id] : false;

            if ( $field->type == 'file' ) {

                $attach_id = get_post_meta( $post_id, $field_id, 1 );

                if ( $value != $attach_id ) {
                    wp_delete_attachment( $attach_id );
                }
            }

            if ( $field->type == 'checkbox' ) {
                $vals = array();

                $count_field = count( $field->values );

                if ( $value && is_array( $value ) ) {
                    foreach ( $value as $val ) {
                        for ( $a = 0; $a < $count_field; $a ++ ) {
                            if ( $field->values[$a] == $val ) {
                                $vals[] = $val;
                            }
                        }
                    }
                }

                if ( $vals ) {
                    update_post_meta( $post_id, $field_id, $vals );
                } else {
                    delete_post_meta( $post_id, $field_id );
                }
            } else {

                if ( $value || $value == 0 ) {
                    update_post_meta( $post_id, $field_id, $value );
                } else {
                    if ( get_post_meta( $post_id, $field_id, 1 ) )
                        delete_post_meta( $post_id, $field_id );
                }
            }

            if ( $value ) {

                if ( $field->type == 'uploader' ) {
                    foreach ( $value as $val ) {
                        usp_delete_temp_media( $val );
                    }
                } else if ( $field->type == 'file' ) {
                    usp_delete_temp_media( $value );
                }
            }
        }
    }

    //support of uploader in admin
    if ( is_admin() && isset( $_POST['post_uploader'] ) && $_POST['post_uploader'] ) {
        global $user_ID;

        $editPost = new USPP_Edit_Post();

        $editPost->add_attachments_from_temps( $user_ID );

        $editPost->update_post_gallery();

        usp_delete_temp_media_by_args( array(
            'user_id'         => $user_ID,
            'uploader_id__in' => array( 'post_uploader', 'post_thumbnail' )
        ) );
    }
}

usp_ajax_action( 'uspp_save_temp_async_uploaded_thumbnail', true );
function uspp_save_temp_async_uploaded_thumbnail() {

    $attachment_id  = intval( $_POST['attachment_id'] );
    $attachment_url = $_POST['attachment_url'];

    if ( ! $attachment_id || ! $attachment_url ) {
        return array(
            'error' => __( 'Error', 'userspace-publication' )
        );
    }

    uspp_update_tempgallery( $attachment_id, $attachment_url );

    return array(
        'save' => true
    );
}

function uspp_update_tempgallery( $attach_id, $attach_url ) {
    global $user_ID;

    $user_id = ($user_ID) ? $user_ID : $_COOKIE['PHPSESSID'];

    $temp_gal = get_site_option( 'uspp_tempgallery' );

    if ( ! $temp_gal )
        $temp_gal = array();

    $temp_gal[$user_id][] = array(
        'ID'  => $attach_id,
        'url' => $attach_url
    );

    update_site_option( 'uspp_tempgallery', $temp_gal );

    return $temp_gal;
}

function uspp_get_attachment_box( $attachment_id, $mime = 'image', $addToClick = true ) {

    if ( $mime == 'image' ) {

        $small_url = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );

        $image = '<img src="' . $small_url[0] . '">';

        if ( $addToClick ) {

            if ( $default = usp_get_option( 'uspp_default_thumb' ) )
                $sizes   = wp_get_attachment_image_src( $attachment_id, $default );
            else
                $sizes   = $small_url;

            $full_url  = wp_get_attachment_image_src( $attachment_id, 'full' );
            $act_sizes = wp_constrain_dimensions( $full_url[1], $full_url[2], $sizes[1], $sizes[2] );

            // uspp_add_image_in_form not exists js function
            return '<a onclick="uspp_add_image_in_form(this,\'<a href=' . $full_url[0] . '><img height=' . $act_sizes[1] . ' width=' . $act_sizes[0] . ' class=usps__text-center  src=' . $full_url[0] . '></a>\');return false;" href="#">' . $image . '</a>';
        } else {
            return $image;
        }
    } else {

        $image = wp_get_attachment_image( $attachment_id, array( 100, 100 ), true );

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

// don't used function
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
 * @since 1.0
 *
 * @param int $post_id    id of the post to edit.
 *
 * @return string html button.
 */
function uspp_button_fast_edit_post( $post_id ) {
    if ( ! $post_id )
        return;

    usp_dialog_scripts();
    uspp_publics_scripts();

    $args = [
        'class'   => 'uspp-edit-post uspp-service-button',
        'data'    => [ 'post' => $post_id ],
        'onclick' => 'uspp_edit_post(this); return false;',
        'icon'    => 'fa-edit'
    ];

    return usp_get_button( $args );
}

/**
 * Get button to fast delete post with confirm
 *
 * @since 1.0
 *
 * @param int $post_id    id of the post to delete.
 *
 * @return string html button.
 */
function uspp_button_fast_delete_post( $post_id ) {
    if ( ! $post_id )
        return;

    usp_dialog_scripts();
    uspp_publics_scripts();

    $args = [
        'class'   => 'uspp-delete-post uspp-service-button',
        'data'    => [ 'post' => $post_id ],
        'onclick' => 'return confirm("' . __( 'Are you sure?', 'userspace-publication' ) . '")? uspp_delete_post(this): false;',
        'icon'    => 'fa-trash'
    ];

    return usp_get_button( $args );
}
