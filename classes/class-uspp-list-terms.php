<?php

class USPP_List_Terms {

	public $taxonomy;
	public $required;
	public $terms;
	public $selected_term;
	public $datalist;
	public $post_terms;
	public $include_terms;
	public $select_amount;
	public $type_output;
	public $first_option;

	function __construct( $taxonomy = false, $type_output = 'select', $required = false ) {

		$this->taxonomy    = $taxonomy;
		$this->type_output = $type_output;
		$this->required    = $required;
	}

	function get_select_list( $terms, $post_terms, $select_amount, $include_terms = false, $type_output = false, $first = false ) {

		$this->include_terms = ( $include_terms ) ? $include_terms : false;

		$this->terms    = $terms;
		$this->datalist = $this->setup_data( $terms );

		$this->first_option = ( $first ) ? true : false;
		$this->post_terms   = ( $post_terms ) ? $this->setup_data( $post_terms, false ) : 0;

		$this->select_amount = $select_amount;

		if ( $type_output ) {
			$this->type_output = $type_output;
		}

		$method = 'get_' . $this->type_output;

		return $this->$method();
	}

	function get_select() {

		$content = '<div class="uspp-terms-select">';

		for ( $a = 0; $a < $this->select_amount; $a ++ ) {

			$this->selected_term = false;

			$content .= '<div class="category-list usp-field-input type-select-input">';

			$content .= '<select class="postform" name="cats[' . $this->taxonomy . '][]">';

			if ( $a > 0 || $this->first_option ) {
				$content .= '<option value="">' . __( 'Not selected', 'userspace-publication' ) . '</option>';
			}

			$content .= $this->get_options_list();

			$content .= '</select>';

			$content .= '</div>';
		}

		$content .= '</div>';

		return $content;
	}

