<?php

class USPP_Edit_Post {

    public $post_id; //идентификатор поста
    public $post     = array();
    public $post_type; //тип записи
    public $update   = false; //действие
    public $user_can = array(
        'publish' => false,
        'edit'    => false,
        'upload'  => false
    );

    function __construct() {

        if ( isset( $_FILES ) ) {
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
            require_once(ABSPATH . "wp-admin" . '/includes/media.php');
        }

        if ( isset( $_POST['post_type'] ) && $_POST['post_type'] ) {
            $this->post_type = sanitize_text_field( $_POST['post_type'] );
        }

        $post_id = isset( $_POST['post_ID'] ) && $_POST['post_ID'] ? $_POST['post_ID'] : (isset( $_POST['post_id'] ) && $_POST['post_id'] ? $_POST['post_id'] : 0);

        if ( $post_id ) {

            $this->post_id = intval( $post_id );

            $post = get_post( $this->post_id );

            $this->post = $post;

            $this->post_type = $post->post_type;

            $this->update = true;
        }

        $this->setup_user_can();

        if ( ! $this->user_can )
            $this->error( __( 'Error publishing!', 'usp-publication' ) . ' Error 100' );

        do_action( 'uspp_init_update_post', $this );
    }

    function error( $error ) {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            wp_send_json( array( 'error' => $error ) );
        } else {
            wp_die( $error );
        }
    }

    function setup_user_can() {
        global $user_ID;

        if ( $this->update ) {

            if ( $this->post_type == 'post-group' ) {

                if ( rcl_can_user_edit_post_group( $this->post_id ) )
                    $this->user_can['edit'] = true;
            } else {

                if ( current_user_can( 'edit_post', $this->post_id ) )
                    $this->user_can['edit'] = true;

                if ( usp_is_user_role( $user_ID, array( 'administrator', 'editor' ) ) || ! uspp_is_limit_editing( $this->post->post_date ) )
                    $this->user_can['edit'] = true;
            }
        } else {

            $this->user_can['publish'] = true;

            $user_can = usp_get_option( 'uspp_access_publicform', 2 );

            if ( $user_can ) {

                if ( $user_ID ) {

                    $userinfo = get_userdata( $user_ID );

                    if ( $userinfo->user_level < $user_can )
                        $this->user_can['publish'] = false;
                } else {

                    $this->user_can['publish'] = false;
                }
            }
        }

        $this->user_can = apply_filters( 'uspp_public_update_user_can', $this->user_can, $this );
    }

    function update_thumbnail() {

        $thumbnail_id = isset( $_POST['post_thumbnail'] ) ? $_POST['post_thumbnail'] : 0;

        $currentThID = $this->post_id ? get_post_meta( $this->post_id, '_thumbnail_id', 1 ) : 0;

        if ( $thumbnail_id ) {

            if ( $currentThID == $thumbnail_id )
                return false;

            update_post_meta( $this->post_id, '_thumbnail_id', $thumbnail_id );
        } else {

            if ( $currentThID )
                delete_post_meta( $this->post_id, '_thumbnail_id' );
        }
    }

    function add_attachments_from_temps( $user_id ) {

        $temps = usp_get_temp_media( array(
            'user_id'         => $user_id,
            'uploader_id__in' => array( 'post_uploader', 'post_thumbnail' )
            ) );

        if ( $temps ) {

            $thumbnail_id = isset( $_POST['post_thumbnail'] ) ? $_POST['post_thumbnail'] : 0;

            foreach ( $temps as $temp ) {

                $attachData = array(
                    'ID'          => $temp->media_id,
                    'post_parent' => $this->post_id,
                    'post_author' => $user_id
                );

                wp_update_post( $attachData );
            }
        }

        return $temps;
    }

    function update_post_gallery() {

        $postGallery = isset( $_POST['uspp-post-gallery'] ) ? $_POST['uspp-post-gallery'] : false;

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
            update_post_meta( $this->post_id, 'rcl_post_gallery', $gallery );
        } else {
            delete_post_meta( $this->post_id, 'rcl_post_gallery' );
        }
    }

    function get_status_post( $moderation ) {
        global $user_ID;

        if ( isset( $_POST['save-as-draft'] ) )
            return 'draft';

        if ( usp_is_user_role( $user_ID, array( 'administrator', 'editor' ) ) )
            return 'publish';

        if ( $moderation == 1 ) {

            $types = usp_get_option( 'uspp_post_types_moderation' );

            if ( $types ) {
                $post_status = in_array( $this->post_type, $types ) ? 'pending' : 'publish';
            } else {
                $post_status = 'pending';
            }
        } else {
            $post_status = 'publish';
        }

        $rating = usp_get_option( 'rating_no_moderation' );

        if ( $rating ) {
            $all_r       = rcl_get_user_rating( $user_ID );
            if ( $all_r >= $rating )
                $post_status = 'publish';
        }

        return $post_status;
    }

    function update_post() {
        global $user_ID;

        $postdata = array(
            'post_type'    => $this->post_type,
            'post_title'   => (isset( $_POST['post_title'] )) ? sanitize_text_field( $_POST['post_title'] ) : '',
            'post_excerpt' => (isset( $_POST['post_excerpt'] )) ? $_POST['post_excerpt'] : '',
            'post_content' => (isset( $_POST['post_content'] )) ? $_POST['post_content'] : ''
        );

        if ( ! $this->post || ! $this->post->post_name ) {
            $postdata['post_name'] = sanitize_title( $postdata['post_title'] );
        }

        if ( $this->post_id ) {
            $postdata['ID']          = $this->post_id;
            $postdata['post_author'] = $this->post->post_author;
        } else {
            $postdata['post_author'] = $user_ID;
        }

        $postdata['post_status'] = $this->get_status_post( usp_get_option( 'uspp_send_to_moderation', 1 ) );

        $postdata = apply_filters( 'uspp_pre_update_postdata', $postdata, $this );

        if ( ! $postdata )
            return false;

        do_action( 'uspp_pre_update_post', $postdata );

        if ( isset( $_POST['form_id'] ) ) {
            $formID = intval( $_POST['form_id'] );
        }

        if ( ! $this->post_id ) {

            $this->post_id = wp_insert_post( $postdata );

            if ( ! $this->post_id ) {
                $this->error( __( 'Error publishing!', 'usp-publication' ) . ' Error 101' );
            } else {

                if ( $formID > 1 )
                    add_post_meta( $this->post_id, 'publicform-id', $formID );

                $post_name = wp_unique_post_slug( $postdata['post_name'], $this->post_id, 'publish', $postdata['post_type'], 0 );

                wp_update_post( [
                    'ID'        => $this->post_id,
                    'post_name' => $post_name
                ] );
            }
        } else {
            wp_update_post( $postdata );
        }

        $this->update_thumbnail();

        if ( ! $this->update ) {
            $this->add_attachments_from_temps( $postdata['post_author'] );
        }

        $this->update_post_gallery();

        delete_post_meta( $this->post_id, 'uspp_slider' );

        uspp_update_post_custom_fields( $this->post_id, $formID );

        usp_delete_temp_media_by_args( array(
            'user_id'         => $user_ID,
            'uploader_id__in' => array( 'post_uploader', 'post_thumbnail' )
        ) );

        do_action( 'uspp_update_post', $this->post_id, $postdata, $this->update, $this );

        if ( isset( $_POST['save-as-draft'] ) ) {
            wp_redirect( get_permalink( usp_get_option( 'uspp_public_form_page' ) ) . '?draft=saved&rcl-post-edit=' . $this->post_id );
            exit;
        }

        if ( $postdata['post_status'] == 'pending' ) {
            if ( $user_ID )
                $redirect_url = get_bloginfo( 'wpurl' ) . '/?p=' . $this->post_id . '&preview=true';
            else
                $redirect_url = get_permalink( usp_get_option( 'uspp_guest_redirect' ) );
        } else {
            $redirect_url = get_permalink( $this->post_id );
        }

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            wp_send_json( array( 'redirect' => $redirect_url ) );
        }

        header( "Location: $redirect_url", true, 302 );
        exit;
    }

}
