<?php

class USPP_Author_Postlist extends USP_Table_Manager {

    public $post_author;
    public $post_type;

    function __construct( $args = array() ) {
        $this->init_custom_prop( 'post_type', isset( $args['post_type'] ) ? $args['post_type'] : null  );

        $this->init_custom_prop( 'post_author', isset( $args['post_author'] ) ? $args['post_author'] : null  );

        parent::
        __construct( array(
            'number'       => 24,
            'is_ajax'      => 1,
            'reset_filter' => false
        ) );
    }

    function get_query() {
        global $user_ID;

        $postStatus = [ 'publish' ];

        if ( $user_ID == $this->post_author ) {
            $postStatus[] = 'private';
            $postStatus[] = 'pending';
            $postStatus[] = 'draft';
            $postStatus[] = 'trash';
        }

        $data = RQ::tbl( new USP_Posts_Query() )
            ->select( [
                'ID',
                'post_date',
                'post_title',
                'post_status',
            ] )
            ->where( [
                'post_author'     => absint( $this->post_author ),
                'post_type'       => sanitize_text_field( $this->post_type ),
                'post_status__in' => $postStatus,
            ] )
            ->orderby( 'wp_posts.post_date', 'DESC' );

        return $data;
    }

    function get_table_cols() {

        return array(
            'post_date'   => [
                'align' => 'center',
                'title' => __( 'Date', 'userspace-publication' ),
                'width' => 20
            ],
            'post_title'  => [
                'title' => __( 'Title', 'userspace-publication' ),
                'width' => 60
            ],
            'post_status' => [
                'align' => 'center',
                'title' => __( 'Status', 'userspace-publication' ),
                'width' => 20
            ],
        );
    }

    function get_table_row( $rowData ) {
        return array(
            'post_date'   => mysql2date( 'd.m.y', $rowData->post_date ),
            'post_title'  => $this->get_post_title( $rowData ),
            'post_status' => $this->get_post_status( $rowData->post_status ),
        );
    }

    function get_post_title( $rowData ) {
        if ( empty( $rowData->post_title ) )
            $rowData->post_title = "<i class='uspi fa-horizontal-ellipsis' aria-hidden='true'></i>";

        return ($rowData->post_status == 'trash') ? $rowData->post_title : '<a target="_blank" href="/?p=' . $rowData->ID . '">' . $rowData->post_title . '</a>';
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
            default: $status = __( 'published', 'userspace-publication' );
        }

        return '<span class="uspp-status__' . $post_status . '">' . $status . '</span>';
    }

    function get_no_result_notice() {
        return usp_get_notice( [ 'text' => __( 'Here has nothing been published yet', 'userspace-publication' ) ] );
    }

}
