<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'usp_init', 'uspp_loading_dependencies' );
function uspp_loading_dependencies() {
    require_once 'classes/class-uspp-form-fields.php';
    require_once 'classes/class-uspp-edit-terms-list.php';
    require_once 'classes/class-uspp-list-terms.php';
    require_once 'classes/class-uspp-public-form-fields.php';
    require_once 'classes/class-uspp-public-form.php';
    require_once 'classes/class-uspp-post-list.php';
    require_once 'classes/class-uspp-edit-post.php';
    require_once 'core.php';
    require_once 'shortcodes.php';
    require_once 'functions-ajax.php';
    require_once 'init.php';

    if ( is_admin() ) {
        require_once 'classes/class-uspp-public-form-manager.php';
        require_once 'admin/index.php';
    }
}

if ( ! is_admin() ) {
    add_action( 'usp_enqueue_scripts', 'uspp_publics_scripts', 10 );
}
function uspp_publics_scripts() {
    usp_enqueue_style( 'uspp-publics', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' );
    usp_enqueue_script( 'uspp-publics', plugin_dir_url( __FILE__ ) . 'assets/js/scripts.js' );
}

function uspp_autocomplete_scripts() {
    usp_enqueue_style( 'magicsuggest', plugin_dir_url( __FILE__ ) . 'assets/js/magicsuggest/magicsuggest-min.css' );
    usp_enqueue_script( 'magicsuggest', plugin_dir_url( __FILE__ ) . 'assets/js/magicsuggest/magicsuggest-min.js' );
}

add_filter( 'usp_init_js_variables', 'uspp_public_add_js_locale', 10 );
function uspp_public_add_js_locale( $data ) {
    $data['errors']['cats_important'] = __( 'Choose a category', 'userspace-publication' );
    return $data;
}

//выводим в медиабиблиотеке только медиафайлы текущего автора
add_action( 'pre_get_posts', 'uspp_restrict_media_library' );
function uspp_restrict_media_library( $wp_query_obj ) {
    global $current_user, $pagenow;

    if ( ! is_a( $current_user, 'WP_User' ) )
        return;

    if ( 'admin-ajax.php' != $pagenow || $_REQUEST['action'] != 'query-attachments' )
        return;

    if ( usp_check_access_console() )
        return;

    if ( ! current_user_can( 'manage_media_library' ) )
        $wp_query_obj->set( 'author', $current_user->ID );

    return;
}

add_filter( 'uspp_pre_update_postdata', 'uspp_update_postdata_excerpt' );
function uspp_update_postdata_excerpt( $postdata ) {
    if ( ! isset( $_POST['post_excerpt'] ) )
        return $postdata;
    $postdata['post_excerpt'] = sanitize_text_field( $_POST['post_excerpt'] );
    return $postdata;
}

//формируем галерею записи
add_filter( 'the_content', 'uspp_post_gallery', 10 );
function uspp_post_gallery( $content ) {
    global $post;

    if ( ! is_single() || $post->post_type == 'products' )
        return $content;

    $oldSlider = get_post_meta( $post->ID, 'uspp_slider', 1 );
    $gallery   = get_post_meta( $post->ID, 'uspp_post_gallery', 1 );

    if ( ! $gallery && $oldSlider ) {

        $args      = array(
            'post_parent'    => $post->ID,
            'post_type'      => 'attachment',
            'numberposts'    => -1,
            'post_status'    => 'any',
            'post_mime_type' => 'image'
        );
        $childrens = get_children( $args );
        if ( $childrens ) {
            $gallery = array();
            foreach ( ( array ) $childrens as $children ) {
                $gallery[] = $children->ID;
            }
        }
    }

    if ( ! $gallery )
        return $content;

    $content = uspp_get_post_gallery( $post->ID, $gallery ) . $content;

    return $content;
}

//Выводим инфу об авторе записи в конце поста
add_filter( 'the_content', 'uspp_author_info', 70 );
function uspp_author_info( $content ) {

    if ( ! usp_get_option( 'uspp_author_box' ) )
        return $content;

    if ( ! is_single() )
        return $content;

    global $post;

    if ( $post->post_type == 'page' )
        return $content;

    if ( usp_get_option( 'uspp_author_box_post_types' ) ) {

        if ( ! in_array( $post->post_type, usp_get_option( 'uspp_author_box_post_types' ) ) )
            return $content;
    }

    $content .= uspp_get_author_block();

    return $content;
}

function uspp_get_author_block() {
    global $post;

    $content = '<div id="uspp_block_author">';
    $content .= '<h3>' . __( 'Publication author', 'userspace-publication' ) . '</h3>';

    if ( function_exists( 'usp_add_userlist_follow_button' ) )
        add_action( 'usp_user_fields_after', 'usp_add_userlist_follow_button', 90 );

    $content .= usp_get_userlist( array(
        'template' => 'rows',
        'orderby'  => 'display_name',
        'include'  => $post->post_author,
        'filter'   => 0,
        'data'     => 'rating_total,description,posts_count,user_registered,comments_count'
        ) );

    if ( function_exists( 'usp_add_userlist_follow_button' ) )
        remove_action( 'usp_user_fields_after', 'usp_add_userlist_follow_button', 90 );

    $content .= '</div>';

    return $content;
}

add_filter( 'the_content', 'uspp_concat_post_meta', 10 );
function uspp_concat_post_meta( $content ) {
    global $post;

    if ( doing_filter( 'get_the_excerpt' ) )
        return $content;

    $option = usp_get_option( 'uspp_custom_fields', 1 );

    if ( ! $option )
        return $content;

    if ( $types = usp_get_option( 'uspp_cf_post_types' ) ) {
        if ( ! in_array( $post->post_type, $types ) )
            return $content;
    }

    $pm = uspp_get_post_custom_fields_box( $post->ID );

    if ( usp_get_option( 'uspp_cf_place' ) == 1 )
        $content .= $pm;
    else
        $content = $pm . $content;

    return $content;
}

function uspp_get_post_custom_fields_box( $post_id ) {

    $formFields = new USPP_Public_Form_Fields( get_post_type( $post_id ), array(
        'form_id' => get_post_meta( $post_id, 'publicform-id', 1 )
        ) );

    $customFields = apply_filters( 'uspp_post_custom_fields', $formFields->get_custom_fields(), $post_id );

    if ( ! $customFields )
        return false;

    $content = '<div class="usp-custom-fields">';

    foreach ( $customFields as $field_id => $field ) {
        $field->set_prop( 'value', get_post_meta( $post_id, $field_id, 1 ) );
        $content .= $field->get_field_value( true );
    }

    $content .= '</div>';

    return $content;
}

add_action( 'init', 'uspp_delete_post_activate' );
function uspp_delete_post_activate() {
    if ( isset( $_POST['uspp-delete-post'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'uspp-delete-post' ) ) {
        add_action( 'wp', 'uspp_delete_post' );
    }
}

function uspp_delete_post() {
    global $user_ID;

    $post_id = intval( $_POST['post_id'] );

    $post = get_post( $post_id );

    if ( $post->post_type == 'post-group' ) {

        if ( ! uspg_can_user_edit_post_group( $post_id ) )
            return false;
    } else {

        if ( ! current_user_can( 'edit_post', $post_id ) )
            return false;
    }

    $post_id = wp_update_post( array(
        'ID'          => $post_id,
        'post_status' => 'trash'
        ) );

    do_action( 'uspp_after_delete_post', $post_id );

    wp_redirect( add_query_arg( [ 'public' => 'deleted' ], usp_get_user_url( $user_ID ) ) );
    exit;
}

add_action( 'uspp_after_delete_post', 'uspp_delete_notice_author_post' );
function uspp_delete_notice_author_post( $post_id ) {

    if ( ! $_POST['reason_content'] )
        return false;

    $post = get_post( $post_id );

    $subject  = __( 'Your post has been deleted', 'userspace-publication' );
    $textmail = '<h3>' . __( 'Post', 'userspace-publication' ) . ' "' . $post->post_title . '" ' . __( 'has been deleted', 'userspace-publication' ) . '</h3>
    <p>' . __( 'Notice of a moderator', 'userspace-publication' ) . ': ' . $_POST['reason_content'] . '</p>';

    usp_mail( get_the_author_meta( 'user_email', $post->post_author ), $subject, $textmail );
}

if ( ! is_admin() )
    add_filter( 'get_edit_post_link', 'uspp_edit_post_link', 100, 2 );
function uspp_edit_post_link( $admin_url, $post_id ) {
    global $user_ID;

    $frontEdit = usp_get_option( 'uspp_front_post_edit', array( 0 ) );

    $user_info = get_userdata( $user_ID );

    if ( array_search( $user_info->user_level, $frontEdit ) !== false || $user_info->user_level < usp_get_option( 'consol_access_usp', 7 ) ) {
        return add_query_arg( [ 'uspp-post-edit' => $post_id ], get_permalink( usp_get_option( 'uspp_public_form_page' ) ) );
    } else {
        return $admin_url;
    }
}

add_action( 'uspp_post_bar_setup', 'uspp_setup_edit_post_button', 10 );
function uspp_setup_edit_post_button() {
    global $post, $user_ID, $current_user;

    if ( ! is_user_logged_in() || ! $post )
        return false;

    if ( is_front_page() || is_tax( 'groups' ) || $post->post_type == 'page' )
        return false;

    if ( ! current_user_can( 'edit_post', $post->ID ) )
        return false;

    $user_info = get_userdata( $current_user->ID );

    if ( $post->post_author != $user_ID ) {
        $author_info = get_userdata( $post->post_author );
        if ( $user_info->user_level < $author_info->user_level )
            return false;
    }

    $frontEdit = usp_get_option( 'uspp_front_post_edit', array( 0 ) );

    if ( false !== array_search( $user_info->user_level, $frontEdit ) || $user_info->user_level >= usp_get_option( 'consol_access_usp', 7 ) ) {

        if ( $user_info->user_level < 10 && uspp_is_limit_editing( $post->post_date ) )
            return false;

        usp_post_bar_add_item( 'uspp-edit-post', array(
            'url'   => get_edit_post_link( $post->ID ),
            'icon'  => 'fa-edit',
            'title' => __( 'Edit', 'userspace-publication' )
            )
        );

        return true;
    }

    return false;
}

add_filter( 'uspp_pre_update_postdata', 'uspp_add_taxonomy_in_postdata', 50, 2 );
function uspp_add_taxonomy_in_postdata( $postdata, $data ) {

    $post_type = get_post_types( array( 'name' => $data->post_type ), 'objects' );

    if ( ! $post_type )
        return false;

    if ( $data->post_type == 'post' ) {

        $post_type['post']->taxonomies = array( 'category' );

        if ( isset( $_POST['tags'] ) && $_POST['tags'] ) {
            $postdata['tags_input'] = $_POST['tags']['post_tag'];
        }
    }

    if ( isset( $_POST['cats'] ) && $_POST['cats'] ) {

        $FormFields = new USPP_Public_Form_Fields( $data->post_type, array(
            'form_id' => $_POST['form_id']
            ) );

        foreach ( $_POST['cats'] as $taxonomy => $terms ) {

            if ( ! isset( $FormFields->taxonomies[$taxonomy] ) )
                continue;

            if ( ! $FormFields->get_field_prop( 'taxonomy-' . $taxonomy, 'only-child' ) ) {

                $allCats = get_terms( $taxonomy );

                $UsppTerms = new USPP_Edit_Terms_List();
                $terms     = $UsppTerms->get_terms_list( $allCats, $terms );
            }

            $postdata['tax_input'][$taxonomy] = $terms;
        }
    }

    return $postdata;
}

add_action( 'uspp_update_post', 'uspp_update_postdata_product_tags', 10, 2 );
function uspp_update_postdata_product_tags( $post_id, $postdata ) {

    if ( ! isset( $_POST['tags'] ) || $postdata['post_type'] == 'post' )
        return false;

    foreach ( $_POST['tags'] as $taxonomy => $terms ) {
        wp_set_object_terms( $post_id, $terms, $taxonomy );
    }
}

add_action( 'uspp_update_post', 'uspp_unset_postdata_tags', 20, 2 );
function uspp_unset_postdata_tags( $post_id, $postdata ) {

    if ( ! isset( $_POST['tags'] ) ) {

        if ( $taxonomies = get_object_taxonomies( $postdata['post_type'], 'objects' ) ) {

            foreach ( $taxonomies as $taxonomy_name => $obj ) {

                if ( $obj->hierarchical )
                    continue;

                wp_set_object_terms( $post_id, NULL, $taxonomy_name );
            }
        }
    }
}

add_action( 'uspp_update_post', 'uspp_set_object_terms_post', 10, 3 );
function uspp_set_object_terms_post( $post_id, $postdata, $update ) {

    if ( $update || ! isset( $postdata['tax_input'] ) || ! $postdata['tax_input'] )
        return false;

    foreach ( $postdata['tax_input'] as $taxonomy_name => $terms ) {
        wp_set_object_terms( $post_id, array_map( 'intval', $terms ), $taxonomy_name );
    }
}

add_filter( 'uspp_pre_update_postdata', 'uspp_register_author_post', 10 );
function uspp_register_author_post( $postdata ) {
    global $user_ID;

    if ( usp_get_option( 'uspp_access_publicform', 2 ) || $user_ID )
        return $postdata;

    if ( ! $postdata['post_author'] ) {

        $email_new_user = sanitize_email( $_POST['email-user'] );

        if ( $email_new_user ) {

            $user_id = false;

            $random_password                = wp_generate_password( $length                         = 12, $include_standard_special_chars = false );

            $userdata = array(
                'user_pass'    => $random_password,
                'user_login'   => $email_new_user,
                'user_email'   => $email_new_user,
                'display_name' => $_POST['name-user']
            );

            $user_id = usp_insert_user( $userdata );

            if ( $user_id ) {

                //переназначаем временный массив изображений от гостя юзеру
                usp_update_temp_media( [ 'user_id' => $user_id ], [
                    'user_id'    => 0,
                    'session_id' => isset( $_COOKIE['PHPSESSID'] ) && $_COOKIE['PHPSESSID'] ? $_COOKIE['PHPSESSID'] : 'none'
                ] );

                //Сразу авторизуем пользователя
                if ( ! usp_get_option( 'usp_confirm_register' ) ) {
                    $creds                  = array();
                    $creds['user_login']    = $email_new_user;
                    $creds['user_password'] = $random_password;
                    $creds['remember']      = true;
                    $user                   = wp_signon( $creds );
                    $user_ID                = $user_id;
                }

                $postdata['post_author'] = $user_id;
                $postdata['post_status'] = 'pending';
            }
        }
    }

    return $postdata;
}

//Сохранение данных публикации в редакторе userspace
/* add_action( 'uspp_update_post', 'uspp_add_box_content', 10, 3 );
  function uspp_add_box_content( $post_id, $postdata, $update ) {

  if ( ! isset( $_POST['post_content'] ) || ! is_array( $_POST['post_content'] ) )
  return false;

  $post_content	 = '';
  $thumbnail		 = false;

  $POST = add_magic_quotes( $_POST['post_content'] );

  foreach ( $POST as $k => $contents ) {
  foreach ( $contents as $type => $content ) {
  if ( $type == 'text' )
  $content = strip_tags( $content );
  if ( $type == 'header' )
  $content = sanitize_text_field( $content );
  if ( $type == 'html' )
  $content = str_replace( '\'', '"', $content );

  if ( $type == 'image' ) {
  $path_media	 = usp_path_by_url( $content );
  $filename	 = basename( $content );

  $dir_path	 = USP_UPLOAD_PATH . 'post-media/';
  $dir_url	 = USP_UPLOAD_URL . 'post-media/';
  if ( ! is_dir( $dir_path ) ) {
  mkdir( $dir_path );
  chmod( $dir_path, 0755 );
  }

  $dir_path	 = USP_UPLOAD_PATH . 'post-media/' . $post_id . '/';
  $dir_url	 = USP_UPLOAD_URL . 'post-media/' . $post_id . '/';
  if ( ! is_dir( $dir_path ) ) {
  mkdir( $dir_path );
  chmod( $dir_path, 0755 );
  }

  if ( copy( $path_media, $dir_path . $filename ) ) {
  unlink( $path_media );
  }

  if ( ! $thumbnail )
  $thumbnail = $dir_path . $filename;

  $content = $dir_url . $filename;
  }

  $post_content .= "[usp-box type='$type' content='$content']";
  }
  }

  if ( $thumbnail )
  uspp_add_thumbnail_post( $post_id, $thumbnail );

  wp_update_post( array( 'ID' => $post_id, 'post_content' => $post_content ) );
  } */

//удаляем папку с изображениями при удалении поста
add_action( 'delete_post', 'uspp_delete_tempdir_attachments' );
function uspp_delete_tempdir_attachments( $postid ) {
    $dir_path = USP_UPLOAD_PATH . 'post-media/' . $postid;
    usp_remove_dir( $dir_path );
}

/* deprecated */
function uspp_form_field( $args ) {
    $field = new USPP_Form_Fields();

    return $field->get_field( $args );
}

add_action( 'uspp_update_post', 'uspp_send_mail_about_new_post', 10, 3 );
function uspp_send_mail_about_new_post( $post_id, $postData, $update ) {

    if ( $update || usp_check_access_console() )
        return false;

    $title = __( 'New write', 'userspace-publication' );
    $email = get_site_option( 'admin_email' );

    $textm = '<p>' . sprintf( __( 'An user added new write on the website "%s"', 'userspace-publication' ), get_bloginfo( 'name' ) ) . '.</p>';
    $textm .= '<p>' . __( 'The name of the write', 'userspace-publication' ) . ': <a href="' . get_permalink( $post_id ) . '">' . get_the_title( $post_id ) . '</a>' . '</p>';
    $textm .= '<p>' . __( 'The author of the write', 'userspace-publication' ) . ': <a href="' . usp_get_user_url( $postData['post_author'] ) . '">' . get_the_author_meta( 'display_name', $postData['post_author'] ) . '</a>' . '</p>';
    $textm .= '<p>' . __( 'Don\'t forget to check this write, probably it is waiting for your moderation', 'userspace-publication' ) . '.</p>';

    usp_mail( $email, $title, $textm );
}

add_filter( 'usp_uploader_manager_items', 'uspp_add_post_uploader_image_buttons', 10, 3 );
function uspp_add_post_uploader_image_buttons( $items, $attachment_id, $uploader ) {

    if ( ! in_array( $uploader->uploader_id, array( 'post_uploader', 'post_thumbnail' ) ) )
        return $items;

    $is_admin = function_exists( 'get_current_screen' ) && ! wp_doing_ajax() ? 1 : 0;

    $isImage = wp_attachment_is_image( $attachment_id );

    $formFields = new USPP_Public_Form_Fields( $uploader->post_type, array(
        'form_id' => $uploader->form_id
        ) );

    if ( ! $is_admin && ! isset( $_POST['is_wp_admin_page'] ) && $isImage && $uploader->uploader_id == 'post_uploader' && $formFields->is_active_field( 'post_thumbnail' ) ) {

        $items[] = array(
            'icon'    => 'fa-image',
            'title'   => __( 'Appoint a thumbnail', 'userspace-publication' ),
            'onclick' => 'uspp_set_post_thumbnail(' . $attachment_id . ',' . $uploader->post_parent . ',this);return false;'
        );
    }

    $addGallery = true;

    if ( $formFields->is_active_field( 'post_uploader' ) ) {

        $field = $formFields->get_field( 'post_uploader' );

        if ( $field->isset_prop( 'gallery' ) )
            $addGallery = $field->get_prop( 'gallery' );
    }

    if ( $isImage && $addGallery ) {

        $postGallery  = get_post_meta( $uploader->post_parent, 'uspp_post_gallery', 1 );
        $valueGallery = ($postGallery && in_array( $attachment_id, $postGallery )) ? $attachment_id : '';

        $items[] = array(
            'icon'    => ($postGallery && in_array( $attachment_id, $postGallery )) ? 'fa-toggle-on' : 'fa-toggle-off',
            'class'   => 'uspp-switch-gallery-button-' . $attachment_id,
            'title'   => __( 'Output in a gallery', 'userspace-publication' ),
            'content' => '<input type="hidden" id="uspp-post-usp-media__item-' . $attachment_id . '" name="uspp-post-gallery[]" value="' . $valueGallery . '">',
            'onclick' => 'uspp_switch_attachment_in_gallery(' . $attachment_id . ',this);return false;'
        );
    }

    return $items;
}

function uspp_get_post_gallery( $gallery_id, $attachment_ids ) {

    return usp_get_image_gallery( array(
        'id'           => 'uspp-post-gallery-' . $gallery_id,
        'center_align' => true,
        'attach_ids'   => $attachment_ids,
        //'width' => 500,
        'height'       => 350,
        'slides'       => array(
            'slide' => 'large',
            'full'  => 'large'
        ),
        'navigator'    => array(
            'thumbnails' => array(
                'width'  => 50,
                'height' => 50,
                'arrows' => true
            )
        )
        ) );
}

add_filter( 'uspp_public_form', 'uspp_add_public_form_captcha', 100 );
function uspp_add_public_form_captcha( $form ) {
    global $user_ID;

    if ( $user_ID )
        return $form;

    $captcha = usp_get_simple_captcha( array( 'img_size' => array( 72, 29 ) ) );

    if ( ! $captcha )
        return $form;

    $form .= '
      <div class="form-block-usp">
        <label>' . __( 'Enter characters', 'userspace-publication' ) . ' <span class="required">*</span></label>
        <img src="' . $captcha->img_src . '" alt="captcha" width="' . $captcha->img_size[0] . '" height="' . $captcha->img_size[1] . '" />
        <input id="usp_captcha_code" required name="usp_captcha_code" style="width: 160px;" size="' . $captcha->char_length . '" type="text" />
        <input id="usp_captcha_prefix" name="usp_captcha_prefix" type="hidden" value="' . $captcha->prefix . '" />
     </div>';

    return $form;
}

add_action( 'uspp_init_update_post', 'uspp_check_public_form_captcha', 10 );
function uspp_check_public_form_captcha() {
    global $user_ID;

    if ( ! $user_ID && isset( $_POST['usp_captcha_prefix'] ) ) {

        $usp_captcha_correct = usp_captcha_check_correct( $_POST['usp_captcha_code'], $_POST['usp_captcha_prefix'] );

        if ( ! $usp_captcha_correct ) {
            wp_die( __( 'Incorrect CAPTCHA!', 'userspace-publication' ) );
        }
    }
}

add_filter( 'the_content', 'uspp_message_post_moderation' );
function uspp_message_post_moderation( $content ) {
    global $post;

    if ( ! isset( $post ) || ! $post )
        return $content;

    if ( $post->post_status == 'pending' ) {
        $content = usp_get_notice( [ 'type' => 'error', 'text' => __( 'Publication pending approval!', 'userspace' ) ] ) . $content;
    }

    if ( $post->post_status == 'draft' ) {
        $content = usp_get_notice( [ 'type' => 'error', 'text' => __( 'Draft of a post!', 'userspace' ) ] ) . $content;
    }

    return $content;
}

//add_action( 'wp', 'uspp_post_bar_setup', 10 );
//function uspp_post_bar_setup() {
//    do_action( 'uspp_post_bar_setup' );
//}
