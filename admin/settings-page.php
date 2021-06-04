<?php

add_filter( 'usp_options', 'uspp_get_publics_options_page' );
function uspp_get_publics_options_page( $options ) {
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

    $types = array( 'post' => __( 'Posts', 'userspace-publication' ) );

    foreach ( $post_types as $post_type ) {
        $types[$post_type->name] = $post_type->label;
    }

    $pages = usp_get_pages_ids();

    $options->add_box( 'publicpost', array(
        'title' => __( 'Publication settings', 'userspace-publication' ),
        'icon'  => 'fa-edit'
    ) )->add_group( 'general', array(
        'title' => __( 'General settings', 'userspace-publication' )
    ) )->add_options( array(
        array(
            'type'   => 'select',
            'slug'   => 'uspp_public_form_page',
            'title'  => __( 'Publishing and editing', 'userspace-publication' ),
            'values' => $pages,
            'notice' => __( 'You are required to publish a links to managing publications, you must specify the page with the shortcode [uspp-public-form]', 'userspace-publication' )
        ),
        array(
            'type'      => 'switch',
            'slug'      => 'uspp_author_box',
            'title'     => __( 'Display information about the author', 'userspace-publication' ),
            'text'      => [
                'off' => __( 'No', 'userspace-publication' ),
                'on'  => __( 'Yes', 'userspace-publication' )
            ],
            'default'   => 1,
            'childrens' => array(
                1 => array(
                    array(
                        'type'   => 'checkbox',
                        'slug'   => 'uspp_author_box_post_types',
                        'title'  => __( 'Types of write for the author`s block output', 'userspace-publication' ),
                        'values' => $types,
                        'notice' => __( 'Select the types of writes where the author`s block should be displayed. If nothing is specified, it is displayed everywhere', 'userspace-publication' )
                    )
                )
            )
        ),
        array(
            'type'      => 'switch',
            'slug'      => 'uspp_show_list_of_publications',
            'title'     => __( 'Show list of publications tab', 'userspace-publication' ),
            'text'      => [
                'off' => __( 'No', 'userspace-publication' ),
                'on'  => __( 'Yes', 'userspace-publication' )
            ],
            'default'   => 1,
            'childrens' => array(
                1 => array(
                    array(
                        'type'   => 'checkbox',
                        'slug'   => 'uspp_post_types_list',
                        'title'  => __( 'Type of post for output a list of writes', 'userspace-publication' ),
                        'values' => $types,
                        'notice' => __( 'Select the type of post which will be to output its archive of writes in this tab. If nothing is specified, it will be outputed a writes all types', 'userspace-publication' )
                    ),
                    array(
                        'type'    => 'radio',
                        'slug'    => 'uspp_tab_list_of_publications',
                        'title'   => __( 'List of publications of the user', 'userspace-publication' ),
                        'values'  => array( __( 'Only owner of the account', 'userspace-publication' ), __( 'Show everyone including guests', 'userspace-publication' ) ),
                        'default' => 1,
                    )
                )
            )
        )
    ) );

    $options->box( 'publicpost' )->add_group( 'form', array(
        'title' => __( 'Form of publication', 'userspace-publication' )
    ) )->add_options( array(
        array(
            'type'   => 'select',
            'slug'   => 'uspp_default_thumb',
            'title'  => __( 'The image size in editor by default', 'userspace-publication' ),
            'values' => $d_sizes,
            'notice' => __( 'Select image size for the visual editor during publishing', 'userspace-publication' )
        ),
        array(
            'type'    => 'switch',
            'slug'    => 'uspp_public_preview',
            'title'   => __( 'Show preview button', 'userspace-publication' ),
            'text'    => [
                'off' => __( 'No', 'userspace-publication' ),
                'on'  => __( 'Yes', 'userspace-publication' )
            ],
            'default' => 1,
        ),
        array(
            'type'    => 'switch',
            'slug'    => 'uspp_public_draft',
            'title'   => __( 'Show draft button', 'userspace-publication' ),
            'text'    => [
                'off' => __( 'No', 'userspace-publication' ),
                'on'  => __( 'Yes', 'userspace-publication' )
            ],
            'default' => 1,
        ),
        array(
            'type'      => 'switch',
            'slug'      => 'uspp_tab_public_form',
            'title'     => __( 'Show form of publication in the personal cabinet', 'userspace-publication' ),
            'text'      => [
                'off' => __( 'No', 'userspace-publication' ),
                'on'  => __( 'Yes', 'userspace-publication' )
            ],
            'default'   => 0,
            'childrens' => array(
                1 => array(
                    array(
                        'type'   => 'number',
                        'slug'   => 'uspp_id_public_form',
                        'title'  => __( 'The form ID', 'userspace-publication' ),
                        'notice' => __( 'Enter the form ID according to the personal Cabinet. The default is 1', 'userspace-publication' )
                    )
                )
            )
        )
    ) );

    $options->box( 'publicpost' )->add_group( 'records', array(
        'title' => __( 'Publication of records', 'userspace-publication' )
    ) )->add_options( array(
        array(
            'type'      => 'select',
            'slug'      => 'uspp_access_publicform',
            'title'     => __( 'Publication is allowed', 'userspace-publication' ),
            'values'    => array(
                10 => __( 'Only Administrators', 'userspace-publication' ),
                7  => __( 'Editors and higher', 'userspace-publication' ),
                2  => __( 'Authors and higher', 'userspace-publication' ),
                0  => __( 'Guests and users', 'userspace-publication' )
            ),
            'childrens' => array(
                array(
                    array(
                        'type'   => 'select',
                        'slug'   => 'uspp_guest_redirect',
                        'title'  => __( 'Redirect to', 'userspace-publication' ),
                        'values' => $pages,
                        'notice' => __( 'Select the page to which the visitors will be redirected after a successful publication, if email authorization is included in the registration precess', 'userspace-publication' )
                    )
                )
            )
        ),
        array(
            'type'      => 'radio',
            'slug'      => 'uspp_send_to_moderation',
            'title'     => __( 'Moderation of publications', 'userspace-publication' ),
            'values'    => array( __( 'Publish now', 'userspace-publication' ), __( 'Send for moderation', 'userspace-publication' ) ),
            'default'   => 0,
            'notice'    => __( 'If subject to moderation: To allow the user to see their publication before moderation has been completed, the user should be classifies as Author or higher', 'userspace-publication' ),
            'childrens' => array(
                1 => array(
                    array(
                        'type'   => 'checkbox',
                        'slug'   => 'uspp_post_types_moderation',
                        'title'  => __( 'Type post', 'userspace-publication' ),
                        'values' => $types,
                        'notice' => __( 'Select the types of posts that will be sent for moderation. If nothing is specified, then the moderation is valid for all types', 'userspace-publication' )
                    )
                )
            )
        )
    ) );

    $options->box( 'publicpost' )->add_group( 'edit', array(
        'title' => __( 'Editing', 'userspace-publication' )
    ) )->add_options( array(
        array(
            'type'   => 'checkbox',
            'slug'   => 'uspp_front_post_edit',
            'title'  => __( 'Frontend editing', 'userspace-publication' ),
            'values' => array(
                10 => __( 'Administrators', 'userspace-publication' ),
                7  => __( 'Editors', 'userspace-publication' ),
                2  => __( 'Authors', 'userspace-publication' )
            )
        ),
        array(
            'type'   => 'number',
            'slug'   => 'uspp_time_editing',
            'title'  => __( 'The time limit edit', 'userspace-publication' ),
            'notice' => __( 'Limit editing time of publication in hours, by default: unlimited', 'userspace-publication' )
        )
    ) );

    $options->box( 'publicpost' )->add_group( 'fields', array(
        'title' => __( 'Custom fields', 'userspace-publication' )
    ) )->add_options( array(
        array(
            'type'      => 'switch',
            'slug'      => 'uspp_custom_fields',
            'title'     => __( 'Automatic output', 'userspace-publication' ),
            'text'      => [
                'off' => __( 'No', 'userspace-publication' ),
                'on'  => __( 'Yes', 'userspace-publication' )
            ],
            'default'   => 1,
            'notice'    => __( 'Settings only for fields created using the form of the publication usp-publication', 'userspace-publication' ),
            'childrens' => array(
                1 => array(
                    array(
                        'type'    => 'radio',
                        'slug'    => 'uspp_cf_place',
                        'title'   => __( 'Output fields location', 'userspace-publication' ),
                        'values'  => array( __( 'Before content', 'userspace-publication' ), __( 'After content', 'userspace-publication' ) ),
                        'default' => 1,
                    ),
                    array(
                        'type'   => 'checkbox',
                        'slug'   => 'uspp_cf_post_types',
                        'title'  => __( 'Types of posts for the output of custom fields', 'userspace-publication' ),
                        'values' => $types,
                        'notice' => __( 'Select types of posts where the values of arbitrary fields will be displayed. If nothing is specified, it is displayed everywhere', 'userspace-publication' )
                    )
                )
            )
        )
    ) );

    return $options;
}
