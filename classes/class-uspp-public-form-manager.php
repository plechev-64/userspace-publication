<?php

class USPP_Public_Form_Manager extends USPP_Public_Form_Fields {
    function __construct( $post_type, $args = false ) {

        parent::__construct( $post_type, $args );
    }

    function form_navi() {

        $post_types = get_post_types( array(
            'public'   => true,
            '_builtin' => false
            ), 'objects' );

        $types = array( 'post' => __( 'Records', 'userspace-publication' ) );

        foreach ( $post_types as $post_type ) {
            $types[$post_type->name] = $post_type->label;
        }

        $content = '<div class="usp-custom-fields-navi">';

        $content .= '<ul class="usp-types-list">';

        foreach ( $types as $type => $name ) {

            $class = ($this->post_type == $type) ? 'class="current-item"' : '';

            $content .= '<li ' . $class . '><a href="' . admin_url( 'admin.php?page=manage-public-form&post-type=' . $type ) . '">' . $name . '</a></li>';
        }

        $content .= '</ul>';

        $content .= '</div>';

        //if ( $this->post_type == 'post' ) {

        global $wpdb;

        $form_id = 1;

        $postForms = $wpdb->get_col( "SELECT option_name FROM " . $wpdb->options . " WHERE option_name LIKE 'uspp_fields_" . $this->post_type . "_%' AND option_name NOT LIKE '%_structure' ORDER BY option_id ASC" );

        if ( $postForms )
            natcasesort( $postForms );

        $content .= '<div class="usp-custom-fields-navi">';

        $content .= '<ul class="usp-types-list">';

        foreach ( $postForms as $name ) {
            preg_match( "/uspp_fields_" . $this->post_type . "_(\d+)\z/", $name, $matches );

            if ( ! $matches )
                continue;

            $id = intval( $matches[1] );

            if ( ! $id )
                continue;

            $form_id = $id;

            $class = ($this->form_id == $form_id) ? 'class="current-item"' : '';

            $content .= '<li ' . $class . '><a href="' . admin_url( 'admin.php?page=manage-public-form&post-type=' . $this->post_type . '&form-id=' . $form_id ) . '">' . __( 'Form', 'userspace-publication' ) . ' ID: ' . $form_id . '</a></li>';
        }

        $content .= '<li><a class="action-form" href="' . wp_nonce_url( admin_url( 'admin.php?page=manage-public-form&form-action=new-form&post-type=' . $this->post_type . '&form-id=' . ($form_id + 1) ), 'uspp-form-action' ) . '"><i class="uspi fa-plus"></i><span>' . __( 'Add form', 'userspace-publication' ) . '</span></a></li>';

        $content .= '</ul>';

        $content .= '</div>';

        $actionButtons = array(
            array(
                'label'   => __( 'Copy', 'userspace-publication' ),
                'icon'    => 'fa-copy',
                'onclick' => 'usp_manager_copy_fields("' . $this->post_type . '_' . ($form_id + 1) . '");'
            )
        );

        if ( $this->form_id != 1 ) {

            $actionButtons = array_merge( array(
                array(
                    'label' => __( 'Delete form', 'userspace-publication' ),
                    'icon'  => 'fa-trash',
                    'href'  => wp_nonce_url( admin_url( 'admin.php?page=manage-public-form&form-action=delete-form&post-type=' . $this->post_type . '&form-id=' . $this->form_id ), 'uspp-form-action' )
                )
                ), $actionButtons );
        }

        $actionButtons = apply_filters( 'uspp_public_form_admin_actions_args', $actionButtons, $this );

        if ( $actionButtons ) {

            $content .= '<div class="uspp-custom-fields-menu">';

            $content .= '<ul class="usp-types-list">';

            foreach ( $actionButtons as $actionButton ) {

                $actionButton['class'] = 'action-button';

                $content .= '<li>' . usp_get_button( $actionButton ) . '</li>';
            }

            $content .= '</ul>';

            $content .= '</div>';
        }

        return $content;
    }

}
