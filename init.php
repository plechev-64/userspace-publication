<?php

add_action( 'wp', 'rcl_deleted_post_notice' );
function rcl_deleted_post_notice() {
    if ( isset( $_GET['public'] ) && $_GET['public'] == 'deleted' )
        add_action( 'rcl_area_notice', function() {
            echo rcl_get_notice( [ 'text' => __( 'The publication has been successfully removed!', 'usp-publication' ) ] );
        } );
}

add_filter( 'usp_init_js_variables', 'rcl_init_js_public_variables', 10 );
function rcl_init_js_public_variables( $data ) {

    $data['local']['preview']            = __( 'Preview', 'usp-publication' );
    $data['local']['publish']            = __( 'Publish', 'usp-publication' );
    $data['local']['save_draft']         = __( 'Save as Draft', 'usp-publication' );
    $data['local']['edit']               = __( 'Edit', 'usp-publication' );
    $data['local']['edit_box_title']     = __( 'Quick edit', 'usp-publication' );
    $data['local']['allowed_downloads']  = __( 'You have exceeded the allowed number of downloads! Max:', 'usp-publication' );
    $data['local']['upload_size_public'] = __( 'Exceeds the maximum file size! Max:', 'usp-publication' );

    return $data;
}

add_action( 'wp', 'rcl_edit_post_activate' );
function rcl_edit_post_activate() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
        return false;
    if ( isset( $_POST['rcl-edit-post'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'rcl-edit-post' ) ) {
        rcl_edit_post();
    }
}

add_action( 'init', 'rcl_setup_author_role', 10 );
function rcl_setup_author_role() {
    global $current_user;

    if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
        return;

    if ( isset( $_REQUEST['post_id'] ) ) {
        $current_user->allcaps['edit_published_pages'] = 1;
        $current_user->allcaps['edit_others_pages']    = 1;
        $current_user->allcaps['edit_others_posts']    = 1;
    }
}

add_action( 'rcl_init_tabs', 'rcl_init_publics_block', 20 );
function rcl_init_publics_block() {

    if ( rcl_get_option( 'uspp_show_list_of_publications', 1 ) == 1 ) {

        $post_types = get_post_types( array(
            'public'   => true,
            '_builtin' => false
            ), 'objects' );

        $types = array( 'post' => __( 'Records', 'usp-publication' ) );

        foreach ( $post_types as $post_type ) {
            $types[$post_type->name] = $post_type->label;
        }

        if ( rcl_get_option( 'uspp_post_types_list' ) ) {
            foreach ( $types as $post_typen => $name ) {
                $find = array_search( $post_typen, rcl_get_option( 'uspp_post_types_list' ) );
                if ( $find === false ) {
                    unset( $types[$post_typen] );
                }
            }
        }

        if ( $types ) {

            $tab_data = array(
                'id'       => 'publics',
                'name'     => __( 'Posts', 'usp-publication' ),
                'title'    => __( 'Published', 'usp-publication' ) . ' "' . __( 'Posts', 'usp-publication' ) . '"',
                'supports' => array( 'ajax', 'cache' ),
                'public'   => rcl_get_option( 'uspp_tab_list_of_publications', 1 ),
                'icon'     => 'fa-list',
                'output'   => 'menu',
                'content'  => array()
            );

            foreach ( $types as $post_type => $name ) {
                $tab_data['content'][] = array(
                    'id'       => 'type-' . $post_type,
                    'name'     => $name,
                    'title'    => __( 'Published', 'usp-publication' ) . ' "' . $name . '"',
                    'icon'     => 'fa-list',
                    'callback' => array(
                        'name' => 'rcl_get_postslist',
                        'args' => array( $post_type, $name )
                    )
                );
            }

            rcl_tab( $tab_data );
        }
    }

    if ( rcl_get_option( 'uspp_tab_public_form', 1 ) == 1 ) {

        rcl_tab(
            array(
                'id'      => 'postform',
                'name'    => __( 'Publication', 'usp-publication' ),
                'title'   => __( 'Form of publication', 'usp-publication' ),
                'public'  => 0,
                'icon'    => 'fa-edit',
                'content' => array(
                    array(
                        'callback' => array(
                            'name' => 'rcl_tab_postform'
                        )
                    )
                )
            )
        );
    }
}
