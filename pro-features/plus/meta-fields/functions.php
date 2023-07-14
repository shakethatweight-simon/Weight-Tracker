<?php

    defined('ABSPATH') or die('Jog on!');

/**
 * Returns true if Meta Fields fully enabled (i.e. not trial mode)
 *
 * @return bool
 */
function ws_ls_meta_fields_is_enabled() {
	return WS_LS_IS_PRO;
}

/**
 * Return base URL for meta fields
 *
 * @param array $args
 *
 * @return string
 */
function ws_ls_meta_fields_base_url( $args = [] ) {

	$url = admin_url( 'admin.php?page=ws-ls-meta-fields' );

	return ( false === empty( $args ) ) ? add_query_arg( $args, $url ) : $url;
}

/**
 * Return an array of field types
 *
 * @return array
 */
function ws_ls_meta_fields_types() {
	return [
		0 => __( 'Number', WE_LS_SLUG),
		3 => __( 'Photo', WE_LS_SLUG),
		5 => __( 'Radio buttons', WE_LS_SLUG ),
		4 => __( 'Range slider', WE_LS_SLUG ),
		7 => __( 'Dropdown', WE_LS_SLUG ),
		6 => __( 'Large text', WE_LS_SLUG ),
		1 => __( 'Small text', WE_LS_SLUG ),
		2 => __( 'Yes', WE_LS_SLUG ) . ' / ' . __( 'No', WE_LS_SLUG )
	];
}

/**
 * Return the text value of a field type ID
 *
 * @param $id
 * @return mixed|string
 */
function ws_ls_meta_fields_types_get_string( $id ) {

	$types = ws_ls_meta_fields_types();

	return ( false === empty( $types[ $id ] ) ) ? $types[ $id ] : '';
}

/**
 * Return a count of enabled meta fields
 *
 * @return int
 */
function ws_ls_meta_fields_number_of_enabled() {

	return count( ws_ls_meta_fields_enabled() );
}

/**
 * Get the field key for given meta id
 *
 * @param $id
 *
 * @param string $column
 *
 * @return mixed|null
 */
function ws_ls_meta_fields_get_column( $id, $column = 'field_key' ) {

	$fields = ws_ls_meta_fields();
	$fields = wp_list_pluck( $fields, $column, 'id' );

	return ( false === empty( $fields[ $id ] ) ) ?
		$fields[ $id ] :
			NULL;

}

/**
 * Return the value for a given entry / meta field
 *
 * @param $entry_id
 * @param $meta_field_id
 * @return null
 */
function ws_ls_meta_fields_get_value_for_entry( $entry_id, $meta_field_id ) {

	if ( false === empty( $entry_id ) ) {

		$data_for_entry = ws_ls_meta( $entry_id );

		foreach ( $data_for_entry as $entry ) {

			if ( (int) $meta_field_id === (int) $entry[ 'meta_field_id' ] ) {
				return $entry[ 'value' ];
			}
		}
	}

	return NULL;
}


/**
 * Fetch all HTML keys for enabled meta fields
 *
 * @return array
 */
function ws_ls_meta_fields_form_field_ids() {

	$ids = [];

	foreach ( ws_ls_meta_fields_enabled() as $field ) {
		$ids[] = ws_ls_meta_fields_form_field_generate_id( $field['id'] );
	}

	return $ids;
}

/**
 *Generate field key
 *
 * @param $id
 * @return string
 */
function ws_ls_meta_fields_form_field_generate_id( $id ) {
	return ( false === empty( $id ) ) ? 'ws-ls-meta-field-' . (int) $id: '';
}

/**
 *
 * Get Meta Fields for entry (used in table display)
 *
 * @param $entry_id
 * @return array
 */
function ws_ls_meta_fields_for_entry_display( $entry_id ) {

	$return = [];

	$data = ws_ls_meta( $entry_id );

	foreach ( $data as $field ) {
		$return[ $field['meta_field_id'] ] = $field;
	}

	return $return;
}

