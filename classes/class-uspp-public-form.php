<?php

class USPP_Public_Form extends USPP_Public_Form_Fields {

	public $post_id = 0;
	public $post_type = 'post';
	public $fields_options;
	public $form_object;
	public $post;
	public $form_id;
	public $current_field = [];
	public $options = [
		'preview' => 1,
		'draft'   => 1,
		'delete'  => 1,
	];
	public $user_can = [
		'upload'  => false,
		'publish' => false,
		'delete'  => false,
		'draft'   => false,
		'edit'    => false,
	];
	public $core_fields = [
		'post_content',
		'post_title',
		'post_uploader',
		'post_excerpt',
		'post_thumbnail',
	];
	public $tax_fields = [];

	function __construct( $args = false ) {
		global $user_ID;

		USP()->use_module( 'forms' );

		$this->init_properties( $args );

		uspp_publication_scripts();
		uspp_publicform_style();

		if ( isset( $_GET['uspp-post-edit'] ) ) {
			$this->post_id = intval( $_GET['uspp-post-edit'] );
		}

		if ( $this->post_id ) {

			$this->post      = get_post( $this->post_id );
			$this->post_type = $this->post->post_type;
			$this->form_id   = get_post_meta( $this->post_id, 'publicform-id', 1 );
		}

		if ( ! $this->form_id ) {
			$this->form_id = 1;
		}

		$this->setup_user_can();

		if ( $this->user_can['publish'] && ! $user_ID ) {
			add_filter( 'uspp_public_form_fields', [ $this, 'add_guest_fields' ], 10 );
		}

		add_filter( 'usp_custom_fields', [ $this, 'init_public_form_fields_filter' ], 10 );

		parent::__construct( $this->post_type, [
			'form_id' => $this->form_id,
		] );

		$this->init_options();

		do_action( 'uspp_public_form_init', $this->get_object_form() );

		if ( $this->options['preview'] ) {
			usp_dialog_scripts();
		}

		if ( $this->user_can['upload'] ) {
			add_action( 'wp_footer', [ $this, 'init_form_scripts' ], 100 );
		}

		$this->form_object = $this->get_object_form();

		do_action( 'uspp_pre_get_public_form', $this );
	}

	function init_public_form_fields_filter( $fields ) {
		return apply_filters( 'uspp_public_form_fields', $fields, $this->get_object_form(), $this );
	}

	function init_properties( $args ) {
		$properties = get_class_vars( get_class( $this ) );

		foreach ( $properties as $name => $val ) {
			if ( isset( $args[ $name ] ) ) {
				$this->$name = $args[ $name ];
			}
		}
	}

	function get_object_form() {

		$dataForm = [];

		$dataForm['post_id']      = $this->post_id;
		$dataForm['post_type']    = $this->post_type;
		$dataForm['post_status']  = ( $this->post_id ) ? $this->post->post_status : 'new';
		$dataForm['post_content'] = ( $this->post_id ) ? $this->post->post_content : '';
		$dataForm['post_excerpt'] = ( $this->post_id ) ? $this->post->post_excerpt : '';
		$dataForm['post_title']   = ( $this->post_id ) ? $this->post->post_title : '';

		return ( object ) $dataForm;
	}

	function add_guest_fields( $fields ) {

		$guestFields = [
			[
				'slug'     => 'user_login',
				'title'    => __( 'Your Name', 'userspace-publication' ),
				'required' => 1,
				'type'     => 'text',
			],
			[
				'slug'     => 'user_email',
				'title'    => __( 'Your E-mail', 'userspace-publication' ),
				'required' => 1,
				'type'     => 'email',
			],
		];

		return array_merge( $guestFields, $fields );
	}

	function init_options() {

		$this->options['preview'] = usp_get_option( 'uspp_public_preview' );
		$this->options['draft']   = usp_get_option( 'uspp_public_draft' );

		$this->options = apply_filters( 'uspp_public_form_options', $this->options, $this->get_object_form() );
	}

