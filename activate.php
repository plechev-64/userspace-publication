<?php

global $usp_options;

if ( ! isset( $usp_options['info_author_recall'] ) )
	$usp_options['info_author_recall']			 = 1;
if ( ! isset( $usp_options['moderation_public_post'] ) )
	$usp_options['moderation_public_post']		 = 1;
if ( ! isset( $usp_options['id_parent_category'] ) )
	$usp_options['id_parent_category']			 = '';
if ( ! isset( $usp_options['user_public_access_recall'] ) )
	$usp_options['user_public_access_recall']	 = 2;

if ( ! isset( $usp_options['public_form_page_rcl'] ) ) {
	if ( ! rcl_isset_plugin_page( 'public-editpage' ) ) {
		$usp_options['public_form_page_rcl'] = rcl_create_plugin_page( 'public-editpage', [
			'post_title'	 => 'Форма публикации',
			'post_content'	 => '[public-form]',
			'post_name'		 => 'rcl-postedit'
			] );
	}
}

if ( ! isset( $usp_options['publics_block_rcl'] ) )
	$usp_options['publics_block_rcl']		 = 1;
if ( ! isset( $usp_options['view_publics_block_rcl'] ) )
	$usp_options['view_publics_block_rcl']	 = 1;

if ( ! isset( $usp_options['type_text_editor'] ) ) {
	$usp_options['type_text_editor'] = 1;
	$usp_options['wp_editor']		 = array( 1, 2 );
}

if ( ! isset( $usp_options['output_public_form_rcl'] ) )
	$usp_options['output_public_form_rcl']		 = 1;
if ( ! isset( $usp_options['user_public_access_recall'] ) )
	$usp_options['user_public_access_recall']	 = 2;
if ( ! isset( $usp_options['front_editing'] ) )
	$usp_options['front_editing']				 = array( 2 );
if ( ! isset( $usp_options['media_uploader'] ) )
	$usp_options['media_uploader']				 = 1;

if ( ! isset( $usp_options['pm_rcl'] ) )
	$usp_options['pm_rcl']	 = 1;
if ( ! isset( $usp_options['pm_place'] ) )
	$usp_options['pm_place'] = 0;

update_site_option( 'rcl_global_options', $usp_options );