/**
 * Format meta data for display
 *
 * @param $value
 * @param $meta_field_id
 * @param bool $is_export
 *
 * @return int
 */
    function ws_ls_fields_display_field_value( $value, $meta_field_id, $is_export = false ) {

        $meta_field = ws_ls_meta_fields_get_by_id( $meta_field_id );

        if ( false === empty( $meta_field['field_type'] ) ) {

            $meta_field['field_type'] 	= (int) $meta_field['field_type'];
			$value 						= stripslashes( $value );

            // Yes / No
            if ( 2 === $meta_field['field_type'] ) {
                return ws_ls_fields_display_field_value_yes_no( $value);
            } else if ( 3 === $meta_field['field_type'] ) {
		        return ws_ls_fields_display_field_value_photo( $value, $is_export );
	        }

        }

        return $value;
    }

	/**
	 * Render Photo Field
	 *
	 * @param $value
	 * @return string
	 *
	 */
	function ws_ls_fields_display_field_value_photo( $value, $is_export = false ) {

		if ( false === empty( $value ) ) {

			$photo = ws_ls_photo_get( $value , 120, 120);

			// If we are exporting, just return the URL to full image
			if ( true === $is_export ) {
				return  wp_get_attachment_url( $value );
			}

			$photo = apply_filters( 'wlt_meta_fields_photo_value', $photo );

			return sprintf('<a href="%1$s" rel="noopener noreferrer" target="_blank">%2$s</a>',
				esc_url($photo['full']),
				$photo['thumb']
			);

		}

		return '';
	}

    /**
     * Render Yes / No field
     *
     * @param $value
     * @return string
     *
     */
    function ws_ls_fields_display_field_value_yes_no( $value ) {

        switch ( (int) $value ) {
            case 1:
                return __('No', WE_LS_SLUG);
                break;
            case 2:
                return __('Yes', WE_LS_SLUG);
                break;
            default:
                return '';

        }
    }