	function setup_user_can() {
		global $user_ID;

		$this->user_can['publish'] = true;

		$user_can = usp_get_option( 'uspp_access_publicform', 2 );

		if ( $user_can ) {

			if ( $user_ID ) {

				$userinfo = get_userdata( $user_ID );

				if ( $userinfo->user_level >= $user_can ) {
					$this->user_can['publish'] = true;
				} else {
					$this->user_can['publish'] = false;
				}
			} else {

				$this->user_can['publish'] = false;
			}
		}

		$this->user_can['draft'] = $user_ID ? true : false;

		$this->user_can['upload'] = $this->user_can['publish'];

		if ( $user_ID && $this->post_id ) {

			$this->user_can['edit'] = current_user_can( 'edit_post', $this->post_id );

			if ( ! $this->user_can['edit'] && 'post-group' == $this->post_type ) {

				$this->user_can['edit'] = ( uspg_can_user_edit_post_group( $this->post_id ) ) ? true : false;
			}

			$this->user_can['delete'] = $this->user_can['edit'];
		}

		$this->user_can = apply_filters( 'uspp_public_form_user_can', $this->user_can, $this->get_object_form() );
	}

	function get_errors() {
		global $user_ID;

		$errors = [];

		if ( ! $this->user_can['publish'] ) {

			if ( ! $user_ID ) {
				$errors[] = __( 'You must be logged in to post. Login or register', 'userspace-publication' );
			} else if ( 'post-group' == $this->post_type ) {
				$errors[] = __( 'Sorry, but you have no rights to publish in this group :(', 'userspace-publication' );
			} else {
				$errors[] = __( 'Sorry, but you have no right to post on this site :(', 'userspace-publication' );
			}
		} else if ( $this->post_id && ! $this->user_can['edit'] ) {
			$errors[] = __( 'You can not edit this publication :(', 'userspace-publication' );
		}

		return apply_filters( 'uspp_public_form_errors', $errors, $this );
	}

	function get_errors_content() {

		$errorContent = '';

		foreach ( $this->get_errors() as $error ) {
			$errorContent .= usp_get_notice( [
				'type' => 'error',
				'text' => $error,
			] );
		}

		return $errorContent;
	}

	function get_form( $args = [] ) {
		$content = '';

		if ( $this->get_errors() ) {
			return $this->get_errors_content();
		}

		if ( isset( $_GET['draft'] ) && 'saved' == $_GET['draft'] ) {
			$content .= usp_get_notice( [
				'type' => 'success',
				'text' => __( 'The draft has been saved successfully!', 'userspace-publication' ),
			] );
		}

		$dataPost = $this->get_object_form();

		if ( $this->taxonomies ) {

			foreach ( $this->taxonomies as $tax_name => $object ) {

				$this->tax_fields[] = 'taxonomy-' . $tax_name;
			}
		}

		$attrs = [
			'data-form_id'   => $this->form_id,
			'data-post_id'   => $this->post_id,
			'data-post_type' => $this->post_type,
			'class'          => [ 'uspp-public-form' ],
		];

		$attrs = apply_filters( 'uspp_public_form_attributes', $attrs, $dataPost );

		$attrsForm = [];
		foreach ( $attrs as $k => $v ) {
			if ( is_array( $v ) ) {
				$attrsForm[] = $k . '="' . implode( ' ', $v ) . '"';
				continue;
			}
			$attrsForm[] = $k . '="' . $v . '"';
		}

		$content .= '<div class="uspp-public-box usp-form usps__relative">';

		$buttons = [];

		if ( usp_user_is_access_console() ) {

			$buttons[] = [
				'href'  => admin_url( 'admin.php?page=manage-public-form&post-type=' . $this->post_type . '&form-id=' . $this->form_id ),
				'label' => __( 'Edit this form', 'userspace-publication' ),
				'icon'  => 'fa-list',
				'type'  => 'clear',
				'class' => 'uspp-bttn__edit-form',
			];
		}

		$buttons = apply_filters( 'uspp_public_form_top_manager_args', $buttons, $this );

		if ( $buttons ) {

			$content .= '<div id="uspp-public-form-top-manager" class="usps usps__jc-end">';

			foreach ( $buttons as $button ) {
				$content .= usp_get_button( $button );
			}

			$content .= '</div>';
		}

		$content .= '<form action="" method="post" ' . implode( ' ', $attrsForm ) . '>';

		if ( $this->fields ) {

			$content .= $this->get_content_form();
		}

		$content .= apply_filters( 'uspp_public_form', '', $this->get_object_form() );

		$content .= $this->get_primary_buttons();

		if ( $this->form_id ) {
			$content .= '<input type="hidden" name="form_id" value="' . $this->form_id . '">';
		}

		$content .= '<input type="hidden" name="post_id" value="' . $this->post_id . '">';
		$content .= '<input type="hidden" name="post_type" value="' . $this->post_type . '">';
		$content .= '<input type="hidden" name="uspp-edit-post" value="1">';
		$content .= wp_nonce_field( 'uspp-edit-post', '_wpnonce', true, false );
		$content .= '</form>';

		if ( $this->user_can['delete'] && $this->options['delete'] ) {

			$content .= '<div id="uspp-form-delete-post" class="usp-field">';

			$content .= $this->get_delete_box();

			$content .= '</div>';
		}

		$content .= apply_filters( 'uspp_after_public_form', '', $this->get_object_form() );

		$content .= '</div>';

		return $content;
	}

