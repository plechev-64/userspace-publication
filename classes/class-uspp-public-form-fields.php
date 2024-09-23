<?php

class USPP_Public_Form_Fields extends FieldsManager {

	public $taxonomies;
	public $post_type = 'post';
	public $form_id = 1;

	function __construct( $post_type, $args = false ) {

		/* old support */
		if ( is_array( $post_type ) ) {
			$args      = $post_type;
			$post_type = $args['post_type'];
		}
		/**/

		$this->post_type  = $post_type;
		$this->form_id    = ( isset( $args['form_id'] ) && $args['form_id'] ) ? $args['form_id'] : 1;
		$this->taxonomies = get_object_taxonomies( $this->post_type, 'objects' );

		if ( 'post' == $this->post_type ) {
			unset( $this->taxonomies['post_format'] );
		}

		add_filter( 'usp_custom_fields', [ $this, 'fix_old_fields' ], 10, 2 );

		$this->setup_public_form_fields();

		add_filter( 'usp_field_options', [ $this, 'edit_field_options' ], 10, 3 );

		$customFields = $this->get_custom_fields();
		if ( $customFields ) {
			foreach ( $customFields as $field_id => $field ) {
				if ( isset( $field->value_in_key ) ) {
					continue;
				}

				$this->get_field( $field_id )->set_prop( 'value_in_key', true );
			}
		}
	}

	function setup_public_form_fields() {
		global $wpdb;

		$manager_id = $this->post_type . '_' . $this->form_id;

		parent::__construct( $manager_id, [
				'sortable'        => true,
				'structure_edit'  => true,
				'meta_delete'     => [
					$wpdb->postmeta => 'meta_key',
				],
				'default_fields'  => $this->get_default_public_form_fields(),
				'default_is_null' => true,
				'field_options'   => [
					[
						'slug'  => 'notice',
						'type'  => 'textarea',
						'title' => __( 'Field description', 'userspace-publication' ),
					],
					[
						'slug'   => 'required',
						'type'   => 'radio',
						'title'  => __( 'Required field', 'userspace-publication' ),
						'values' => [
							__( 'No', 'userspace-publication' ),
							__( 'Yes', 'userspace-publication' ),
						],
					],
				],
			]
		);

		$this->setup_default_fields();
	}

	function get_default_public_form_fields() {

		$fields = [
			[
				'slug'      => 'post_title',
				'maxlength' => 100,
				'title'     => __( 'Title', 'userspace-publication' ),
				'type'      => 'text',
			],
		];

		if ( $this->taxonomies ) {

			foreach ( $this->taxonomies as $taxonomy => $object ) {

				if ( $this->is_hierarchical_tax( $taxonomy ) ) {

					$label = $object->labels->name;

					if ( 'groups' == $taxonomy ) {
						$label = __( 'Group category', 'userspace-publication' );
					}

					$options = [];

					if ( 'groups' != $taxonomy ) {

						$options = [
							[
								'slug'   => 'number-select',
								'type'   => 'number',
								'title'  => __( 'Amount to choose', 'userspace-publication' ),
								'notice' => __( 'Only when output through select', 'userspace-publication' ),
							],
							[
								'slug'   => 'type-select',
								'type'   => 'select',
								'title'  => __( 'Output option', 'userspace-publication' ),
								'values' => [
									'select'      => __( 'Select', 'userspace-publication' ),
									'checkbox'    => __( 'Checkbox', 'userspace-publication' ),
									'multiselect' => __( 'Multiselect', 'userspace-publication' ),
								],
							],
							[
								'slug'   => 'only-child',
								'type'   => 'select',
								'title'  => __( 'Only child terms', 'userspace-publication' ),
								'notice' => __( 'Attach only the selected child terms to the post, ignoring parents', 'userspace-publication' ),
								'values' => [
									__( 'Disable', 'userspace-publication' ),
									__( 'Enable', 'userspace-publication' ),
								],
							],
						];
					}

					$fields[] = [
						'slug'    => 'taxonomy-' . $taxonomy,
						'title'   => $label,
						'type'    => 'select',
						'options' => $options,
					];
				}
			}
		}

		$fields[] = [
			'slug'      => 'post_excerpt',
			'maxlength' => 200,
			'title'     => __( 'Short entry', 'userspace-publication' ),
			'type'      => 'textarea',
		];

		$fields[] = [
			'slug'        => 'post_content',
			'title'       => __( 'Content of the publication', 'userspace-publication' ),
			'type'        => 'textarea',
			'post-editor' => [ 'html', 'editor' ],
			'required'    => 1,
			'options'     => [
				[
					'slug'    => 'post-editor',
					'type'    => 'checkbox',
					'title'   => __( 'Editor settings', 'userspace-publication' ),
					'default' => [ 'html', 'editor' ],
					'values'  => [
						'media'  => __( 'Media loader', 'userspace-publication' ),
						'html'   => __( 'HTML editor', 'userspace-publication' ),
						'editor' => __( 'Visual editor', 'userspace-publication' ),
					],
				],
			],
		];

		$fields[] = [
			'slug'       => 'post_uploader',
			'title'      => __( 'usp-publication media loader', 'userspace-publication' ),
			'type'       => 'uploader',
			'multiple'   => 1,
			'temp_media' => 1,
			'file_types' => 'png, gif, jpg',
			'fix_editor' => 'post_content',
			'options'    => [
				[
					'slug'    => 'fix_editor',
					'type'    => 'radio',
					'title'   => __( 'Adding of an image to the text editor', 'userspace-publication' ),
					'values'  => [
						__( 'Disabled', 'userspace-publication' ),
						'post_content' => __( 'Enabled', 'userspace-publication' ),
					],
					'default' => 'post_content',
				],
				[
					'slug'  => 'multiple',
					'type'  => 'hidden',
					'value' => 1,
				],
				[
					'slug'  => 'temp_media',
					'type'  => 'hidden',
					'value' => 1,
				],
				[
					'slug'    => 'gallery',
					'type'    => 'radio',
					'title'   => __( 'Offer an output of images in a gallery', 'userspace-publication' ),
					'values'  => [
						__( 'Disabled', 'userspace-publication' ),
						__( 'Enabled', 'userspace-publication' ),
					],
					'default' => 1,
				],
			],
		];

		if ( post_type_supports( $this->post_type, 'thumbnail' ) ) {

			$fields[] = [
				'slug'       => 'post_thumbnail',
				'title'      => __( 'Thumbnail of the publication', 'userspace-publication' ),
				'type'       => 'uploader',
				'temp_media' => 1,
				'file_types' => 'png, gif, jpg',
				'fix_editor' => 'post_content',
				'options'    => [
					[
						'slug'  => 'file_types',
						'type'  => 'hidden',
						'value' => 'png, gif, jpg',
					],
					[
						'slug'  => 'max_files',
						'type'  => 'hidden',
						'value' => 2,
					],
					[
						'slug'  => 'multiple',
						'type'  => 'hidden',
						'value' => 0,
					],
					[
						'slug'  => 'temp_media',
						'type'  => 'hidden',
						'value' => 1,
					],
					[
						'slug'    => 'fix_editor',
						'type'    => 'radio',
						'title'   => __( 'Adding of an image to the text editor', 'userspace-publication' ),
						'values'  => [
							__( 'Disabled', 'userspace-publication' ),
							'post_content' => __( 'Enabled', 'userspace-publication' ),
						],
						'default' => 'post_content',
					],
				],
			];
		}

		if ( $this->taxonomies ) {

			foreach ( $this->taxonomies as $taxonomy => $object ) {

				if ( ! $this->is_hierarchical_tax( $taxonomy ) ) {

					$label = $object->labels->name;

					$fields[] = [
						'slug'        => 'taxonomy-' . $taxonomy,
						'title'       => $label,
						'type'        => 'checkbox',
						'number-tags' => 20,
						'input-tags'  => 1,
						'options'     => [
							[
								'slug'  => 'number-tags',
								'type'  => 'number',
								'title' => __( 'Maximum output', 'userspace-publication' ),
							],
							[
								'slug'   => 'input-tags',
								'type'   => 'select',
								'title'  => __( 'New values entry field', 'userspace-publication' ),
								'values' => [
									__( 'Disable', 'userspace-publication' ),
									__( 'Enable', 'userspace-publication' ),
								],
							],
						],
					];
				}
			}
		}

		return apply_filters( 'uspp_default_public_form_fields', $fields, $this->post_type, $this );
	}