/**
 * Render Meta Fields form
 *
 * @param $arguments
 * @param null $placeholders
 *
 * @return string
 */
    function ws_ls_meta_fields_form( $arguments, $placeholders = NULL ) {

	    $arguments = wp_parse_args( $arguments, [
	    	                                        'entry'                 => NULL,
		                                            'hide-fields-photos'    => false,
		                                            'custom-field-groups'   => '',      // If specified, only show custom fields that are within these groups
		                                            'custom-field-slugs'    => ''       // If specified, only show the custom fields that are specified
	    ] );

        $html                   = '';
        $photo_fields_rendered  = 0;

        // Do we have to filter by ID or Group?
	    $filter_by_id       = ( false === empty( $arguments[ 'custom-field-slugs' ] ) );
	    $filter_by_group    = ( false === empty( $arguments[ 'custom-field-groups' ] ) );

        $group_id = null;
        $groups = ws_ls_meta_fields_groups(true);
        if ( 1 == count($groups) ) {
            // If there is only one, then it's the "none" group, no custom groups have been added
            $groups = [];
        }
        else {
            $groups = array_column($groups, null, 'id');
        }

        foreach ( ws_ls_meta_fields_enabled() as $field ) {
            
            if ( count( $groups ) && ( $group_id !== $field[ 'group_id' ] ) ) {
                $group_id = $field[ 'group_id' ];
                // get group name and show title
                $html .= sprintf( '<div class="ws-ls-meta-field-group-name">%s</div>', $groups[ $group_id ][ 'name' ] );
            }

	        $field[ 'field_name' ] = stripslashes( $field[ 'field_name' ] );

        	// Filter by ID?
	        if ( true === $filter_by_id &&
	                false === in_array( $field[ 'id' ], $arguments[ 'custom-field-slugs' ] ) ) {
				continue;
	        }

	        // Filter by group?
	        if ( true === $filter_by_group &&
	                ( 0 === (int) $field[ 'group_id' ] || false === in_array( $field[ 'group_id' ], $arguments[ 'custom-field-groups' ] ) ) ) {
		        continue;
	        }

	        $field[ 'placeholder' ] = ( false === empty( $placeholders[ 'meta' ][ $field[ 'id' ] ] ) ) ? $placeholders[ 'meta' ][ $field[ 'id' ] ] . ws_ls_meta_fields_get_column( $field[ 'id' ], 'suffix' ) : '';

	        $value = ( false === empty( $arguments[ 'entry' ][ 'meta'] ) &&
	                     true === array_key_exists( $field[ 'id' ], $arguments[ 'entry' ][ 'meta'] ) ) ?
		                    $arguments[ 'entry' ][ 'meta'][ $field[ 'id' ] ]
					            : '';

	        $html .= apply_filters( 'wlt-form-custom-field-row', '', $field );

            switch ( (int) $field[ 'field_type' ] ) {

                case 1:
                    $html .= ws_ls_meta_fields_form_field_text( $field, $value );
                    break;
                case 2:
                    $html .= ws_ls_meta_fields_form_field_yes_no( $field, $value );
                    break;
	            case 3:

	            	if ( true !== $arguments[ 'hide-fields-photos' ] ) {
			            $html .= ws_ls_meta_fields_form_field_photo( $field, $value );

			            $photo_fields_rendered++;
		            }

		            break;
	            case 4:
		            $html .= ws_ls_meta_fields_form_field_range_slider( $field, $value );
	            	break;
	            case 5:
		            $html .= ws_ls_meta_fields_form_field_radio_buttons( $field, $value );
		            break;
				case 6:
					$html .= ws_ls_meta_fields_form_field_textarea( $field, $value );
					break;
				case 7:
					$html .= ws_ls_meta_fields_form_field_select( $field, $value );
					break;
                default: // 0
                    $html .= ws_ls_meta_fields_form_field_number( $field, $value );
            }
        }

        // Any photos displayed? Add a wee notice about file sizes etc
        if ( $photo_fields_rendered > 0 ) {
            $html .= ws_ls_meta_fields_photos_form_display_info();
        }

        return $html;
    }


    /**
     * Generate the HTML for a meta field text field
     *
     * @param $field
     * @param $value
     * @return string
     */
    function ws_ls_meta_fields_form_field_text( $field, $value ) {

        return sprintf('<div class="ws-ls-meta-field">
                            <label for="%1$s" class="ws-ls-meta-field-title ykuk-form-label" >%2$s</label>
                            <input type="text" id="%1$s" name="%1$s" %3$s tabindex="%4$s" maxlength="200" value="%5$s" class="%1$s ws-ls-meta-field" data-msg="%6$s \'%2$s\'." placeholder="%7$s" />
                        </div>',
            ws_ls_meta_fields_form_field_generate_id( $field['id'] ),
            esc_attr( $field['field_name'] ),
            2 === (int) $field['mandatory'] ? ' required' : '',
            ws_ls_form_tab_index_next(),
            ( false === empty( $value ) ) ? esc_attr( $value ) : '',
            __('Please enter a value for', WE_LS_SLUG),
	        ( false === empty( $field[ 'placeholder' ] ) ) ? esc_attr( $field[ 'placeholder' ] ) : ''
        );

    }

	/**
	 * Generate the HTML for a meta field text field
	 *
	 * @param $field
	 * @param $value
	 * @return string
	 */
	function ws_ls_meta_fields_form_field_textarea( $field, $value ) {

		if ( false === empty( $value ) ) {
			$value = stripslashes( $value );
		}

		return ws_ls_form_field_textarea( [ 'css-class' 		=> '',
											'css-class-label' 	=> 'ws-ls-meta-field-title',
											'css-class-row' 	=> 'ws-ls-meta-field',
											'mandatory'			=> ( 2 === (int) $field['mandatory'] ),
											'name'				=> ws_ls_meta_fields_form_field_generate_id( $field['id'] ),
											'placeholder'		=> '',
											'show-label'		=> true,
											'title'				=> $field['field_name'],
											'value'				=> $value

		]);

	}
    /**
     * Generate the HTML for a meta field number field
     *
     * @param $field
     * @param $value
     * @return string
     */
    function ws_ls_meta_fields_form_field_number( $field, $value ) {

    	//TODO: Refactor to use ws_ls_form_field_number()

        return sprintf('<div class="ws-ls-meta-field">
                            <label for="%1$s" class="ws-ls-meta-field-title ykuk-form-label">%2$s</label>
                            <input type="number" id="%1$s" name="%1$s" %3$s step="any" tabindex="%4$s" maxlength="200" value="%5$s" class="%1$s ws-ls-meta-field" data-msg="%6$s \'%2$s\'." placeholder="%7$s" />
                        </div>',
            ws_ls_meta_fields_form_field_generate_id( $field['id'] ),
            esc_attr( $field['field_name'] ),
            2 === (int)  $field['mandatory'] ? ' required' : '',
            ws_ls_form_tab_index_next(),
            ( false === empty( $value ) ) ? esc_attr( $value ) : '',
            __('Please enter a number for', WE_LS_SLUG),
	        ( false === empty( $field[ 'placeholder' ] ) ) ? esc_attr( $field[ 'placeholder' ] ) : ''
        );

    }

    /**
     * Generate the HTML for a meta field yes / no field
     *
     * @param $field
     * @param $value
     * @return string
     */
    function ws_ls_meta_fields_form_field_yes_no( $field, $value ) {

        $html = sprintf( '<div class="ws-ls-meta-field">
                            <label for="%1$s" class="ws-ls-meta-field-title ykuk-form-label">%2$s</label>
                            <select name="%1$s" id="%1$s" tabindex="%3$s" class="%1$s ykuk-select">
                            ',
                            ws_ls_meta_fields_form_field_generate_id( $field['id'] ),
                            esc_attr( $field['field_name'] ),
                            ws_ls_form_tab_index_next()
        );

        $value = (int) $value;

        if ( 2 !== (int) $field['mandatory'] ) {
            $html .= sprintf( '<option value="0" %1$s ></option>', selected( $value, 0, false ) );
        }

        $html .= sprintf( '<option value="1" %1$s>%2$s</option>', selected( $value, 1, false ), __('No', WE_LS_SLUG) );
        $html .= sprintf( '<option value="2" %1$s>%2$s</option>', selected( $value, 2, false ), __('Yes', WE_LS_SLUG) );

        $html .= '</select></div>';

        return $html;

    }

