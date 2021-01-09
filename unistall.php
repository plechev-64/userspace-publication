<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$usp_options = get_site_option( 'usp_global_options' );

// delete all global settings
if ( $usp_options ) {
    unset( $usp_options['uspp_public_form_page'] );
    unset( $usp_options['uspp_author_box'] );
    unset( $usp_options['uspp_author_box_post_types'] );
    unset( $usp_options['uspp_show_list_of_publications'] );
    unset( $usp_options['uspp_post_types_list'] );
    unset( $usp_options['uspp_tab_list_of_publications'] );
    unset( $usp_options['uspp_public_preview'] );
    unset( $usp_options['uspp_public_draft'] );
    unset( $usp_options['uspp_default_thumb'] );
    unset( $usp_options['uspp_tab_public_form'] );
    unset( $usp_options['uspp_id_public_form'] );
    unset( $usp_options['uspp_access_publicform'] );
    unset( $usp_options['uspp_guest_redirect'] );
    unset( $usp_options['uspp_send_to_moderation'] );
    unset( $usp_options['uspp_post_types_moderation'] );
    unset( $usp_options['uspp_front_post_edit'] );
    unset( $usp_options['uspp_time_editing'] );
    unset( $usp_options['uspp_custom_fields'] );
    unset( $usp_options['uspp_cf_place'] );
    unset( $usp_options['uspp_cf_post_types'] );

    update_site_option( 'usp_global_options', $usp_options );
}

// delete created pages
$plugin_page = get_site_option( 'uspp_publication_page' );
wp_delete_post( $plugin_page );

// del publications page
delete_site_option( 'uspp_publication_page' );