	function get_primary_buttons() {

		$buttons = [];

		if ( $this->post_id ) {
			$buttons['gotopost'] = [
				'href'  => ( 'publish' != $this->post->post_status ) ? get_bloginfo( 'wpurl' ) . '/?p=' . $this->post_id . '&preview=true' : get_permalink( $this->post_id ),
				'label' => __( 'Go to the post', 'userspace-publication' ),
				'attrs' => [
					'target' => '_blank',
				],
				'id'    => 'uspp-view-post',
				'icon'  => 'fa-share-square',
			];
		}

		if ( $this->options['draft'] && $this->user_can['draft'] ) {
			$buttons['draft'] = [
				'onclick' => 'uspp_save_draft(this); return false;',
				'label'   => __( 'Save as Draft', 'userspace-publication' ),
				'id'      => 'uspp-draft-post',
				'icon'    => 'fa-shield',
			];
		}

		if ( $this->options['preview'] ) {
			$buttons['preview'] = [
				'onclick' => 'uspp_preview(this); return false;',
				'label'   => __( 'Preview', 'userspace-publication' ),
				'id'      => 'uspp-preview-post',
				'icon'    => 'fa-eye',
			];
		}

		$buttons['publish'] = [
			'onclick' => 'uspp_publish(this); return false;',
			'label'   => __( 'Publish', 'userspace-publication' ),
			'id'      => 'uspp-publish-post',
			'icon'    => 'fa-print',
		];

		$buttons = apply_filters( 'uspp_public_form_primary_buttons', $buttons, $this->get_object_form(), $this );

		if ( ! $buttons ) {
			return false;
		}

		$content = '<div class="usp-field uspp-submit-public-form">';

		foreach ( $buttons as $button ) {
			$content .= usp_get_button( $button );
		}

		$content .= '</div>';

		return $content;
	}