/**
 * Prep option arrays
 * @param $field
 *
 * @return mixed
 */
function ws_ls_meta_fields_form_prep_options( $field ) {

	foreach ( [ 'options-labels', 'options-values' ] as $key ) {
		if ( true === isset( $field[ $key ] ) ) {

			$field[ $key ] = json_decode( $field[ $key ], true );

			$field[ $key ] = array_filter( $field[ $key ] );

		}
	}

	$field[ 'options' ] = [];

	// Set value to label if not specified
	for ( $i = 0; $i < count( $field[ 'options-labels' ] ); $i++ ) {

		if ( true === empty( $field[ 'options-values' ][ $i ] ) ) {
			$field[ 'options-values' ][ $i ] = $field[ 'options-labels' ][ $i ];
		}

		$field[ 'options' ][ $field[ 'options-values' ][ $i ] ] = $field[ 'options-labels' ][ $i ];
	}

	return $field;
}

/**
 * Display meta field for radio button
 * @param $field
 * @param $value
 *
 * @return string
 */
function ws_ls_meta_fields_form_field_radio_buttons( $field, $value ) {

	if ( false === WS_LS_IS_PRO ) {
		return '';
	}

	$field_id = ws_ls_meta_fields_form_field_generate_id( $field['id'] );

	$html = sprintf( '<div class="ws-ls-meta-field">
						<label for="%1$s" class="ws-ls-meta-field-title ykuk-form-label">%2$s</label>',
		$field_id,
		esc_attr( $field['field_name'] )
	);

	// Prep label/values
	$field = ws_ls_meta_fields_form_prep_options( $field );

	if ( true === empty( $field[ 'options-labels' ] ) ) {
		$html .= '<p>' . __( 'No labels/values have been specified for this question.', WE_LS_SLUG ) . '</p>';
	}

	$first = true;

	foreach ( $field[ 'options-labels' ] as $key => $label ) {

		$option_value = $field[ 'options-values' ][ $key ];
		$checked 		= ( ( $value === $option_value ) ||
		                	( true === $first && 2 === (int) $field[ 'mandatory' ] ) );

		$html .= sprintf ( '<div class="ws-ls-meta-field-radio-button ykuk-form-controls">
							  <input type="radio" id="%2$s" name="%1$s" value="%3$s" class="ykuk-radio" %5$s>
							  <label for="%2$s ykuk-form-label">%4$s</label>
							</div>',
							$field_id,
							ws_ls_component_id(),
							esc_attr( $option_value ),
							esc_attr( $label ),
							( true === $checked ) ? 'checked' : ''
		);

		$first = false;
	}

	$html .= '</div>';

	return $html;
}

/**
 * Meta field select
 * @param $field
 * @param $value
 * @return string
 */
