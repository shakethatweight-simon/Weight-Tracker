<?php

defined('ABSPATH') or die('Jog on!');

/**
 * Render [wlt-target] shortcode
 * @param bool $user_id
 *
 * @return string
 */
function ws_ls_shortcode_target( $user_id = NULL ) {

	if( false === is_user_logged_in() ) {
		return '';
	}

	$user_id = ( true === empty( $user_id ) ) ? get_current_user_id() : $user_id;

	$target_weight = ws_ls_target_get( $user_id, 'display' );

	return esc_html( $target_weight );
}
add_shortcode( 'wlt-target', 'ws_ls_shortcode_target' );
add_shortcode( 'wt-target-weight', 'ws_ls_shortcode_target' );

/**
 * Render shortcode [wt-start-weight]
 * @param bool $user_id
 *
 * @return string
 */
function ws_ls_shortcode_start_weight( $user_id = NULL ) {

	if( false === is_user_logged_in() ) {
		return '';
	}

	$arguments[ 'user-id' ] = ( true === empty( $user_id ) ) ? get_current_user_id() : $user_id;

	$oldest_entry = ws_ls_entry_get_oldest( $arguments );

	if( true === empty( $oldest_entry ) ) {
		return '';
	}

	return $oldest_entry[ 'display' ];
}
add_shortcode( 'wlt-weight-start', 'ws_ls_shortcode_start_weight' );
add_shortcode( 'wt-start-weight', 'ws_ls_shortcode_start_weight' );

/**
 * Render shortcode [wt-latest-weight]
 * @param bool $user_id
 *
 * @return string
 */
function ws_ls_shortcode_recent_weight( $user_id = NULL ) {

	if( false === is_user_logged_in() ) {
		return '';
	}

	$arguments[ 'user-id' ] = ( true === empty( $user_id ) ) ? get_current_user_id() : $user_id;

	if ( $cache = ws_ls_cache_user_get( $arguments[ 'user-id' ], 'shortcode-latest-weight' ) ) {
		return $cache;
	}

	$latest_entry = ws_ls_entry_get_latest( $arguments );

	if( true === empty( $latest_entry ) ) {
		return '';
	}

	ws_ls_cache_user_set( $arguments[ 'user-id' ], 'shortcode-latest-weight', $latest_entry[ 'display' ] );

	return $latest_entry[ 'display' ];
}
add_shortcode( 'wlt-weight-most-recent', 'ws_ls_shortcode_recent_weight' );
add_shortcode( 'wt-latest-weight', 'ws_ls_shortcode_recent_weight' );

/**
 * Shortcide [wt-difference-between-latest-previous] display difference between previous
 *
 * @param array $user_defined_arguments
 *
 * @return string|null
 */
function ws_ls_shortcode_difference_in_weight_previous_latest( $user_defined_arguments = [] ){

	$arguments = shortcode_atts( [	'user-id' 					=> get_current_user_id(),
									'invert' 					=> false,
									'display' 					=> 'weight', // weight or percentage
									'include-percentage-sign' 	=> true,
									'kiosk-mode'                => false
								]
	, $user_defined_arguments );

	$cache_key = 'shortcode-diff-prev-latest-' . md5( json_encode( $arguments ) );

	if ( $cache = ws_ls_cache_user_get( $arguments[ 'user-id' ], $cache_key ) ) {
		return $cache;
	}

	$latest_entry = ws_ls_entry_get_latest( $arguments );

	if ( true === empty( $latest_entry[ 'kg' ] ) ) {
		return '';
	}

	$previous_entry = ws_ls_entry_get_previous( $arguments );

	if( true === empty( $previous_entry ) ) {
		return '';
	}

	if ( $previous_entry[ 'id' ] === $latest_entry[ 'id' ] ) {
		return '';
	}

	if ( 'percentage' == $arguments[ 'display' ] ) {

		$output = ws_ls_calculate_percentage_difference( $previous_entry[ 'kg' ], $latest_entry[ 'kg' ] );

		if ( true === empty( $output[ 'percentage' ] ) ) {
			return '';
		}

		$output = ( true === $output[ 'increase' ] ) ?  $output[ 'percentage' ] : -$output[ 'percentage' ];

		$output = ws_ls_round_number( $output, 1 );

		if ( true === $arguments[ 'include-percentage-sign' ] ) {
			$output .= '%';
		}

	} else {

		$difference             = $latest_entry[ 'kg' ] - $previous_entry[ 'kg' ];
		$difference             = ( false === ws_ls_to_bool( $arguments[ 'invert' ] ) ) ? $difference : -$difference ;
		$sign                   = ( $difference > 0 ) ? '+' : '';
		$weight_to_display      = ws_ls_weight_display( $difference, $arguments[ 'user-id' ], false, false, true );
		$output                 = sprintf ('%s%s', $sign, $weight_to_display[ 'display' ] );

		if ( true === $arguments[ 'kiosk-mode' ] ) {

			// Note, this currently only supports lose weight!
			$label = ( $difference <= 0 ) ? 'ykuk-label ykuk-label-success' : 'ykuk-label ykuk-label-warning';

			$output = sprintf( '<span class="%s">%s</span>', $label, $output );
		}
	}

	ws_ls_cache_user_set( $arguments[ 'user-id' ], $cache_key, $output );

	return $output;
}
add_shortcode( 'wt-difference-between-latest-previous', 'ws_ls_shortcode_difference_in_weight_previous_latest' );

