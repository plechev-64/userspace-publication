<?php

RCL()->use_module( 'fields-manager' );

class Rcl_Public_Form_Fields extends Rcl_Fields_Manager {

    public $taxonomies;
    public $post_type = 'post';
    public $form_id   = 1;

    function __construct( $post_type, $args = false ) {

        /* old support */
        if ( is_array( $post_type ) ) {
            $args      = $post_type;
            $post_type = $args['post_type'];
        }
        /**/

        $this->post_type  = $post_type;
        $this->form_id    = (isset( $args['form_id'] ) && $args['form_id']) ? $args['form_id'] : 1;
        $this->taxonomies = get_object_taxonomies( $this->post_type, 'objects' );

        if ( $this->post_type == 'post' ) {
            unset( $this->taxonomies['post_format'] );
        }

        add_filter( 'usp_custom_fields', array( $this, 'fix_old_fields' ), 10, 2 );

        $this->setup_public_form_fields();

        add_filter( 'usp_field_options', array( $this, 'edit_field_options' ), 10, 3 );

        if ( $customFields = $this->get_custom_fields() ) {
            foreach ( $customFields as $field_id => $field ) {
                if ( isset( $field->value_in_key ) )
                    continue;

                $this->get_field( $field_id )->set_prop( 'value_in_key', true );
            }
        }
    }

    function setup_public_form_fields() {
        global $wpdb;

        $manager_id = $this->post_type . '_' . $this->form_id;

        parent::__construct( $manager_id, array(
            'sortable'        => true,
            'structure_edit'  => true,
            'meta_delete'     => array(
                $wpdb->postmeta => 'meta_key'
            ),
            'default_fields'  => $this->get_default_public_form_fields(),
            'default_is_null' => true,
            'field_options'   => array(
                array(
                    'slug'  => 'notice',
                    'type'  => 'textarea',
                    'title' => __( 'field description', 'usp-publication' )
                ),
                array(
                    'slug'   => 'required',
                    'type'   => 'radio',
                    'title'  => __( 'required field', 'usp-publication' ),
                    'values' => array(
                        __( 'No', 'usp-publication' ),
                        __( 'Yes', 'usp-publication' )
                    )
                )
            )
            )
        );

        $this->setup_default_fields();
    }

    function get_default_public_form_fields() {

        $fields = array(
            array(
                'slug'      => 'post_title',
                'maxlength' => 100,
                'title'     => __( 'Title', 'usp-publication' ),
                'type'      => 'text'
            )
        );

        if ( $this->taxonomies ) {

            foreach ( $this->taxonomies as $taxonomy => $object ) {

                if ( $this->is_hierarchical_tax( $taxonomy ) ) {

                    $label = $object->labels->name;

                    if ( $taxonomy == 'groups' )
                        $label = __( 'Group category', 'usp-publication' );

                    $options = array();

                    if ( $taxonomy != 'groups' ) {

                        $options = array(
                            array(
                                'slug'   => 'number-select',
                                'type'   => 'number',
                                'title'  => __( 'Amount to choose', 'usp-publication' ),
                                'notice' => __( 'only when output through select', 'usp-publication' )
                            ),
                            array(
                                'slug'   => 'type-select',
                                'type'   => 'select',
                                'title'  => __( 'Output option', 'usp-publication' ),
                                'values' => array(
                                    'select'      => __( 'Select', 'usp-publication' ),
                                    'checkbox'    => __( 'Checkbox', 'usp-publication' ),
                                    'multiselect' => __( 'Multiselect', 'usp-publication' )
                                )
                            ),
                            array(
                                'slug'   => 'only-child',
                                'type'   => 'select',
                                'title'  => __( 'Only child terms', 'usp-publication' ),
                                'notice' => __( 'Attach only the selected child terms to the post, ignoring parents', 'usp-publication' ),
                                'values' => array(
                                    __( 'Disable', 'usp-publication' ),
                                    __( 'Enable', 'usp-publication' )
                                )
                            )
                        );
                    }

                    $fields[] = array(
                        'slug'    => 'taxonomy-' . $taxonomy,
                        'title'   => $label,
                        'type'    => 'select',
                        'options' => $options
                    );
                }
            }
        }

        $fields[] = array(
            'slug'      => 'post_excerpt',
            'maxlength' => 200,
            'title'     => __( 'Short entry', 'usp-publication' ),
            'type'      => 'textarea'
        );

        $fields[] = array(
            'slug'        => 'post_content',
            'title'       => __( 'Content of the publication', 'usp-publication' ),
            'type'        => 'textarea',
            'post-editor' => array( 'html', 'editor' ),
            'required'    => 1,
            'options'     => array(
                array(
                    'slug'    => 'post-editor',
                    'type'    => 'checkbox',
                    'title'   => __( 'Editor settings', 'usp-publication' ),
                    'default' => array( 'html', 'editor' ),
                    'values'  => array(
                        'media'  => __( 'Media loader', 'usp-publication' ),
                        'html'   => __( 'HTML editor', 'usp-publication' ),
                        'editor' => __( 'Visual editor', 'usp-publication' )
                    )
                )
            )
        );

        $fields[] = array(
            'slug'       => 'post_uploader',
            'title'      => __( 'usp-publication media loader', 'usp-publication' ),
            'type'       => 'uploader',
            'multiple'   => 1,
            'temp_media' => 1,
            'file_types' => 'png, gif, jpg',
            'fix_editor' => 'post_content',
            'options'    => array(
                array(
                    'slug'    => 'fix_editor',
                    'type'    => 'radio',
                    'title'   => __( 'Adding of an image to the text editor', 'usp-publication' ),
                    'values'  => array(
                        __( 'Disabled', 'usp-publication' ),
                        'post_content' => __( 'Enabled', 'usp-publication' )
                    ),
                    'default' => 'post_content'
                ),
                array(
                    'slug'  => 'multiple',
                    'type'  => 'hidden',
                    'value' => 1
                ),
                array(
                    'slug'  => 'temp_media',
                    'type'  => 'hidden',
                    'value' => 1
                ),
                array(
                    'slug'    => 'gallery',
                    'type'    => 'radio',
                    'title'   => __( 'Offer an output of images in a gallery', 'usp-publication' ),
                    'values'  => array(
                        __( 'Disabled', 'usp-publication' ),
                        __( 'Enabled', 'usp-publication' )
                    ),
                    'default' => 1
                )
            )
        );

        if ( post_type_supports( $this->post_type, 'thumbnail' ) ) {

            $fields[] = array(
                'slug'       => 'post_thumbnail',
                'title'      => __( 'Thumbnail of the publication', 'usp-publication' ),
                'type'       => 'uploader',
                'temp_media' => 1,
                'file_types' => 'png, gif, jpg',
                'fix_editor' => 'post_content',
                'options'    => array(
                    array(
                        'slug'  => 'file_types',
                        'type'  => 'hidden',
                        'value' => 'png, gif, jpg'
                    ),
                    array(
                        'slug'  => 'max_files',
                        'type'  => 'hidden',
                        'value' => 2
                    ),
                    array(
                        'slug'  => 'multiple',
                        'type'  => 'hidden',
                        'value' => 0
                    ),
                    array(
                        'slug'  => 'temp_media',
                        'type'  => 'hidden',
                        'value' => 1
                    ),
                    array(
                        'slug'    => 'fix_editor',
                        'type'    => 'radio',
                        'title'   => __( 'Adding of an image to the text editor', 'usp-publication' ),
                        'values'  => array(
                            __( 'Disabled', 'usp-publication' ),
                            'post_content' => __( 'Enabled', 'usp-publication' )
                        ),
                        'default' => 'post_content'
                    )
                )
            );
        }

        if ( $this->taxonomies ) {

            foreach ( $this->taxonomies as $taxonomy => $object ) {

                if ( ! $this->is_hierarchical_tax( $taxonomy ) ) {

                    $label = $object->labels->name;

                    $fields[] = array(
                        'slug'        => 'taxonomy-' . $taxonomy,
                        'title'       => $label,
                        'type'        => 'checkbox',
                        'number-tags' => 20,
                        'input-tags'  => 1,
                        'options'     => array(
                            array(
                                'slug'  => 'number-tags',
                                'type'  => 'number',
                                'title' => __( 'Maximum output', 'usp-publication' )
                            ),
                            array(
                                'slug'   => 'input-tags',
                                'type'   => 'select',
                                'title'  => __( 'New values entry field', 'usp-publication' ),
                                'values' => array(
                                    __( 'Disable', 'usp-publication' ),
                                    __( 'Enable', 'usp-publication' )
                                )
                            )
                        )
                    );
                }
            }
        }

        $fields = apply_filters( 'uspp_default_public_form_fields', $fields, $this->post_type, $this );

        return $fields;
    }