	function get_field_form( $field_id, $args = false ) {

		$dataPost = $this->get_object_form();

		$field = $this->get_field( $field_id );

		if ( ! $field ) {
			return false;
		}

		$this->current_field = $field;

		$contentField = false;

		if ( $this->taxonomies && in_array( $field_id, $this->tax_fields ) ) {
			$taxonomy = $this->is_taxonomy_field( $field_id );

			if ( $taxonomy ) {
				$contentField = $this->get_terms_list( $taxonomy, $field_id );
			}
		} else {

			if ( in_array( $field_id, $this->core_fields ) ) {

				if ( 'post_content' == $field_id ) {
					$contentField = $this->get_editor( [
						'post_content' => $dataPost->post_content,
						'options'      => $field->get_prop( 'post-editor' ),
					] );

					$contentField .= $field->get_notice();
				} else if ( 'post_excerpt' == $field_id ) {

					$field->set_prop( 'value', $dataPost->post_excerpt );

					$contentField = $field->get_field_input();
				} else if ( 'post_title' == $field_id ) {

					$field->set_prop( 'value', esc_textarea( $dataPost->post_title ) );

					$contentField = $field->get_field_input( esc_textarea( $dataPost->post_title ) );
				} else if ( 'uploader' == $field->type ) {

					if ( 'post_thumbnail' == $field_id ) {

						$field->set_prop( 'uploader_props', [
							'post_parent' => $this->post_id,
							'form_id'     => intval( $this->form_id ),
							'post_type'   => $this->post_type,
							'multiple'    => 0,
							'crop'        => 1,
						] );

						$uploader = $field->get_uploader();

						if ( $this->post_id ) {

							$thumbnail_id = get_post_meta( $this->post_id, '_thumbnail_id', 1 );
						} else {
							$session_id = ! empty( $_COOKIE['PHPSESSID'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['PHPSESSID'] ) ) : '';

							$thumbnail_id = ( new TempMediaQuery() )
								->select( [ 'media_id' ] )
								->where( [
									'user_id'         => $uploader->user_id ? $uploader->user_id : 0,
									'session_id'      => $uploader->user_id ? '' : $session_id,
									'uploader_id__in' => [ 'post_thumbnail' ],
								] )
								->limit( 1 )
								->get_var();
						}

						if ( $thumbnail_id ) {
							$field->set_prop( 'value', $thumbnail_id );
						}

						$contentField = $field->get_field_input();
					}

					if ( 'post_uploader' == $field_id ) {

						$field->set_prop( 'uploader_props', [
							'post_parent' => $this->post_id,
							'form_id'     => intval( $this->form_id ),
							'post_type'   => $this->post_type,
						] );

						$uploader = $field->get_uploader();

						if ( $this->post_id ) {
							$imagIds = ( new PostsQuery() )->select( [ 'ID' ] )->where( [
								'post_parent' => $this->post_id,
								'post_type'   => 'attachment',
							] )->limit( - 1 )->order( 'ASC' )->get_col();
						} else {
							$session_id = ! empty( $_COOKIE['PHPSESSID'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['PHPSESSID'] ) ) : '';

							$imagIds = ( new TempMediaQuery() )
								->select( [ 'media_id' ] )
								->where( [
									'user_id'         => $uploader->user_id ? $uploader->user_id : 0,
									'session_id'      => $uploader->user_id ? '' : $session_id,
									'uploader_id__in' => [ 'post_uploader', 'post_thumbnail' ],
								] )
								->limit( - 1 )->order( 'ASC' )->get_col();
						}

						$contentField = $uploader->get_gallery( $imagIds );

						$contentField .= $uploader->get_uploader();

						$contentField .= $field->get_notice();
					}
				}
			} else {

				if ( ! isset( $field->value ) ) {
					$field->set_prop( 'value', ( $this->post_id ) ? get_post_meta( $this->post_id, $field_id, 1 ) : null );
				}

				$contentField = $field->get_field_input();
			}
		}

		if ( ! $contentField ) {
			return false;
		}

		$content = '<div id="form-field-' . $field_id . '" class="usp-field field-' . $field_id . '">';

		$content .= $field->get_title();

		$content .= $contentField;

		$content .= '</div>';

		return $content;
	}