function ws_ls_meta_fields_form_field_select( $field, $value ) {

	if ( false === WS_LS_IS_PRO ) {
		return '';
	}

	// Prep label/values
	$field = ws_ls_meta_fields_form_prep_options( $field );

	if ( true === empty( $field[ 'options' ] ) ) {
		return '<p>' . __( 'No labels/values have been specified for this question.', WE_LS_SLUG ) . '</p>';
	}

	return ws_ls_form_field_select([	'key' 			    =>  ws_ls_meta_fields_form_field_generate_id( $field['id'] ),
										'label'			    => $field[ 'field_name' ],
										'values'		    => $field[ 'options' ],
										'selected'		    => $value,
										'include-div'       => true,
										'css-class'		    => 'ykuk-select',
										'css-class-row'     => 'ws-ls-meta-field',
										'css-class-title'   => 'ws-ls-meta-field-title',
										'required'          => ( 2 === (int) $field[ 'mandatory' ] ),
										'empty-option'	    => ( 2 === (int) $field[ 'include_empty' ] )
	]);

	return $html;
}

/**
 * Generate the HTML for a meta field photo
 *
 * @param $field
 * @param $value
 * @param null $field_id
 * @return string
 */
	function ws_ls_meta_fields_form_field_photo( $field, $value, $field_id = NULL ) {

		if ( false === WS_LS_IS_PRO ) {
			return '';
		}

		$field_id = $field_id ?: ws_ls_meta_fields_form_field_generate_id( $field['id'] );

		$html = sprintf('<div class="ws-ls-meta-field ws-ls-meta-field-photo">
                            <label for="%1$s" class="ws-ls-meta-field-title ykuk-form-label">%2$s</label>',
                            esc_attr( $field_id ),
							esc_html( $field['field_name'] )
                        );

        $attachment_id = NULL;

        $html .= '<div class="ws-ls-table ws-ls-photo-current">
                       <div class="ws-ls-row">';

        // Show Add button
        $html .= sprintf('<div class="ws-ls-cell ws-ls-photo-select">
                                <input type="file" data-msg="%6$s \'%7$s\'." name="%1$s" id="%8$s" tabindex="%2$s" data-rule-accept="png|jpeg|jpg" class="ws-ls-hide ws-ls-input-file ws-ls-meta-fields-photo" %5$s data-required="%4$s" />
                                <label for="%8$s" class="ws-ls-button">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="17" viewBox="0 0 20 17"><path d="M10 0l-5.2 4.9h3.3v5.1h3.8v-5.1h3.3l-5.2-4.9zm9.3 11.5l-3.2-2.1h-2l3.4 2.6h-3.5c-.1 0-.2.1-.2.1l-.8 2.3h-6l-.8-2.2c-.1-.1-.1-.2-.2-.2h-3.6l3.4-2.6h-2l-3.2 2.1c-.4.3-.7 1-.6 1.5l.6 3.1c.1.5.7.9 1.2.9h16.3c.6 0 1.1-.4 1.3-.9l.6-3.1c.1-.5-.2-1.2-.7-1.5z"/></svg>
                                    <span>%3$s</span>
                                </label>

                        ',
            esc_attr( $field_id ),
            ws_ls_form_tab_index_next(),
            ( false === empty( $value ) ) ? __('Replace photo', WE_LS_SLUG) : __('Select photo', WE_LS_SLUG),
            2 === (int) $field['mandatory'] ? 'y' : 'n',
            true === empty( $value ) && 2 === (int) $field['mandatory'] ? 'required' : '',
            __('Please select a photo (png or jpg) for', WE_LS_SLUG),
            esc_attr( $field['field_name'] ),
            ws_ls_component_id()
        );

		// Do we have an existing photo?
		if ( false === empty( $value ) ) {

			$attachment_id = (int) $value;

			$thumbnail = wp_get_attachment_image_src( $attachment_id );
			$full_url = wp_get_attachment_url( $attachment_id );

			if ( false === empty($thumbnail) ) {

				$thumbnail 	= apply_filters( 'wlt-filter-form-photo-thumbnail', $thumbnail );
				$full_url 	= apply_filters( 'wlt-filter-form-photo-full-url', $full_url );

				$html .= sprintf('

													<p>%8$s:</p>
													<a href="%1$s" target="_blank" rel="noopener noreferrer">
														<img src="%2$s" alt="%3$s" width="%5$s" height="%6$s" />
													</a>
													<div class="ws-ls-photo-delete-existing">
														<input type="checkbox" name="%9$s-delete" id="%9$s-delete" data-required="%10$s" data-field-id="%9$s" class="ws-ls-photo-field-delete" value="y" />
														<label for="%9$s-delete">%7$s</label>
													</div>

												<input type="hidden" name="%9$s-previous" value="%4$s" />
										 ',
						esc_url( $full_url ),
						esc_url( $thumbnail[0] ),
						__('Existing photo for this date', WE_LS_SLUG),
						(int) $attachment_id,
						(int) $thumbnail[1],
						(int) $thumbnail[2],
						__( 'Delete existing photo', WE_LS_SLUG ),
						__( 'Existing photo', WE_LS_SLUG ),
						esc_attr( $field_id ),
						2 === (int) $field['mandatory'] ? 'y' : 'n'
					);
				}
		}

        $html .= '</div></div></div></div>';

		return $html;
}

