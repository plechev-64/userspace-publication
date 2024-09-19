<?php

class USPP_Author_Postlist extends TableManager {

	private $_required_params = [
		'orderby' => 'post_date',
		'order'   => 'DESC',
		'number'  => 24
	];

	function __construct( $args = [] ) {
		parent:: __construct( array_merge( $this->_required_params, $args ) );
	}

	function get_query() {
		global $user_ID;

		$postStatus = [ 'publish' ];

		if ( $user_ID == $this->get_param( 'post_author' ) ) {
			$postStatus[] = 'private';
			$postStatus[] = 'pending';
			$postStatus[] = 'draft';
			$postStatus[] = 'trash';
		}

		return ( new PostsQuery() )
			->select( [
				'ID',
				'post_date',
				'post_title',
				'post_status',
			] )
			->where( [
				'post_author'     => absint( $this->get_param( 'post_author' ) ),
				'post_type'       => sanitize_text_field( $this->get_param( 'post_type' ) ),
				'post_status__in' => $postStatus,
			] );
	}

	function get_table_cols() {

		return [
			'post_date'   => [
				'align' => 'center',
				'title' => __( 'Date', 'userspace-publication' ),
				'width' => 20,
			],
			'post_title'  => [
				'title' => __( 'Title', 'userspace-publication' ),
				'width' => 60,
			],
			'post_status' => [
				'align' => 'center',
				'title' => __( 'Status', 'userspace-publication' ),
				'width' => 20,
			],
		];
	}

	function get_table_row( $item ) {
		return [
			'post_date'   => mysql2date( 'd.m.y', $item->post_date ),
			'post_title'  => $this->get_post_title( $item ),
			'post_status' => $this->get_post_status( $item->post_status ),
		];
	}

	function get_post_title( $rowData ) {
		if ( empty( $rowData->post_title ) ) {
			$rowData->post_title = "<i class='uspi fa-horizontal-ellipsis' aria-hidden='true'></i>";
		}

		return ( 'trash' == $rowData->post_status ) ? $rowData->post_title : '<a target="_blank" href="/?p=' . $rowData->ID . '">' . $rowData->post_title . '</a>';
	}

	function get_post_status( $post_status ) {
		switch ( $post_status ) {
			case 'pending':
				$status = __( 'pending', 'userspace-publication' );
				break;
			case 'draft':
				$status = __( 'draft', 'userspace-publication' );
				break;
			case 'private':
				$status = __( 'private', 'userspace-publication' );
				break;
			case 'trash':
				$status = __( 'deleted', 'userspace-publication' );
				break;
			default:
				$status = __( 'published', 'userspace-publication' );
		}

		return '<span class="uspp-status__' . $post_status . '">' . $status . '</span>';
	}

	function get_no_result_notice() {
		return usp_get_notice( [ 'text' => __( 'Here has nothing been published yet', 'userspace-publication' ) ] );
	}

}