    function edit_field_options( $options, $field, $manager_id ) {

        if ( in_array( $field->id, [ 'post_content', 'post_uploader', 'post_thumbnail' ] ) ) {

            unset( $options['placeholder'] );
            unset( $options['maxlength'] );

            if ( $field->id == 'post_uploader' ) {
                unset( $options['required'] );
            }

            if ( $field->id == 'post_thumbnail' ) {
                unset( $options['required'] );
            }
        }

        if ( $this->is_taxonomy_field( $field->id ) ) {

            unset( $options['empty_first'] );

            if ( $field->id == 'taxonomy-groups' ) {

                unset( $options['required'] );
                unset( $options['values'] );
            } else if ( isset( $options['values'] ) ) {
                $options['values']['title'] = __( 'Specify term_ID to be selected', 'usp-publication' );
            }
        }

        return $options;
    }

    function get_custom_fields() {

        if ( ! $this->fields )
            return false;

        $defaultSlugs = $this->get_default_ids();

        $customFields = array();

        foreach ( $this->fields as $field_id => $field ) {

            if ( in_array( $field_id, $defaultSlugs ) )
                continue;

            $customFields[$field_id] = $field;
        }

        return $customFields;
    }

    function is_taxonomy_field( $field_id ) {

        if ( ! $this->taxonomies )
            return false;

        foreach ( $this->taxonomies as $taxname => $object ) {

            if ( $field_id == 'taxonomy-' . $taxname )
                return $taxname;
        }

        return false;
    }

    function is_hierarchical_tax( $taxonomy ) {

        if ( ! $this->taxonomies || ! isset( $this->taxonomies[$taxonomy] ) )
            return false;

        if ( $this->taxonomies[$taxonomy]->hierarchical )
            return true;

        return false;
    }

    function get_default_ids() {

        $defaulFields = $this->get_default_fields();

        if ( ! $defaulFields )
            return false;

        $default = array(
            'post_title',
            'post_content',
            'post_excerpt',
            'post_uploader',
            'post_thumbnail'
        );

        $ids = array();

        foreach ( $defaulFields as $field_id => $field ) {

            if ( in_array( $field_id, $default ) || $this->is_taxonomy_field( $field_id ) ) {

                $ids[] = $field_id;
            }
        }

        return $ids;
    }

    function fix_old_fields( $fields ) {

        if ( ! $fields )
            return $fields;

        foreach ( $fields as $k => $field ) {
            if ( isset( $field['slug'] ) && in_array( $field['slug'], [ 'post_uploader', 'post_thumbnail' ] ) && $field['type'] == 'custom' ) {
                $fields[$k]['type'] = 'uploader';
            }
        }

        return $fields;
    }

}