/**
 * Display shortcode for difference since start
 * @param null $user_id
 *
 * @return string
 */
function ws_ls_shortcode_difference_in_weight_from_oldest( $user_id = NULL ) {

	// If not logged in then return no value
	if( false === is_user_logged_in() ) {
		return '';
	}

	$arguments[ 'user-id' ] = ( true === empty( $user_id ) ) ? get_current_user_id() : $user_id;

	if ( $cache = ws_ls_cache_user_get( $arguments[ 'user-id' ], 'shortcode-since-start' ) ) {
		return $cache;
	}

	$latest_entry = ws_ls_entry_get_latest( $arguments );

	if( true === empty( $latest_entry ) ) {
		return '';
	}

	$difference =  ws_ls_weight_display( $latest_entry[ 'difference_from_start_kg' ], $arguments[ 'user-id' ], false, false, true );

	ws_ls_cache_user_set( $arguments[ 'user-id' ], 'shortcode-since-start', $difference[ 'display' ] );

	return $difference[ 'display' ];
}
add_shortcode( 'wlt-weight-diff', 'ws_ls_shortcode_difference_in_weight_from_oldest' );
add_shortcode( 'wt-difference-since-start', 'ws_ls_shortcode_difference_in_weight_from_oldest' );

/**
 * Shortcide [wt-difference-from-target] display weight difference from target
 *
 * @param array $user_defined_arguments
 *
 * @return string|null
 */
function ws_ls_shortcode_difference_in_weight_target( $user_defined_arguments = [] ){

	// If not logged in then return no value
	if( false === is_user_logged_in() ) {
		return '';
	}

	$arguments = shortcode_atts( [	'user-id' => get_current_user_id(),
									'display' 					=> 'weight', // weight or percentage
									'include-percentage-sign' 	=> true,
									'invert' => false
								]
	, $user_defined_arguments );

	if ( $cache = ws_ls_cache_user_get( $arguments[ 'user-id' ], 'shortcode-target' ) ) {
		return $cache;
	}

	$latest_entry = ws_ls_entry_get_latest( $arguments );

	if ( true === empty( $latest_entry[ 'kg' ] ) ) {
		return '';
	}

	$target_weight = ws_ls_target_get( $arguments[ 'user-id' ], 'kg' );

	if ( true === empty( $target_weight ) ) {
		return '';
	}

	if ( 'percentage' == $arguments[ 'display' ] ) {

		$output = ws_ls_calculate_percentage_difference( $latest_entry[ 'kg' ], $target_weight );

		if ( true === empty( $output[ 'percentage' ] ) ) {
			return '';
		}

		$output = ( true === $output[ 'increase' ] ) ?  $output[ 'percentage' ] : -$output[ 'percentage' ];

		$output = ws_ls_round_number( $output, 1 );

		if ( true === $arguments[ 'include-percentage-sign' ] ) {
			$output .= '%';
		}

	} else {
		$difference = $latest_entry[ 'kg' ] - $target_weight;

		$difference = ( false === ws_ls_to_bool( $arguments[ 'invert' ] ) ) ? $difference : -$difference ;

		$sign       = ( $difference > 0 ) ? '+' : '';

		$difference = ws_ls_weight_display( $difference, $arguments[ 'user-id' ], false, false, true );
		$output     = sprintf ('%s%s', $sign, $difference[ 'display' ] );
	}

	ws_ls_cache_user_set( $arguments[ 'user-id' ], 'shortcode-target', $output );

	return $output;
}
add_shortcode( 'wlt-weight-diff-from-target', 'ws_ls_shortcode_difference_in_weight_target' );
add_shortcode( 'wt-difference-from-target', 'ws_ls_shortcode_difference_in_weight_target' );