	function get_terms_list( $taxonomy, $field_id ) {

		$field = $this->get_field( $field_id );

		$content = '<div class="uspp-terms-select taxonomy-' . $taxonomy . '">';

		$terms = $field->isset_prop( 'values' ) ? $field->get_prop( 'values' ) : [];

		if ( $this->is_hierarchical_tax( $taxonomy ) ) {

			if ( 'post-group' == $this->post_type ) {

				global $uspg_group;

				$group_id = false;
				if ( isset( $uspg_group->term_id ) && $uspg_group->term_id ) {
					$group_id = $uspg_group->term_id;
				} else if ( $this->post_id ) {
					$group_id = uspg_get_group_id_by_post( $this->post_id );
				}

				$options_gr = uspg_get_options_group( $group_id );

				$termList = uspg_get_tags_list_group( $options_gr['tags'], $this->post_id );

				if ( ! $termList ) {
					return false;
				}

				$content .= $termList;
			} else {
				$type_select = $field->get_prop( 'type-select' );
				$type        = ( $type_select ) ? $type_select : 'select';

				$number_select = $field->get_prop( 'number-select' );
				$number        = ( $number_select ) ? $number_select : 1;

				$termList   = new USPP_List_Terms( $taxonomy, $type, $field->get_prop( 'required' ) );
				$post_terms = $this->get_post_terms( $taxonomy );

				$content .= $termList->get_select_list( $this->get_allterms( $taxonomy ), $post_terms, $number, $terms );
			}
		} else {

			$content .= $this->tags_field( $taxonomy, $terms );
		}

		$content .= $field->get_notice();

		$content .= '</div>';

		return $content;
	}

	function get_editor( $args = false ) {

		$wp_uploader = false;
		$quicktags   = false;
		$tinymce     = false;

		if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {

			if ( in_array( 'media', $args['options'] ) ) {
				$wp_uploader = true;
			}

			if ( in_array( 'html', $args['options'] ) ) {
				$quicktags = true;
			}

			if ( in_array( 'editor', $args['options'] ) ) {
				$tinymce = true;
			}
		}

		$data = [
			'wpautop'       => 1,
			'media_buttons' => $wp_uploader,
			'textarea_name' => 'post_content',
			'textarea_rows' => 10,
			'tabindex'      => null,
			'editor_css'    => '',
			'editor_class'  => 'autosave',
			'teeny'         => 0,
			'dfw'           => 0,
			'tinymce'       => $tinymce,
			'quicktags'     => $quicktags,
		];

		$post_content = ( isset( $args['post_content'] ) ) ? $args['post_content'] : false;

		ob_start();

		wp_editor( $post_content, 'post_content', $data );

		$content = ob_get_contents();

		ob_end_clean();

		return $content;
	}

	function get_tags_checklist( $taxonomy, $t_args = [] ) {

		if ( ! is_array( $t_args ) || false === $t_args ) {
			return false;
		}

		$values         = [];
		$checked_values = [];
		$post_tags      = ( $this->post_id ) ? $this->get_tags( $this->post_id, $taxonomy ) : [];

		$tags = get_terms( $taxonomy, $t_args );
		if ( 0 != $t_args['number'] && $tags ) {

			foreach ( $tags as $tag ) {

				$checked = false;

				if ( isset( $post_tags[ $tag->slug ] ) && $tag->name == $post_tags[ $tag->slug ]->name ) {
					$checked = true;
					unset( $post_tags[ $tag->slug ] );
				}

				if ( $checked ) {
					$checked_values[] = $tag->name;
				}

				$values[ $tag->name ] = $tag->name;
			}
		}

		if ( $post_tags ) {

			foreach ( $post_tags as $tag ) {

				$checked_values[] = $tag->name;

				$values[ $tag->name ] = $tag->name;
			}
		}

		if ( ! $values ) {
			return false;
		}

		return Field::setup( [
			'type'       => 'checkbox',
			'slug'       => $taxonomy . '-tags',
			'input_name' => 'tags[' . $taxonomy . ']',
			'required'   => $this->current_field->get_prop( 'required' ),
			'values'     => $values,
			'value'      => $checked_values,
		] )->get_field_input();
	}

	function get_tags( $post_id, $taxonomy = 'post_tag' ) {

		$post_tags = get_the_terms( $post_id, $taxonomy );

		$tags = [];
		if ( $post_tags ) {
			foreach ( $post_tags as $tag ) {
				$tags[ $tag->slug ] = $tag;
			}
		}

		return $tags;
	}

