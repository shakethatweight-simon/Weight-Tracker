<?php

	defined('ABSPATH') or die('Jog on!');


function ws_ls_data_table_placeholder( $user_id = false, $max_entries = false,
                                        $smaller_width = false, $enable_add_edit = true,
                                            $order_direction = 'asc' ) {

	ws_ls_data_table_enqueue_scripts();

	$html = '';

	// Saved data?
	if (false === is_admin()) {
		$html = ws_ls_display_data_saved_message();
	}

	$html .= sprintf('<table class="ws-ls-user-data-ajax table ws-ls-loading-table" id="%s"
			data-paging="true"
			data-filtering="true"
			data-sorting="true"
			data-editing="%s"
			data-cascade="true"
			data-toggle="true"
			data-use-parent-width="true"
			data-user-id="%s",
			data-max-entries="%s"
			data-small-width="%s"
			data-order-direction="%s">
		</table>',
		uniqid('ws-ls-'),
		true === $enable_add_edit ? 'true' : 'false',
		is_numeric($user_id) ? $user_id : 'false',
		is_numeric($max_entries) ? $max_entries : 'false',
		$smaller_width ? 'true' : 'false',
        $order_direction
	);

	return $html;
}

function ws_ls_data_table_get_rows($user_id = false, $max_entries = false,
                                    $smaller_width = false, $front_end = false,
                                    $order_direction = 'asc' ) {

	$chart_config = wp_parse_args( $options, [
		'bezier'                => ws_ls_option_to_bool( 'ws-ls-bezier-curve', 'yes', true ),
		'height'                => 250,
		'show-gridlines'        => ws_ls_option_to_bool( 'ws-ls-grid-lines', 'yes', true ),
		'show-target'           => true,
		'show-meta-fields'      => true,
		'type'                  => get_option( 'ws-ls-chart-type', 'line' ),
		'user-id'               => get_current_user_id(),
		'weight-line-color'     => get_option( 'ws-ls-line-colour', '#aeaeae' ),
		'bar-weight-fill-color' => get_option( 'ws-ls-line-fill-colour', '#f9f9f9' ),
		'target-fill-color'     => get_option( 'ws-ls-target-colour', '#76bada' ),
		'begin-y-axis-at-zero'  => ws_ls_option_to_bool( 'ws-ls-axes-start-at-zero', 'n' )
	] );

	// Fetch all columns that will be displayed in data table.
	$columns = ws_ls_data_table_get_columns( $smaller_width, $front_end );

	// Build any filters
	$filters = [];

	if( false === empty( $max_entries ) ) {
		$filters['start'] = 0;
		$filters['limit'] = (int) $max_entries;
	}
	if(is_numeric($user_id)) {
		$filters['user-id'] = $user_id;
	}

	$filters['sort-column'] = 'weight_date';
    $filters['sort-order'] = $order_direction;

	// Fetch all relevant weight entries that we're interested in
	$user_data = ws_ls_user_data( $filters );

	// Loop through the data and expected columns and build a clean array of row data for HTML table.
	$rows = array();

	$previous_user_weight = [];

	// If in front end, load user's weight format
	$convert_weight_format = ( true === $front_end ) ? (int) $user_id : false;

	if ( false === empty( $user_data['weight_data'] ) ) {
        foreach ( $user_data['weight_data'] as $data) {

            // Build a row up for given columns
            $row = array();

            foreach ($columns as $column) {

                $column_name = $column['name'];

                if('gainloss' == $column_name) {

                    // Compare to previous weight and determine if a gain / loss in weight
                    $gain_loss = '';
                    $gain_class = '';

                    if(false === empty($previous_user_weight[$data['user_id']])) {

                        $row['previous-weight'] = $previous_user_weight[$data['user_id']];

                        if ($data['kg'] > $previous_user_weight[$data['user_id']]) {
                            $gain_class = 'gain';
                            $gain_loss = ws_ls_convert_kg_into_relevant_weight_string( $data['kg'] - $previous_user_weight[$data['user_id']], true, $convert_weight_format);
                        } elseif ($data['kg'] < $previous_user_weight[$data['user_id']]) {
                            $gain_class = 'loss';
                            $gain_loss = ws_ls_convert_kg_into_relevant_weight_string( $data['kg'] - $previous_user_weight[$data['user_id']], true, $convert_weight_format);
                        } elseif ($data['kg'] == $previous_user_weight[$data['user_id']]) {
                            $gain_class = 'same';
                        }

                        $row[ 'previous-weight-diff' ] = $data[ 'kg' ] - $previous_user_weight[ $data[ 'user_id' ] ];

                    } else {
                        $gain_loss = __('First entry', WE_LS_SLUG);
                    }

                    $previous_user_weight[$data['user_id']] = $data['kg'];
                }

                if ('gainloss' === $column_name) {
                    $row[$column_name]['value'] = ws_ls_blur_text( $gain_loss );
                    $row[$column_name]['options']['classes'] = 'ws-ls-' . $gain_class .  ws_ls_blur(); // Can use this method for icons
                } else if ('bmi' === $column_name) {
                    $row[$column_name]['value'] =  ws_ls_get_bmi_for_table(ws_ls_user_preferences_get( 'height',$data['user_id']), $data['kg'], __('No height', WE_LS_SLUG)) ;
                    $row[$column_name]['options']['classes'] = 'ws-ls-' . sanitize_key($row[$column_name]['value']) . ws_ls_blur(); // Can use this method for icons
                } else if (!empty($data[$column_name])) {
                    switch ($column_name) {
                        case 'kg':
                            $row[$column_name]['options']['sortValue'] = $data['kg'];
                            $row[$column_name]['options']['classes'] = ws_ls_blur();
                            $row[$column_name]['value'] = ws_ls_blur_text(  ws_ls_convert_kg_into_relevant_weight_string($data['kg'] , false, $convert_weight_format) );
                            break;
                        case 'user_nicename':
                            $row[$column_name]['options']['sortValue'] = $data['user_nicename'];
                            $row[$column_name]['value'] = sprintf('<a href="%s">%s</a>', ws_ls_get_link_to_user_profile($data['user_id']), $data['user_nicename'] );
                            break;
                        default:
                            $row[$column_name] = esc_html( $data[$column_name] );
                            break;
                    }
                } else if ( false !== strpos( $column_name , 'meta-' ) ) {

                    $field_id = str_replace( 'meta-', '', $column_name );

                    $row[ $column_name ] = [];

                    if ( false === empty( $data['meta-fields'][ (int)$field_id ]['value'] ) ) {

                        $field = $data['meta-fields'][ (int) $field_id ];

                        $row[ $column_name ]['value'] = ws_ls_fields_display_field_value( $field['value'], $field['meta_field_id'] );
                        $row[ $column_name ]['value'] = ws_ls_blur_text( $row[ $column_name ]['value'] );
                        $row[ $column_name ]['options']['classes'] = ws_ls_blur();
                    }

                }

            }
            array_push($rows, $row );
        }
    }

    // Reverse the array so most recent entries are shown first (as default)
    $rows = array_reverse( $rows );

    return $rows;
}

