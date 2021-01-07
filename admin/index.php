<?php

require_once 'settings-page.php';

add_action( 'admin_init', 'uspp_public_admin_scripts' );
function uspp_public_admin_scripts() {
    wp_enqueue_style( 'uspp_public_admin_style', plugin_dir_url( __FILE__ ) . 'admin/assets/style.css' );
}

add_action( 'admin_menu', 'uspp_admin_page_publicform', 30 );
function uspp_admin_page_publicform() {
    add_submenu_page( 'manage-userspace', __( 'Form of publication', 'usp-publication' ), __( 'Form of publication', 'usp-publication' ), 'manage_options', 'manage-public-form', 'uspp_public_form_manager' );
}

function uspp_public_form_manager() {

    $post_type = (isset( $_GET['post-type'] )) ? $_GET['post-type'] : 'post';
    $form_id   = (isset( $_GET['form-id'] )) ? $_GET['form-id'] : 1;

    $shortCode = 'public-form post_type="' . $post_type . '"';

    if ( $form_id > 1 ) {
        $shortCode .= ' form_id="' . $form_id . '"';
    }

    $formManager = new USPP_Public_Form_Manager( $post_type, array(
        'form_id' => $form_id
        ) );

    $content = '<h2>' . __( 'Manage publication forms', 'usp-publication' ) . '</h2>';

    $content .= '<p>' . __( 'On this page you can manage the creation of publications for registered record types. Create custom fields for the form of publication of various types and manage', 'usp-publication' ) . '</p>';

    $content .= '<div id="uspp-public-form-manager">';

    $content .= $formManager->form_navi();

    $content .= usp_get_notice( [ 'text' => __( 'Use shortcode for publication form', 'usp-publication' ) . ' [' . $shortCode . ']' ] );

    $content .= $formManager->get_manager();

    $content .= '</div>';

    echo $content;
}

add_action( 'add_meta_boxes', 'uspp_custom_fields_editor_post', 1, 2 );
function uspp_custom_fields_editor_post( $post_type, $post ) {
    add_meta_box( 'custom_fields_editor_post', __( 'Arbitrary fields of  publication', 'usp-publication' ), 'uspp_custom_fields_list_posteditor', $post->post_type, 'normal', 'high' );
}

function uspp_custom_fields_list_posteditor( $post ) {
    $form_id = 1;

    if ( $post->ID && $post->post_type == 'post' )
        $form_id = get_post_meta( $post->ID, 'publicform-id', 1 );

    $content = uspp_get_custom_fields_edit_box( $post->ID, $post->post_type, $form_id );

    if ( ! $content )
        return false;

    echo $content;

    echo '<input type="hidden" name="uspp_custom_fields_nonce" value="' . wp_create_nonce( __FILE__ ) . '" />';
}

add_action( 'save_post', 'uspp_custom_fields_update', 0 );
function uspp_custom_fields_update( $post_id ) {
    if ( ! isset( $_POST['uspp_custom_fields_nonce'] ) )
        return false;
    if ( ! wp_verify_nonce( $_POST['uspp_custom_fields_nonce'], __FILE__ ) )
        return false;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return false;
    if ( ! current_user_can( 'edit_post', $post_id ) )
        return false;

    uspp_update_post_custom_fields( $post_id );

    return $post_id;
}

add_action( 'admin_init', 'uspp_public_form_admin_actions', 10 );
function uspp_public_form_admin_actions() {

    if ( ! isset( $_GET['page'] ) || $_GET['page'] != 'manage-public-form' )
        return false;

    if ( ! isset( $_GET['form-action'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'uspp-form-action' ) )
        return false;

    switch ( $_GET['form-action'] ) {

        case 'new-form':

            $newFormId = $_GET['form-id'];

            add_option( 'uspp_fields_post_' . $newFormId, array() );

            wp_redirect( admin_url( 'admin.php?page=manage-public-form&post-type=post&form-id=' . $newFormId ) );
            exit;

            break;

        case 'delete-form':

            $delFormId = $_GET['form-id'];

            delete_site_option( 'uspp_fields_post_' . $delFormId );

            wp_redirect( admin_url( 'admin.php?page=manage-public-form&post-type=post' ) );
            exit;

            break;
    }
}

/* deprecated: add_dashboard_metabox not used */
//add_action( 'usp_add_dashboard_metabox', 'uspp_add_publicpost_metabox' );
//function uspp_add_publicpost_metabox( $screen ) {
//    add_meta_box( 'uspp-publicpost-metabox', __( 'Posts awaiting approval', 'usp-publication' ), 'uspp_publicpost_metabox', $screen->id, 'column3' );
//}

//function uspp_publicpost_metabox() {
//
//    $posts = get_posts( array( 'numberposts' => -1, 'post_type' => 'any', 'post_status' => 'pending' ) );
//
//    if ( ! $posts ) {
//        echo '<p>' . __( 'No posts under moderation', 'usp-publication' ) . '</p>';
//        return;
//    }
//
//    echo '<table class="wp-list-table widefat fixed striped">';
//    echo '<tr>'
//    . '<th>' . __( 'Header', 'usp-publication' ) . '</th>'
//    . '<th>' . __( 'Author', 'usp-publication' ) . '</th>'
//    . '<th>' . __( 'Type', 'usp-publication' ) . '</th>'
//    . '</tr>';
//    foreach ( $posts as $post ) {
//        echo '<tr>'
//        . '<td><a href="' . get_edit_post_link( $post->ID ) . '" target="_blank">' . $post->post_title . '</a></td>'
//        . '<td>' . $post->post_author . ': ' . get_the_author_meta( 'user_login', $post->post_author ) . '</td>'
//        . '<td>' . $post->post_type . '</td>'
//        . '</tr>';
//    }
//    echo '</table>';
//}
