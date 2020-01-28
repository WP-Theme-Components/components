<?php
/**
 * WordPress Theme Components
 *
 * @package WP-Theme-Components\components
 * @author Cameron Jones
 * @version 0.1.0
 */

namespace WP_Theme_Components;

/**
 * Bail if accessed directly
 *
 * @since 0.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Bail if our version of PHP doesn't support namespaces
 *
 * @since 0.1.0
 */
if ( -1 === version_compare( \phpversion(), '5.3.0' ) ) {
	return;
}

/**
 * Get the theme subdirectory components reside in
 *
 * @since 0.1.0
 */
function get_components_directory() {
	return 'theme-components';
}

/**
 * Get the template directory path
 *
 * @since 0.1.0
 */
function get_template_dir() {
	return trailingslashit( \get_template_directory() );
}

/**
 * Get the stylesheet directory path
 *
 * @since 0.1.0
 */
function get_stylesheet_dir() {
	return trailingslashit( \get_stylesheet_directory() );
}

/**
 * Get all installed components
 *
 * @since 0.1.0
 */
function get_components() {
	$template_components   = glob( get_template_dir() . get_components_directory() . '/**/component.php' );
	$stylesheet_components = glob( get_stylesheet_dir() . get_components_directory() . '/**/component.php' );
	$components            = array_merge( $template_components, $stylesheet_components );
	return array_map(
		function( $val ) {
			$data             = get_component_data( $val );
			$data['filepath'] = $val;
			return $data;
		},
		$components
	);
}

/**
 * Include all installed components
 *
 * @since 0.1.0
 */
function require_components() {
	$components = get_components();
	if ( ! empty( $components ) ) {
		foreach ( $components as $component ) {
			require_once $component['filepath'];
		}
	}
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\\require_components' );

/**
 * Create a components directory if one doesn't exist
 *
 * @since 0.1.0
 */
function create_components_directory() {
	if ( ! file_exists( get_stylesheet_dir() . get_components_directory() ) ) {
		mkdir( get_stylesheet_dir() . get_components_directory() );
	}
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\\create_components_directory' );

/**
 * Get the component file headers
 *
 * @since 0.1.0
 * @link https://developer.wordpress.org/reference/functions/get_file_data/
 * @param string $file The file path of the component.
 * @return array
 */
function get_component_data( $file ) {
	// We don't need to write to the file, so just open for reading.
	$fp = \fopen( $file, 'r' );

	// Pull only the first 8 KB of the file in.
	$file_data = \fread( $fp, 8 * KB_IN_BYTES );

	// PHP will close file handle, but we are good citizens.
	\fclose( $fp );

	// Make sure we catch CR-only line endings.
	$file_data = \str_replace( "\r", "\n", $file_data );

	// Set our headers.
	$headers = array(
		'author'  => 'Author',
		'version' => 'Version',
	);

	foreach ( $headers as $field => $regex ) {
		if ( \preg_match( '/^[ \t\/*#@]*' . \preg_quote( $regex, '/' ) . ' (.*)$/mi', $file_data, $match ) && $match[1] ) {
			$headers[ $field ] = \_cleanup_header_comment( $match[1] );
		} else {
			$headers[ $field ] = '';
		}
	}

	return $headers;
}