/**
 * Fetch the rows for the data table
 * @param $arguments
 *
 * @return array|null
 */
function ws_ls_datatable_rows( $arguments ) {

	$arguments = wp_parse_args( $arguments, [	 'user-id'          => NULL,
	                                             'max-entries'      => NULL,
	                                             'smaller-width'    => false,
	                                             'front-end'        => false,
	                                             'sort-order'       => 'asc'
	] );

	$cache_key  = ws_ls_cache_generate_key_from_array( 'footable', $arguments );
	$rows       = NULL;

	if ( $cache = ws_ls_cache_user_get( $arguments[ 'user-id' ], $cache_key ) ) {
		$rows = $cache;
	} else {

		// Fetch all columns that will be displayed in data table.
		$columns = ws_ls_data_table_get_columns( $arguments[ 'smaller-width' ], $arguments[ 'front-end' ] );

		// Build any filters
		$filters = [];

		if( false === empty( $arguments[ 'max-entries' ] ) ) {
			$filters['start'] = 0;
			$filters['limit'] = (int) $arguments[ 'max-entries' ];
		}

		if( false === empty( $arguments[ 'user-id' ] ) ) {
			$filters['user-id'] = (int) $arguments[ 'user-id' ];
		}

		$filters['sort-column'] = 'weight_date';
		$filters['sort-order']  = $arguments[ 'sort-order' ];

		// Fetch all relevant weight entries that we're interested in
		$user_data = ws_ls_user_data( $filters );

		// Loop through the data and expected columns and build a clean array of row data for HTML table.
		$rows = [];

		$previous_user_weight = [];

		if ( false === empty( $user_data[ 'weight_data' ] ) ) {
			foreach ( $user_data[ 'weight_data' ] as $data ) {

				// Build a row up for given columns
				$row = [];

				foreach ( $columns as $column ) {

					$column_name = $column[ 'name' ];

					if('gainloss' == $column_name) {

						// Compare to previous weight and determine if a gain / loss in weight
						$gain_loss = '';
						$gain_class = '';

						if( false === empty( $previous_user_weight[ $data['user_id'] ] ) ) {

							$row[ 'previous-weight'] = $previous_user_weight[ $data[ 'user_id' ] ];

							if ( $data['kg'] > $previous_user_weight[ $data[ 'user_id' ] ] ) {
								$gain_class = 'gain';
							} elseif ( $data[ 'kg' ] < $previous_user_weight[ $data[ 'user_id' ] ] ) {
								$gain_class = 'loss';
							} elseif ( $data['kg'] == $previous_user_weight[ $data[ 'user_id' ] ] ) {
								$gain_class = 'same';
							}

							$row[ 'previous-weight-diff' ] = $data['kg'] - $previous_user_weight[ $data[ 'user_id' ] ];

						} else {
							$gain_loss = __( 'First entry', WE_LS_SLUG );
						}

						$previous_user_weight[ $data[ 'user_id' ] ] = $data[ 'kg' ];

						$row[ $column_name ][ 'value']              = $gain_loss;
						$row[ $column_name ][ 'options']['classes'] = 'ws-ls-' . $gain_class .  ws_ls_blur();

					} else if ( 'bmi' === $column_name ) {

						$row[ $column_name ][ 'value' ]                 =  ws_ls_get_bmi_for_table(ws_ls_user_preferences_get( 'height',$data['user_id']), $data['kg'], __('No height', WE_LS_SLUG)) ;
						$row[ $column_name ][ 'options' ][ 'classes' ]  = 'ws-ls-' . sanitize_key( $row[ $column_name ][ 'value' ] ) . ws_ls_blur();

					} else if ( false === empty( $data[ $column_name ] ) ) {

						switch ( $column_name ) {
							case 'kg':
								$row[ $column_name ][ 'options' ][ 'sortValue' ]    = $data['kg'];
								$row[ $column_name ][ 'options' ][ 'classes' ]      = ws_ls_blur();
								$row[ $column_name ][ 'value' ]                     = $data['kg'];
								break;
							case 'user_nicename':
								$row[ $column_name ]['options']['sortValue']  = $data[ 'user_nicename' ];
								$row[ $column_name ]['value']                 = sprintf('<a href="%s">%s</a>', ws_ls_get_link_to_user_profile( $data[ 'user_id' ] ), $data[ 'user_nicename' ] );
								break;
							default:
								$row[ $column_name ] = esc_html( $data[ $column_name ] );
								break;
						}

					} else if ( false !== strpos( $column_name , 'meta-' ) ) {

						$field_id = str_replace( 'meta-', '', $column_name );

						$row[ $column_name ] = [];

						if ( false === empty( $data[ 'meta-fields' ][ (int) $field_id ][ 'value' ] ) ) {

							$field = $data[ 'meta-fields' ][ (int) $field_id ] ;

							$row[ $column_name ][ 'value' ]                 = ws_ls_fields_display_field_value( $field[ 'value' ], $field[ 'meta_field_id' ] );
							$row[ $column_name ][ 'value' ]                 = ws_ls_blur_text( $row[ $column_name ][ 'value' ] );
							$row[ $column_name ][ 'options' ][ 'classes' ]  = ws_ls_blur();
						}

					}

				}
				array_push( $rows, $row );
			}
		}

		// Reverse the array so most recent entries are shown first (as default)
		$rows = array_reverse( $rows );

		ws_ls_cache_user_set( $arguments[ 'user-id' ], $cache_key, $rows );
	}

	// Localise the row for the user viewing
	$rows = array_map( 'ws_ls_datatable_rows_localise', $rows );

	return $rows;
}