	function tags_field( $taxonomy, $terms ) {

		if ( ! $this->taxonomies || ! isset( $this->taxonomies[ $taxonomy ] ) ) {
			return false;
		}

		$args = [
			'input_field' => $this->current_field->get_prop( 'input-tags' ),
			'terms_cloud' => [
				'hide_empty' => false,
				'number'     => $this->current_field->get_prop( 'number-tags' ),
				'orderby'    => 'count',
				'order'      => 'DESC',
				'include'    => $terms,
			],
		];

		$args = apply_filters( 'uspp_public_form_tags', $args, $taxonomy, $this->get_object_form() );

		$content = $this->get_tags_checklist( $taxonomy, $args['terms_cloud'] );

		if ( $args['input_field'] ) {
			$content .= $this->get_tags_input( $taxonomy );
		}

		if ( ! $content ) {
			return false;
		}

		return '<div class="uspp-tags-list">' . $content . '</div>';
	}

	function get_tags_input( $taxonomy = 'post_tag' ) {

		usp_autocomplete_scripts();

		$args = [
			'type'        => 'text',
			'id'          => 'uspp-tags-' . $taxonomy,
			'name'        => 'tags[' . $taxonomy . ']',
			'placeholder' => $this->taxonomies[ $taxonomy ]->labels->new_item_name,
			'label'       => '<span>' . $this->taxonomies[ $taxonomy ]->labels->add_new_item . '</span><br><small>' . $this->taxonomies[ $taxonomy ]->labels->name . ' ' . __( 'It separates by push of Enter button', 'userspace-publication' ) . '</small>',
		];

		$fields = uspp_form_field( $args );

		$fields .= "<script>
		jQuery(window).on('load', function(){
			jQuery('#uspp-tags-" . $taxonomy . "').magicSuggest({
				data: USP.ajaxurl,
				dataUrlParams: { action: 'uspp_get_like_tags', taxonomy: '" . $taxonomy . "', ajax_nonce:USP.nonce },
				noSuggestionText: '" . __( "Not found", "usp-publication" ) . "',
				ajaxConfig: {
					  xhrFields: {
						withCredentials: true
					  }
				}
			});
		});
		</script>";

		return $fields;
	}

	function get_allterms( $taxonomy ) {

		$args = [
			'number'       => 0,
			'offset'       => 0,
			'orderby'      => 'id',
			'order'        => 'ASC',
			'hide_empty'   => false,
			'fields'       => 'all',
			'slug'         => '',
			'hierarchical' => true,
			'name__like'   => '',
			'pad_counts'   => false,
			'get'          => '',
			'child_of'     => 0,
			'parent'       => '',
		];

		$args = apply_filters( 'uspp_public_form_hierarchical_terms', $args, $taxonomy, $this->get_object_form() );

		return get_terms( $taxonomy, $args );
	}

	function get_post_terms( $taxonomy ) {

		if ( ! isset( $this->taxonomies[ $taxonomy ] ) ) {
			return false;
		}

		$post_terms = get_the_terms( $this->post_id, $taxonomy );

		if ( $post_terms ) {

			foreach ( $post_terms as $key => $term ) {

				foreach ( $post_terms as $t ) {

					if ( $t->parent == $term->term_id ) {
						unset( $post_terms[ $key ] );
						break;
					}
				}
			}
		}

		return $post_terms;
	}

