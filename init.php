<?php

add_action( 'wp', 'uspp_deleted_post_notice' );
function uspp_deleted_post_notice() {
	if ( isset( $_GET['public'] ) && 'deleted' == $_GET['public'] ) {
		add_action( 'usp_area_notice', function () {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo usp_get_notice( [ 'text' => esc_html__( 'The publication has been successfully removed!', 'userspace-publication' ) ] );
		} );
	}
}

// translation for js-file
add_filter( 'usp_init_js_variables', 'uspp_init_js_public_variables', 10 );
function uspp_init_js_public_variables( $data ) {
	$data['local']['save']               = __( 'Save', 'userspace-publication' );
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
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return false;
	}

	if ( isset( $_POST['uspp-edit-post'], $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'uspp-edit-post' ) ) {
		uspp_edit_post();
	}
}

add_action( 'init', 'uspp_setup_author_role', 10 );
function uspp_setup_author_role() {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		return;
	}

	if ( isset( $_REQUEST['post_id'] ) ) {
		global $current_user;

		$current_user->allcaps['edit_published_pages'] = 1;
		$current_user->allcaps['edit_others_pages']    = 1;
		$current_user->allcaps['edit_others_posts']    = 1;
	}
}


add_action( 'usp_init_tabs', 'uspp_init_post_tabs', 20 );
function uspp_init_post_tabs() {
	if ( usp_get_option( 'uspp_show_list_of_publications', 1 ) == 1 ) {
		$post_types = get_post_types( [
			'public'   => true,
			'_builtin' => false,
		], 'objects' );

		$types = [ 'post' => __( 'Posts', 'userspace-publication' ) ];

		foreach ( $post_types as $post_type ) {
			$types[ $post_type->name ] = $post_type->label;
		}

		if ( usp_get_option( 'uspp_post_types_list' ) ) {
			foreach ( $types as $post_types_k => $name ) {
				$find = array_search( $post_types_k, usp_get_option( 'uspp_post_types_list' ) );

				if ( false === $find ) {
					unset( $types[ $post_types_k ] );
				}
			}
		}

		if ( $types ) {
			$tab_data = [
				'id'       => 'posts',
				'name'     => __( 'Posts', 'userspace-publication' ),
				'title'    => __( 'Published', 'userspace-publication' ) . ' "' . __( 'Posts', 'userspace-publication' ) . '"',
				'supports' => [ 'ajax', 'cache' ],
				'public'   => usp_get_option( 'uspp_tab_list_of_publications', 1 ),
				'icon'     => 'fa-list',
				'output'   => 'menu',
				'content'  => [],
			];

			foreach ( $types as $post_type => $name ) {
				$tab_data['content'][] = [
					'id'       => 'type-' . $post_type,
					'name'     => $name,
					'title'    => __( 'Published', 'userspace-publication' ) . ' "' . $name . '"',
					'icon'     => 'fa-list',
					'callback' => [
						'name' => 'uspp_get_postslist',
						'args' => [ $post_type, $name ],
					],
				];
			}

			usp_tab( $tab_data );
		}
	}

	if ( usp_get_option( 'uspp_tab_public_form', 1 ) == 1 ) {
		usp_tab(
			[
				'id'      => 'postform',
				'name'    => __( 'Publication', 'userspace-publication' ),
				'title'   => __( 'Form of publication', 'userspace-publication' ),
				'public'  => 0,
				'icon'    => 'fa-edit',
				'content' => [
					[
						'callback' => [
							'name' => 'uspp_tab_postform',
						],
					],
				],
			]
		);
	}
}