/**
 * Take a table row and localise for the person viewing it
 * @param $row
 *
 * @return mixed
 */
function ws_ls_datatable_rows_localise( $row ) {

	if ( false === empty( $row[ 'previous-weight-diff' ] ) ) {
		$row[ 'gainloss' ][ 'value' ] = ws_ls_blur_text( ws_ls_weight_display( $row[ 'previous-weight-diff' ], NULL, 'display', false, true ) );
	}

	if ( false === empty( $row[ 'kg' ][ 'value' ] ) ) {
		$row[ 'kg' ][ 'value' ] = ws_ls_blur_text( ws_ls_weight_display( $row[ 'kg' ][ 'value' ], NULL, 'display' ) );
	}

	return $row;
}

/**
 * Depending on settings, return relevant columns for data table
 * @param bool $smaller_width
 * @param bool $front_end
 * @return array - column definitions
 */
function ws_ls_data_table_get_columns($smaller_width = false, $front_end = false) {

	$columns = array (
		array('name' => 'db_row_id', 'title' => 'ID', 'visible'=> false, 'type' => 'number'),
		array('name' => 'user_id', 'title' => 'USER ID', 'visible'=> false, 'type' => 'number')
	);

	// If not front end, add nice nice name
	if (false == $front_end) {
		$columns[] = array('name' => 'user_nicename', 'title' => __('User', WE_LS_SLUG), 'breakpoints'=> '', 'type' => 'text');
	} else {
		// If in the front end, switch to smaller width (hide measurements etc)
		$smaller_width = $front_end;
	}

	$columns[] = array('name' => 'date', 'title' => __('Date', WE_LS_SLUG), 'breakpoints'=> '', 'type' => 'date');
	$columns[] = array('name' => 'kg', 'title' => __('Weight', WE_LS_SLUG), 'visible'=> true, 'type' => 'text');
	$columns[] = array('name' => 'gainloss', 'title' => ws_ls_tooltip('+/-', __('Difference', WE_LS_SLUG)), 'visible'=> true, 'breakpoints'=> 'xs', 'type' => 'text');

	// Add BMI?
	if(WE_LS_DISPLAY_BMI_IN_TABLES) {
		array_push($columns, array('name' => 'bmi', 'title' => ws_ls_tooltip('BMI', __('Body Mass Index', WE_LS_SLUG)), 'breakpoints'=> 'xs', 'type' => 'text'));
	}

    if ( true === ws_ls_meta_fields_is_enabled() ) {

        foreach ( ws_ls_meta_fields_enabled() as $field ) {
        	if ( true === apply_filters( 'wlt-filter-column-include', true, $field ) ) {
				array_push($columns, array('name' => 'meta-' . $field['id'], 'title' => $field['field_name'], 'breakpoints'=> (($smaller_width) ? 'lg' : 'md'), 'type' => 'text'));
			}
        }

    }

	// Add notes;
	array_push($columns, array('name' => 'notes', 'title' => __('Notes', WE_LS_SLUG), 'breakpoints'=> 'lg', 'type' => 'text'));

	$columns = apply_filters( 'wlt-filter-front-end-data-table-columns', $columns );

	return $columns;
}