	function get_delete_box() {
		global $user_ID;

		if ( usp_user_has_role( $user_ID, [ 'administrator', 'editor' ] ) ) {
			$content = usp_get_button( [
				'label'      => __( 'Options delete post', 'userspace-publication' ),
				'class'      => [ 'public-form-button uspp-delete-toggle' ],
				'icon'       => 'fa-angle-down',
				'icon_align' => 'right',
			] );
			$content .= '<div class="uspp-delete-form">';
			$content .= '<form action="" method="post"  onsubmit="return confirm(\'' . __( 'Are you sure?', 'userspace-publication' ) . '\');">';
			$content .= wp_nonce_field( 'uspp-delete-post', '_wpnonce', true, false );
			$content .= $this->get_reasons_list();
			$content .= '<div class="uspp-delete-text">' . __( 'or enter your own', 'userspace-publication' ) . '</div>';
			$content .= '<textarea required id="reason_content" name="reason_content"></textarea>';
			$content .= '<span id="uspp-without-notify" class="usp-checkbox-box checkbox-display-inline usps__inline usps__relative">'
			            . '<input type="checkbox" id="uspp-delete-silence" class="checkbox-field" name="no-reason" onclick="(!document.getElementById(\'reason_content\').getAttribute(\'disabled\')) ? document.getElementById(\'reason_content\').setAttribute(\'disabled\', \'disabled\') : document.getElementById(\'reason_content\').removeAttribute(\'disabled\')" value="1"> '
			            . '<label class="usp-label usps usps__ai-center usps__no-select" for="uspp-delete-silence">' . __( 'Without notice', 'userspace-publication' ) . '</label>'
			            . '</span>';
			$content .= usp_get_button( [
				'submit' => true,
				'label'  => __( 'Delete post', 'userspace-publication' ),
				'icon'   => 'fa-trash',
				'class'  => 'uspp-bttn-delete-post',
			] );
			$content .= '<input type="hidden" name="uspp-delete-post" value="1">';
			$content .= '<input type="hidden" name="post_id" value="' . $this->post_id . '">';
			$content .= '</form>';
			$content .= '</div>';
		} else {

			$content = '<form method="post" action="" onsubmit="return confirm(\'' . __( 'Are you sure?', 'userspace-publication' ) . '\');">';
			$content .= wp_nonce_field( 'uspp-delete-post', '_wpnonce', true, false );
			$content .= usp_get_button( [
				'submit' => true,
				'label'  => __( 'Delete post', 'userspace-publication' ),
				'class'  => 'uspp-bttn-delete-post',
				'icon'   => 'fa-trash',
			] );
			$content .= '<input type="hidden" name="uspp-delete-post" value="1">';
			$content .= '<input type="hidden" name="post_id" value="' . $this->post_id . '">';
			$content .= '</form>';
		}

		return $content;
	}

	function get_reasons_list() {

		$reasons = [
			[
				'value'   => __( 'Does not correspond the topic', 'userspace-publication' ),
				'content' => __( 'The publication does not correspond to the site topic', 'userspace-publication' ),
			],
			[
				'value'   => __( 'Not completed', 'userspace-publication' ),
				'content' => __( 'Publication does not correspond the rules', 'userspace-publication' ),
			],
			[
				'value'   => __( 'Advertising/Spam', 'userspace-publication' ),
				'content' => __( 'Publication labeled as advertising or spam', 'userspace-publication' ),
			],
		];

		$reasons = apply_filters( 'uspp_public_form_delete_reasons', $reasons, $this->get_object_form() );

		if ( ! $reasons ) {
			return false;
		}

		$content = '<div class="uspp-delete-text">' . __( 'Use blank notice', 'userspace-publication' ) . ':</div>';

		foreach ( $reasons as $reason ) {
			$content .= usp_get_button( [
				'onclick' => 'document.getElementById("reason_content").value="' . $reason['content'] . '"',
				'label'   => $reason['value'],
				'class'   => 'uspp-reason-bttn',
			] );
		}

		return $content;
	}

	function init_form_scripts() {

		$obj = $this->form_object;

		echo '<script type="text/javascript">'
		     . 'uspp_init_public_form({'
		     . 'post_type:"' . esc_js( $obj->post_type ) . '",'
		     . 'post_id:"' . esc_js( $obj->post_id ) . '",'
		     . 'post_status:"' . esc_js( $obj->post_status ) . '",'
		     . 'form_id:"' . esc_js( $this->form_id ) . '"'
		     . '});</script>';
	}

}