	function get_multiselect() {

		usp_multiselect_scripts();

		$content = '<div class="uspp-terms-select">';

		for ( $a = 0; $a < $this->select_amount; $a ++ ) {

			$this->selected_term = false;

			$content .= '<div class="category-list usp-field-input type-multiselect-input">';

			$content .= '<select id="taxonomy-field-' . $this->taxonomy . '" class="postform" name="cats[' . $this->taxonomy . '][]" multiple>';

			if ( $a > 0 || $this->first_option ) {
				$content .= '<option value="">' . __( 'Not selected', 'userspace-publication' ) . '</option>';
			}

			$content .= $this->get_options_list();

			$content .= '</select>';

			$content .= '</div>';
		}

		$content .= '</div>';

		$content .= '<script>jQuery(window).on("load", function() {jQuery("#taxonomy-field-' . $this->taxonomy . '").multiselect({
    search: true,
    maxPlaceholderOpts: 5,
    texts: {
        placeholder: "' . __( 'Select some options', 'userspace-publication' ) . '",
        search: "' . __( 'Search', 'userspace-publication' ) . '",
        selectedOptions: " ' . __( 'selected', 'userspace-publication' ) . '",
    }
});});</script>';

		return $content;
	}

	function get_checkbox() {

		$content = '<div class="uspp-terms-select">';

		$content .= '<div class="category-list usp-field-input type-checkbox-input">';

		$content .= $this->get_checkbox_list();

		$content .= '</div>';

		$content .= '</div>';

		return $content;
	}

	function setup_data( $terms, $forInc = true ) {

		$new_terms = [];
		foreach ( $terms as $term ) {
			$new_terms[ $term->term_id ] = [
				'term_id' => $term->term_id,
				'name'    => $term->name,
				'parent'  => $term->parent,
			];
		}

		$datalist = [];

		if ( $forInc && $this->include_terms ) {

			foreach ( $this->include_terms as $incID ) {

				foreach ( $new_terms as $term_id => $term ) {

					if ( $term_id != $incID ) {
						continue;
					}

					$datalist[ $term_id ]           = $term;
					$datalist[ $term_id ]['parent'] = 0;

					$childrens = $this->get_childrens( $term_id );

					if ( $childrens ) {

						$datalist[ $term_id ]['childrens'] = $childrens;

						$child_tree = $this->get_childrens_tree( $term_id );

						if ( $child_tree ) {

							foreach ( $child_tree as $child_id ) {

								$datalist[ $child_id ] = $new_terms[ $child_id ];

								$get_childrens = $this->get_childrens( $child_id );

								if ( $get_childrens ) {
									$datalist[ $child_id ]['childrens'] = $get_childrens;
								}
							}
						}
					}
				}
			}
		} else {

			foreach ( $new_terms as $term_id => $term ) {

				$datalist[ $term_id ] = $term;

				$childrens = $this->get_childrens( $term_id );

				if ( $childrens ) {
					$datalist[ $term_id ]['childrens'] = $childrens;
				}
			}
		}

		return $datalist;
	}

	function get_childrens_tree( $term_id ) {
		$childrens     = $this->get_childrens( $term_id );
		$sub_childrens = $childrens;
		foreach ( $childrens as $child_id ) {
			$sub_childrens = array_merge( $this->get_childrens_tree( $child_id ), $sub_childrens );
		}

		return $sub_childrens;
	}

	function get_childrens( $term_id ) {
		$childrens = [];
		foreach ( $this->terms as $term ) {
			if ( $term->parent != $term_id ) {
				continue;
			}
			$childrens[] = $term->term_id;
		}

		return $childrens;
	}

	function get_options_list( $term_ids = false ) {

		$terms_data = ( $term_ids ) ? $this->get_terms_data( $term_ids ) : $this->datalist;

		if ( ! $terms_data ) {
			return false;
		}

		$options = [];

		foreach ( $terms_data as $term_id => $term ) {

			if ( $term['parent'] ) {
				continue;
			}

			if ( isset( $term['childrens'] ) && $term['childrens'] ) {
				$options[] = '<optgroup label="' . $term['name'] . '">' . $this->get_options_list( $term['childrens'] ) . '</optgroup>';
				continue;
			}

			if ( $this->post_terms ) {

				if ( ! $this->selected_term && selected( isset( $this->post_terms[ $term_id ] ), true, false ) ) {

					unset( $this->post_terms[ $term_id ] );

					$this->selected_term = $term_id;
				}
			}

			$options[] = '<option ' . selected( $this->selected_term, $term_id, false ) . ' value="' . $term_id . '">' . $term['name'] . '</option>';

			if ( 'multiselect' == $this->type_output ) {
				$this->selected_term = false;
			}
		}

		if ( ! $options ) {
			return false;
		}

		return implode( '', $options );
	}

	function get_terms_data( $term_ids ) {
		$terms = [];
		foreach ( $term_ids as $term_id ) {
			$terms[ $term_id ]           = $this->datalist[ $term_id ];
			$terms[ $term_id ]['parent'] = 0;
		}

		return $terms;
	}

	function get_checkbox_list( $term_ids = false ) {

		$terms_data = ( $term_ids ) ? $this->get_terms_data( $term_ids ) : $this->datalist;

		$options = false;
		foreach ( $terms_data as $term_id => $term ) {

			if ( $term['parent'] ) {
				continue;
			}

			if ( isset( $term['childrens'] ) ) {
				$options[] = '<div class="child-list-category">'
				             . '<div class="uspp-parent-category">' . $term['name'] . '</div>'
				             . $this->get_checkbox_list( $term['childrens'] )
				             . '</div>';
				continue;
			}

			$args = [
				'type'    => 'checkbox',
				'id'      => 'category-' . $term_id,
				'name'    => 'cats[' . $this->taxonomy . '][]',
				'checked' => checked( isset( $this->post_terms[ $term_id ] ), true, false ),
				'label'   => $term['name'],
				'value'   => $term_id,
			];

			if ( $this->required ) {
				$args['required'] = true;
				$args['class']    = 'required-checkbox';
			}

			$options[] = uspp_form_field( $args );
		}

		if ( ! $options ) {
			return false;
		}

		return implode( '', $options );
	}

}
