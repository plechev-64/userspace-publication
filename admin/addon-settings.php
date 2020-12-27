<?php

add_filter( 'rcl_options', 'rcl_get_publics_options_page' );
function rcl_get_publics_options_page( $options ) {
    global $_wp_additional_image_sizes, $wpdb;

    $_wp_additional_image_sizes['thumbnail'] = 1;
    $_wp_additional_image_sizes['medium']    = 1;
    $_wp_additional_image_sizes['large']     = 1;
    foreach ( $_wp_additional_image_sizes as $name => $size ) {
        $sh_name        = $name;
        if ( $size != 1 )
            $sh_name        .= ' (' . $size['width'] . '*' . $size['height'] . ')';
        $d_sizes[$name] = $sh_name;
    }

    $post_types = get_post_types( array(
        'public'   => true,
        '_builtin' => false
        ), 'objects' );

    $types = array( 'post' => __( 'Records', 'usp-publication' ) );

    foreach ( $post_types as $post_type ) {
        $types[$post_type->name] = $post_type->label;
    }

    $pages = rcl_get_pages_ids();

    $options->add_box( 'publicpost', array(
        'title' => __( 'Publication settings', 'usp-publication' ),
        'icon'  => 'fa-pencil-square-o'
    ) )->add_group( 'general', array(
        'title' => __( 'General settings', 'usp-publication' )
    ) )->add_options( array(
        array(
            'type'   => 'select',
            'slug'   => 'uspp_public_form_page',
            'title'  => __( 'Publishing and editing', 'usp-publication' ),
            'values' => $pages,
            'notice' => __( 'You are required to publish a links to managing publications, you must specify the page with the shortcode [public-form]', 'usp-publication' )
        ),
        array(
            'type'      => 'select',
            'slug'      => 'uspp_author_box',
            'title'     => __( 'Display information about the author', 'usp-publication' ),
            'values'    => array(
                __( 'Disabled', 'usp-publication' ),
                __( 'Enabled', 'usp-publication' )
            ),
            'childrens' => array(
                1 => array(
                    array(
                        'type'   => 'checkbox',
                        'slug'   => 'post_types_authbox',
                        'title'  => __( 'Types of write for the author`s block output', 'usp-publication' ),
                        'values' => $types,
                        'notice' => __( 'Select the types of writes where the author`s block should be displayed. If nothing is specified, it is displayed everywhere', 'usp-publication' )
                    )
                )
            )
        ),
        array(
            'type'      => 'select',
            'slug'      => 'uspp_show_list_of_publications',
            'title'     => __( 'List of publications tab', 'usp-publication' ),
            'values'    => array( __( 'Disabled', 'usp-publication' ), __( 'Enabled', 'usp-publication' ) ),
            'childrens' => array(
                1 => array(
                    array(
                        'type'   => 'checkbox',
                        'slug'   => 'post_types_list',
                        'title'  => __( 'Type of post for output a list of writes', 'usp-publication' ),
                        'values' => $types,
                        'notice' => __( 'Select the type of post which will be to output its archive of writes in this tab. If nothing is specified, it will be outputed a writes all types', 'usp-publication' )
                    ),
                    array(
                        'type'   => 'select',
                        'slug'   => 'uspp_tab_list_of_publications',
                        'title'  => __( 'List of publications of the user', 'usp-publication' ),
                        'values' => array( __( 'Only owner of the account', 'usp-publication' ), __( 'Show everyone including guests', 'usp-publication' ) )
                    )
                )
            )
        )
    ) );

    $options->box( 'publicpost' )->add_group( 'form', array(
        'title' => __( 'Form of publication', 'usp-publication' )
    ) )->add_options( array(
        array(
            'type'   => 'select',
            'slug'   => 'public_preview',
            'title'  => __( 'Use preview', 'usp-publication' ),
            'values' => array( __( 'No', 'usp-publication' ), __( 'Yes', 'usp-publication' ) )
        ),
        array(
            'type'   => 'select',
            'slug'   => 'public_draft',
            'title'  => __( 'Use draft', 'usp-publication' ),
            'values' => array( __( 'No', 'usp-publication' ), __( 'Yes', 'usp-publication' ) )
        ),
        array(
            'type'   => 'select',
            'slug'   => 'default_size_thumb',
            'title'  => __( 'The image size in editor by default', 'usp-publication' ),
            'values' => $d_sizes,
            'notice' => __( 'Select image size for the visual editor during publishing', 'usp-publication' )
        ),
        array(
            'type'      => 'select',
            'slug'      => 'uspp_tab_public_form',
            'title'     => __( 'Form of publication output in the personal cabinet', 'usp-publication' ),
            'values'    => array( __( 'Do not display', 'usp-publication' ), __( 'Output', 'usp-publication' ) ),
            'default'   => 1,
            'childrens' => array(
                1 => array(
                    array(
                        'type'   => 'number',
                        'slug'   => 'form-lk',
                        'title'  => __( 'The form ID', 'usp-publication' ),
                        'notice' => __( 'Enter the form ID according to the personal Cabinet. The default is 1', 'usp-publication' )
                    )
                )
            )
        )
    ) );

    $options->box( 'publicpost' )->add_group( 'records', array(
        'title' => __( 'Publication of records', 'usp-publication' )
    ) )->add_options( array(
        array(
            'type'      => 'select',
            'slug'      => 'uspp_access_publicform',
            'title'     => __( 'Publication is allowed', 'usp-publication' ),
            'values'    => array(
                10 => __( 'only Administrators', 'usp-publication' ),
                7  => __( 'Editors and higher', 'usp-publication' ),
                2  => __( 'Authors and higher', 'usp-publication' ),
                0  => __( 'Guests and users', 'usp-publication' )
            ),
            'childrens' => array(
                array(
                    array(
                        'type'   => 'select',
                        'slug'   => 'guest_post_redirect',
                        'title'  => __( 'Redirect to', 'usp-publication' ),
                        'values' => $pages,
                        'notice' => __( 'Select the page to which the visitors will be redirected after a successful publication, if email authorization is included in the registration precess', 'usp-publication' )
                    )
                )
            )
        ),
        array(
            'type'      => 'select',
            'slug'      => 'uspp_send_to_moderation',
            'title'     => __( 'Moderation of publications', 'usp-publication' ),
            'values'    => array( __( 'Publish now', 'usp-publication' ), __( 'Send for moderation', 'usp-publication' ) ),
            'notice'    => __( 'If subject to moderation: To allow the user to see their publication before moderation has been completed, the user should be classifies as Author or higher', 'usp-publication' ),
            'childrens' => array(
                1 => array(
                    array(
                        'type'   => 'checkbox',
                        'slug'   => 'post_types_moderation',
                        'title'  => __( 'Type post', 'usp-publication' ),
                        'values' => $types,
                        'notice' => __( 'Select the types of posts that will be sent for moderation. If nothing is specified, then the moderation is valid for all types', 'usp-publication' )
                    )
                )
            )
        )
    ) );

    $options->box( 'publicpost' )->add_group( 'edit', array(
        'title' => __( 'Editing', 'usp-publication' )
    ) )->add_options( array(
        array(
            'type'   => 'checkbox',
            'slug'   => 'uspp_front_post_edit',
            'title'  => __( 'Frontend editing', 'usp-publication' ),
            'values' => array(
                10 => __( 'Administrators', 'usp-publication' ),
                7  => __( 'Editors', 'usp-publication' ),
                2  => __( 'Authors', 'usp-publication' )
            )
        ),
        array(
            'type'   => 'number',
            'slug'   => 'time_editing',
            'title'  => __( 'The time limit edit', 'usp-publication' ),
            'notice' => __( 'Limit editing time of publication in hours, by default: unlimited', 'usp-publication' )
        )
    ) );

    $options->box( 'publicpost' )->add_group( 'fields', array(
        'title' => __( 'Custom fields', 'usp-publication' )
    ) )->add_options( array(
        array(
            'type'      => 'select',
            'slug'      => 'uspp_custom_fields',
            'title'     => __( 'Automatic output', 'usp-publication' ),
            'values'    => array( __( 'No', 'usp-publication' ), __( 'Yes', 'usp-publication' ) ),
            'notice'    => __( 'Settings only for fields created using the form of the publication usp-publication', 'usp-publication' ),
            'childrens' => array(
                1 => array(
                    array(
                        'type'   => 'select',
                        'slug'   => 'uspp_cf_place',
                        'title'  => __( 'Output fields location', 'usp-publication' ),
                        'values' => array( __( 'Above publication content', 'usp-publication' ), __( 'On content recording', 'usp-publication' ) )
                    ),
                    array(
                        'type'   => 'checkbox',
                        'slug'   => 'pm_post_types',
                        'title'  => __( 'Types of posts for the output of custom fields', 'usp-publication' ),
                        'values' => $types,
                        'notice' => __( 'Select types of posts where the values of arbitrary fields will be displayed. If nothing is specified, it is displayed everywhere', 'usp-publication' )
                    )
                )
            )
        )
    ) );

    return $options;
}