/**
 * Enqueue relevant CSS / JS when needed to make footables work
 */
function ws_ls_data_table_enqueue_scripts() {

	$minified = ws_ls_use_minified();

	wp_enqueue_style('ws-ls-footables', plugins_url( '/assets/css/footable.standalone.min.css', __DIR__  ), [], WE_LS_CURRENT_VERSION);
    wp_enqueue_style('ws-ls-footables-wlt', plugins_url( '/assets/css/footable.css', __DIR__ ), [ 'ws-ls-footables' ], WE_LS_CURRENT_VERSION);
    wp_enqueue_script('ws-ls-footables-js', plugins_url( '/assets/js/footable.min.js', __DIR__ ), [ 'jquery' ], WE_LS_CURRENT_VERSION, true);
	wp_enqueue_script('ws-ls-footables-admin', plugins_url( '/assets/js/data.footable' .     $minified . '.js', __DIR__ ), [ 'ws-ls-footables-js' ], WE_LS_CURRENT_VERSION, true);
	wp_localize_script('ws-ls-footables-admin', 'ws_user_table_config', ws_ls_data_js_config() );
    wp_enqueue_style('fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', [], WE_LS_CURRENT_VERSION);
}

/**
 * Used to embed config settings for jQuery front end
 * @return array of settings
 */
function ws_ls_data_js_config() {
	$config = array(
					'security' => wp_create_nonce('ws-ls-user-tables'),
					'base-url' => ws_ls_get_link_to_user_data(),
					'base-url-meta-fields' => ws_ls_meta_fields_base_url(),
					'base-url-awards' => ws_ls_awards_base_url(),
					'label-add' =>  __('Add', WE_LS_SLUG),
                    'label-meta-fields-add-button' =>  __('Add Custom Field', WE_LS_SLUG),
					'label-awards-add-button' =>  __('Add Award', WE_LS_SLUG),
					'label-confirm-delete' =>  __('Are you sure you want to delete the row?', WE_LS_SLUG),
					'label-error-delete' =>  __('Unfortunately there was an error deleting the row.', WE_LS_SLUG),
                    'locale-search-text' =>  __('Search', WE_LS_SLUG),
					'locale-no-results' =>  __('No data found', WE_LS_SLUG)
				);
	// Add some extra config settings if not in admin
    if ( false === is_admin() ) {
        $config['front-end'] = 'true';
        $config['ajax-url'] = admin_url('admin-ajax.php');

        $edit_link = ws_ls_get_url();

        // Strip old edit and cancel QS values
		$edit_link = remove_query_arg( ['ws-edit-entry', 'ws-edit-cancel', 'ws-edit-saved'], $edit_link );

		$config['edit-url'] = esc_url( add_query_arg( 'ws-edit-entry', '|ws-id|', $edit_link ) );

		$config['current-url-base64'] = add_query_arg( 'ws-edit-saved', 'true', $edit_link );
		$config['current-url-base64'] = base64_encode($config['current-url-base64']);
        $config['us-date'] = ( false === ws_ls_get_config('WE_LS_US_DATE', get_current_user_id()) ) ? 'false' : 'true';

    } else {
		$config['current-url-base64'] = ws_ls_get_url(true);
        $config['us-date'] = (WE_LS_US_DATE) ? 'true' : 'false';
	}

	return $config;
}
