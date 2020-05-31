<?php
/**
 * WordPress Theme Components
 *
 * @package WP-Theme-Components\components
 * @author Cameron Jones
 * @version 0.2.0
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
	$stylesheet_components = get_stylesheet_dir() !== get_template_dir() ? glob( get_stylesheet_dir() . get_components_directory() . '/**/component.php' ) : array();
	$components            = array_merge( $template_components, $stylesheet_components );
	return array_map(
		function( $val ) {
			$data             = get_component_data( $val );
			$data['filepath'] = $val;
			$name             = ucwords( ltrim( str_replace( '-', ' ', $data['subpackage'] ), '\\' ) );
			$data['name']     = ! empty( $name ) ? $name : ltrim( str_replace( get_template_dir() . get_components_directory(), '', $val ), '/' );
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
add_action( 'after_setup_theme', __NAMESPACE__ . '\\require_components', -999 );

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
add_action( 'after_setup_theme', __NAMESPACE__ . '\\create_components_directory', -999 );

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
		'author'      => '@author',
		'version'     => '@version',
		'package'     => '@package',
		'repository'  => '@link',
		'description' => '*',
		'subpackage'  => '@subpackage',
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

/**
 * Register the admin page to view the list of components
 *
 * @since 0.1.0
 */
function register_admin_page() {
	add_theme_page( 'Components', 'Components', 'manage_options', 'components', __NAMESPACE__ . '\\render_admin_page' );
}

add_action( 'admin_menu', __NAMESPACE__ . '\\register_admin_page' );

/**
 * Render the admin page to view the list of components
 *
 * @since 0.1.0
 */
function render_admin_page() {
	$components = get_components();
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<table class="wp-list-table widefat components">
			<thead>
				<tr>
					<th scope="col" id="name" class="manage-column column-name column-primary">Component</th>
					<th scope="col" id="description" class="manage-column column-description">Details</th>
				</tr>
			</thead>

			<tbody id="the-list">
				<?php
				if ( ! empty( $components ) ) {
					foreach ( $components as $component ) {
						$edit_path = str_replace( get_template_dir(), '', $component['filepath'] );
						$rel_path  = str_replace( get_theme_root(), '', $component['filepath'] );
						$theme     = str_replace( '/', '', str_replace( $edit_path, '', $rel_path ) );
						$edit_url  = \add_query_arg(
							array(
								'file'  => rawurlencode( $edit_path ),
								'theme' => $theme,
							),
							\admin_url( 'theme-editor.php' )
						);
						$actions   = array();
						$meta      = array();
						if ( \current_user_can( 'edit_plugins' ) ) {
							$actions['edit'] = array(
								'link'  => $edit_url,
								'label' => 'Edit',
							);
						}
						if ( ! empty( $component['repository'] ) ) {
							$actions['repository'] = array(
								'link'  => $component['repository'],
								'label' => 'Repository',
							);
						}
						if ( ! empty( $component['version'] ) ) {
							$meta['version'] = array(
								'label' => 'Version ' . $component['version'],
							);
						}
						if ( ! empty( $component['author'] ) ) {
							$meta['author'] = array(
								'label' => 'by ' . $component['author'],
							);
						}
						?>
						<tr>
							<td class="component-title column-primary">
								<strong><?php echo esc_html( $component['name'] ); ?></strong>
								<?php
								if ( ! empty( $actions ) ) {
									?>
									<p class="row-actions visible">
										<?php render_array_of_links( $actions ); ?>
									</p>
									<?php
								}
								?>
							</td>
							<td class="column-description desc">
								<?php
								if ( ! empty( $component['description'] ) ) {
									printf(
										'<p>%1$s</p>',
										esc_html( $component['description'] )
									);
								}
								if ( ! empty( $meta ) ) {
									?>
									<p class="row-actions visible">
										<?php render_array_of_links( $meta ); ?>
									</p>
									<?php
								}
								?>
							</td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>

			<tfoot>
				<tr>
					<th scope="col" class="manage-column column-name column-primary">Component</th>
					<th scope="col" class="manage-column column-description">Details</th>
				</tr>
			</tfoot>

		</table>
	</div>
	<?php
}

/**
 * Render an array of links
 *
 * @since 0.1.0
 * @param array $links Array of links.
 */
function render_array_of_links( $links ) {
	$i = 1;
	$c = count( $links );
	foreach ( $links as $key => $link ) {
		if ( isset( $link['link'] ) ) {
			?>
			<a href="<?php echo esc_url( $link['link'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
				<?php
		} else {
			?>
			<span><?php echo esc_html( $link['label'] ); ?></span>
			<?php
		}
		if ( $i < $c ) {
			echo ' | ';
		}
		$i++;
	}
}

/**
 * Get the URL of the component directory.
 *
 * Useful for getting stylesheets, scripts, images and other static assets. Effectively A URL version of __FILE__
 *
 * @since 0.2.0
 * @param string $file __FILE__ magic constant.
 * @return string
 */
function get_component_url( $file ) {
	$path = str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $file ) );
	$path = ltrim( $path, '/' );
	$path = rtrim( $path, 'component.php' );
	return trailingslashit( site_url() ) . $path;
}