	function edit_field_options( $options, $field, $manager_id ) {

		if ( in_array( $field->id, [ 'post_content', 'post_uploader', 'post_thumbnail' ] ) ) {

			unset( $options['placeholder'] );
			unset( $options['maxlength'] );

			if ( 'post_uploader' == $field->id ) {
				unset( $options['required'] );
			}

			if ( 'post_thumbnail' == $field->id ) {
				unset( $options['required'] );
			}
		}

		if ( $this->is_taxonomy_field( $field->id ) ) {

			unset( $options['empty_first'] );

			if ( 'taxonomy-groups' == $field->id ) {

				unset( $options['required'] );
				unset( $options['values'] );
			} else if ( isset( $options['values'] ) ) {
				$options['values']['title'] = __( 'Specify term_ID to be selected', 'userspace-publication' );
			}
		}

		return $options;
	}

	function get_custom_fields() {

		if ( ! $this->fields ) {
			return false;
		}

		$defaultSlugs = $this->get_default_ids();

		$customFields = [];

		foreach ( $this->fields as $field_id => $field ) {

			if ( in_array( $field_id, $defaultSlugs ) ) {
				continue;
			}

			$customFields[ $field_id ] = $field;
		}

		return $customFields;
	}

	function is_taxonomy_field( $field_id ) {

		if ( ! $this->taxonomies ) {
			return false;
		}

		foreach ( $this->taxonomies as $tax_name => $object ) {
			if ( 'taxonomy-' . $tax_name == $field_id ) {
				return $tax_name;
			}
		}

		return false;
	}

	function is_hierarchical_tax( $taxonomy ) {

		if ( ! $this->taxonomies || ! isset( $this->taxonomies[ $taxonomy ] ) ) {
			return false;
		}

		if ( $this->taxonomies[ $taxonomy ]->hierarchical ) {
			return true;
		}

		return false;
	}

	function get_default_ids() {

		$default_fields = $this->get_default_fields();

		if ( ! $default_fields ) {
			return false;
		}

		$default = [
			'post_title',
			'post_content',
			'post_excerpt',
			'post_uploader',
			'post_thumbnail',
		];

		$ids = [];

		foreach ( $default_fields as $field_id => $field ) {

			if ( in_array( $field_id, $default ) || $this->is_taxonomy_field( $field_id ) ) {

				$ids[] = $field_id;
			}
		}

		return $ids;
	}

	function fix_old_fields( $fields ) {

		if ( ! $fields ) {
			return false;
		}

		foreach ( $fields as $k => $field ) {
			if ( isset( $field['slug'] ) && in_array( $field['slug'], [ 'post_uploader', 'post_thumbnail' ] ) && 'custom' == $field['type'] ) {
				$fields[ $k ]['type'] = 'uploader';
			}
		}

		return $fields;
	}

}