/**
 * Slug exist?
 * @param $slug
 * @param null $exising_id
 *
 * @return string|null
 */
function ws_ls_meta_fields_group_slug_generate( $slug, $exising_id = NULL ) {

	if ( true === empty( $slug ) ) {
		return NULL;
	}

	$slug = sanitize_title( $slug );

	$original_slug = $slug;

	$try = 1;

	// Ensure the slug is unique
	while ( false === ws_ls_meta_fields_slug_is_unique( $slug, $exising_id ) ) {

		$slug = sprintf( '%s_%d', $original_slug, $try );

		$try++;
	}

	return $slug;
}

/**
 * Return the link for managing Groups page
 * @return string
 */
function ws_ls_meta_fields_groups_link() {
	return admin_url( 'admin.php?page=ws-ls-meta-fields&mode=groups' );
}

/**
 * Take one or many custom field group slugs and convert to ID
 * @param $slugs
 *
 * @return array
 */
function ws_ls_meta_fields_groups_slugs_to_ids( $slugs ) {

	$ids = NULL;

	if ( true === empty( $slugs ) ) {
		return $ids;
	}

	$slugs  = explode( ',', $slugs );
	$groups = ws_ls_meta_fields_groups();
	$groups = wp_list_pluck( $groups, 'id', 'slug' );

	foreach ( $slugs as $slug ) {

		if ( false === empty( $groups[ $slug ] ) ) {
			$ids[] = (int) $groups[ $slug ];
		}
	}

	return $ids;
}

/**
 * Take one or many custom field slugs and convert to ID
 * @param $slugs
 *
 * @return array
 */
function ws_ls_meta_fields_slugs_to_ids( $slugs ) {

	if ( true === empty( $slugs ) ) {
		return null;
	}

	$ids = NULL;

	$slugs  = explode( ',', $slugs );
	$fields = ws_ls_meta_fields();
	$fields = wp_list_pluck( $fields, 'id', 'field_key' );

	foreach ( $slugs as $slug ) {

		if ( false === empty( $fields[ $slug ] ) ) {
			$ids[] = (int) $fields[ $slug ];
		}
	}

	return $ids;
}

/**
 * Take one custom field slugs and convert an to ID
 * @param $slug
 *
 * @return array
 */
function ws_ls_meta_fields_slug_to_id( $slug ) {

	if ( true === empty( $slug ) ) {
		return null;
	}

	$key = ws_ls_meta_fields_slugs_to_ids( $slug );

	if ( true === empty( $key[ 0 ] ) ) {
		return null;
	}

	return (int) $key[ 0 ];
}

/**
 * Merge slugs and groups custom field IDs into one.
 * @param $arguments
 *
 * @return array
 */
function ws_ls_meta_fields_slugs_and_groups_to_id( $arguments ) {

	$ids = [];

	if ( false === empty( $arguments[ 'custom-field-groups' ] ) ) {
		$arguments[ 'custom-field-groups' ] = ws_ls_meta_fields_groups_slugs_to_ids( $arguments[ 'custom-field-groups' ] );

		$arguments[ 'custom-field-groups' ] = ws_ls_meta_fields_group_field_ids( $arguments[ 'custom-field-groups' ] );

		$ids = array_merge( $ids, $arguments[ 'custom-field-groups' ] );
	}

	if ( false === empty( $arguments[ 'custom-field-slugs' ] ) ) {
		$arguments[ 'custom-field-slugs' ]  = ws_ls_meta_fields_slugs_to_ids( $arguments[ 'custom-field-slugs' ] );
		$ids = array_merge( $ids, $arguments[ 'custom-field-slugs' ] );
	}

	return $ids;

}
