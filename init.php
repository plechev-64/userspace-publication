<?php

add_action( 'wp', 'uspp_deleted_post_notice' );
function uspp_deleted_post_notice() {
    if ( isset( $_GET['public'] ) && $_GET['public'] == 'deleted' )
        add_action( 'usp_area_notice', function() {
            echo usp_get_notice( [ 'text' => __( 'The publication has been successfully removed!', 'userspace-publication' ) ] );
        } );
}

add_filter( 'usp_init_js_variables', 'uspp_init_js_public_variables', 10 );
function uspp_init_js_public_variables( $data ) {

    $data['local']['preview']            = __( 'Preview', 'userspace-publication' );
    $data['local']['publish']            = __( 'Publish', 'userspace-publication' );
    $data['local']['save_draft']         = __( 'Save as Draft', 'userspace-publication' );
    $data['local']['edit']               = __( 'Edit', 'userspace-publication' );
    $data['local']['edit_box_title']     = __( 'Quick edit', 'userspace-publication' );
    $data['local']['allowed_downloads']  = __( 'You have exceeded the allowed number of downloads! Max:', 'userspace-publication' );
    $data['local']['upload_size_public'] = __( 'Exceeds the maximum file size! Max:', 'userspace-publication' );

    return $data;
}

add_action( 'wp', 'uspp_edit_post_activate' );
function uspp_edit_post_activate() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
        return false;
    if ( isset( $_POST['uspp-edit-post'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'uspp-edit-post' ) ) {
        uspp_edit_post();
    }
}

add_action( 'init', 'uspp_setup_author_role', 10 );
function uspp_setup_author_role() {
    global $current_user;

    if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
        return;

    if ( isset( $_REQUEST['post_id'] ) ) {
        $current_user->allcaps['edit_published_pages'] = 1;
        $current_user->allcaps['edit_others_pages']    = 1;
        $current_user->allcaps['edit_others_posts']    = 1;
    }
}

add_action( 'usp_init_tabs', 'uspp_init_publics_block', 20 );
function uspp_init_publics_block() {

    if ( usp_get_option( 'uspp_show_list_of_publications', 1 ) == 1 ) {

        $post_types = get_post_types( array(
            'public'   => true,
            '_builtin' => false
            ), 'objects' );

        $types = array( 'post' => __( 'Records', 'userspace-publication' ) );

        foreach ( $post_types as $post_type ) {
            $types[$post_type->name] = $post_type->label;
        }

        if ( usp_get_option( 'uspp_post_types_list' ) ) {
            foreach ( $types as $post_typen => $name ) {
                $find = array_search( $post_typen, usp_get_option( 'uspp_post_types_list' ) );
                if ( $find === false ) {
                    unset( $types[$post_typen] );
                }
            }
        }

        if ( $types ) {

            $tab_data = array(
                'id'       => 'publics',
                'name'     => __( 'Posts', 'userspace-publication' ),
                'title'    => __( 'Published', 'userspace-publication' ) . ' "' . __( 'Posts', 'userspace-publication' ) . '"',
                'supports' => array( 'ajax', 'cache' ),
                'public'   => usp_get_option( 'uspp_tab_list_of_publications', 1 ),
                'icon'     => 'fa-list',
                'output'   => 'menu',
                'content'  => array()
            );

            foreach ( $types as $post_type => $name ) {
                $tab_data['content'][] = array(
                    'id'       => 'type-' . $post_type,
                    'name'     => $name,
                    'title'    => __( 'Published', 'userspace-publication' ) . ' "' . $name . '"',
                    'icon'     => 'fa-list',
                    'callback' => array(
                        'name' => 'uspp_get_postslist',
                        'args' => array( $post_type, $name )
                    )
                );
            }

            usp_tab( $tab_data );
        }
    }

    if ( usp_get_option( 'uspp_tab_public_form', 1 ) == 1 ) {

        usp_tab(
            array(
                'id'      => 'postform',
                'name'    => __( 'Publication', 'userspace-publication' ),
                'title'   => __( 'Form of publication', 'userspace-publication' ),
                'public'  => 0,
                'icon'    => 'fa-edit',
                'content' => array(
                    array(
                        'callback' => array(
                            'name' => 'uspp_tab_postform'
                        )
                    )
                )
            )
        );
    }
}
