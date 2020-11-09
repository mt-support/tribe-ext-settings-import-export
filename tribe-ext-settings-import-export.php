<?php
/**
 * Plugin Name:       The Events Calendar Extension: Settings Import / Export
 * Plugin URI:        https://theeventscalendar.com/extensions/settings-import-export/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-settings-import-export
 * Description:       You can import and export the settings of The Events Calendar.
 * Version:           2.1.0
 * Extension Class:   Tribe\Extensions\Settings_Import_Export\Main
 * Author:            Modern Tribe, Inc.
 * Author URI:        http://m.tri.be/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tribe-ext-settings-import-export
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

namespace Tribe\Extensions\Settings_Import_Export;

use Tribe__Extension;
use Tribe__Utils__Array;

/**
 * Define Constants.
 */

if ( ! defined( __NAMESPACE__ . '\NS' ) ) {
	define( __NAMESPACE__ . '\NS', __NAMESPACE__ . '\\' );
}

/**
 * Polyfill for 'array_key_first' for PHP versions below 7.3
 *
 * 'array_key_first' exists only as of PHP 7.3
 */
if ( ! function_exists( 'array_key_first' ) ) {
	function array_key_first( array $arr ) {
		foreach ( $arr as $key => $unused ) {
			return $key;
		}

		return null;
	}
}

// Do not load unless Tribe Common is fully loaded and our class does not yet exist.
if (
	class_exists( 'Tribe__Extension' )
	&& ! class_exists( NS . 'Main' )
) {
	/**
	 * Extension main class, class begins loading on init() function.
	 */
	class Main extends Tribe__Extension {

		/**
		 * Extension initialization and hooks.
		 */
		public function init() {
			// Load plugin textdomain
			load_plugin_textdomain( 'tribe-ext-settings-import-export', false, basename( __DIR__ ) . '/languages/' );

			if ( ! $this->php_version_check() ) {
				return;
			}

			// Filters and Hooks here
			add_action( 'admin_menu', [ $this, 'settings_menu' ], 99 );

			if ( is_multisite() ) {
				add_action( 'network_admin_menu', [ $this, 'multisite_settings_menu' ], 99 );
			}

			add_action( 'admin_init', [ $this, 'process_settings_action' ] );

			add_action( 'admin_enqueue_scripts', [ $this, 'enquque_styles' ] );
		}

		/**
		 * Check if we have a sufficient version of PHP. Admin notice if we don't and user should see it.
		 *
		 * @link https://theeventscalendar.com/knowledgebase/php-version-requirement-changes/ All extensions require PHP 5.6+.
		 *
		 * @return bool
		 */
		private function php_version_check() {
			$php_required_version = '7.0';

			if ( version_compare( PHP_VERSION, $php_required_version, '<' ) ) {
				if (
					is_admin()
					&& current_user_can( 'activate_plugins' )
				) {
					$message = '<p>';
					$message .= sprintf( esc_html__( '%s requires PHP version %s or newer to work. Please contact your website host and inquire about updating PHP.', 'tribe-ext-settings-import-export' ), $this->get_name(), $php_required_version );
					$message .= sprintf( ' <a href="%1$s">%1$s</a>', 'https://wordpress.org/about/requirements/' );
					$message .= '</p>';
					tribe_notice( 'tribe-ext-settings-import-export' . '-php-version', $message, [ 'type' => 'error' ] );
				}

				return false;
			}

			return true;
		}

		/**
		 * Register the settings page on multi-site.
		 */
		function multisite_settings_menu() {
			add_submenu_page(
				'settings.php',
				__( 'Events Settings Import / Export', 'tribe-ext-settings-import-export' ),
				__( 'Events Settings Import / Export', 'tribe-ext-settings-import-export' ),
				'manage_options',
				'tribe_import_export', [
					$this,
					'settings_page',
				]
			);
		}

		/**
		 * Register the settings page.
		 */
		function settings_menu() {
			add_submenu_page(
				'edit.php?post_type=tribe_events',
				__( 'Settings Import / Export', 'tribe-ext-settings-import-export' ),
				__( 'Settings Import / Export', 'tribe-ext-settings-import-export' ),
				'manage_options',
				'tribe_import_export', [
					$this,
					'settings_page',
				]
			);
		}

		/**
		 * Render the settings page.
		 */
		public function settings_page() {
			$notice_class = '';
			$msg          = '';
			?>
			<div class="wrap tribe-ext-sie">
				<h2><?php esc_html_e( 'Settings Import / Export', 'tribe-ext-settings-import-export' ); ?></h2>

				<?php
				// Success and error messages
				if ( ! empty( $_GET['action'] ) ) {
					if ( $_GET['action'] == 'import_success' ) {
						$msg          = esc_html__( 'Settings imported.', 'tribe-ext-settings-import-export' );
						$notice_class = 'notice-success ';
					} elseif ( $_GET['action'] == 'import_failed' ) {
						$msg          = esc_html__( 'There were some errors during the import.', 'tribe-ext-settings-import-export' );
						$notice_class = 'notice-error ';
					} elseif ( $_GET['action'] == 'reset_success' ) {
						$msg          = esc_html__( 'Reset successful.', 'tribe-ext-settings-import-export' );
						$notice_class = 'notice-success ';
					} elseif ( $_GET['action'] == 'reset_failed' ) {
						$msg          = esc_html__( 'There were some errors during the reset.', 'tribe-ext-settings-import-export' );
						$notice_class = 'notice-error ';
					} elseif ( $_GET['action'] == 'reset_no' ) {
						$msg          = sprintf( esc_html__( 'Reset failed. Please enter "%s" in the text field to reset the settings.', 'tribe-ext-settings-import-export' ), $this->get_reset_keyword() );
						$notice_class = 'notice-error ';
					}

					if ( ! empty ( $_GET['msg'] ) ) {
						$msg .= '<p>' . urldecode( $_GET['msg'] ) . '</p>';
					}
					?>
					<div class="notice <?php echo $notice_class; ?> is-dismissible"><p><?php echo $msg; ?></p></div>
				<?php } ?>

				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'nonce', 'nonce' ); ?>
					<div class="metabox-holder">

						<?php $this->render_export_settings(); ?>

						<?php $this->render_import_settings(); ?>

						<?php $this->render_import_from_text_settings(); ?>

						<?php $this->render_reset_settings(); ?>

					</div><!-- .metabox-holder -->
				</form>
			</div><!--end .wrap-->

			<?php
		}

		/**
		 * Renders the box for exporting settings.
		 */
		public function render_export_settings() {
			?>
			<div class="postbox">
				<h3>
					<span><?php esc_html_e( 'Export Settings', 'tribe-ext-settings-import-export' ); ?></span>
				</h3>
				<div class="inside">
					<p><?php esc_html_e( 'Export the setting of The Events Calendar, Event Tickets and add-ons for this site as a .json file. This allows you to easily import the configuration into another site.', 'tribe-ext-settings-import-export' ); ?></p>
					<?php
					if ( is_network_admin() ) {
						echo '<p><strong>';
						esc_html_e( 'Note: You are on the Network Admin Dashboard.', 'tribe-ext-settings-import-export' );
						echo ' ';
						esc_html_e( 'The export will contain the relevant settings of all sub-sites.', 'tribe-ext-settings-import-export' );
						echo '</strong></p>';
					} elseif ( is_multisite() ) {
						echo '<p><strong>';
						esc_html_e( 'Note: You are on a sub-site in a multi-site network.', 'tribe-ext-settings-import-export' );
						echo ' ';
						esc_html_e( 'The export will contain the relevant settings of this sub-site only.', 'tribe-ext-settings-import-export' );
						echo '</strong></p>';
					}
					?>

					<p>
						<?php submit_button( esc_html__( 'Export', 'tribe-ext-settings-import-export' ), 'secondary', 'export', false ); ?>
					</p>
				</div><!-- .inside -->
			</div><!-- .postbox -->
			<?php
		}

		/**
		 * Renders the box for import settings.
		 */
		public function render_import_settings() {
			?>
			<div class="postbox">
				<h3>
					<span><?php esc_html_e( 'Import Settings', 'tribe-ext-settings-import-export' ); ?></span>
				</h3>
				<div class="inside">
					<p><?php esc_html_e( 'Import the plugin settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.', 'tribe-ext-settings-import-export' ); ?></p>
					<?php
					if ( is_network_admin() ) {
						echo '<p><strong>';
						esc_html_e( 'Note: You are on the Network Admin Dashboard.', 'tribe-ext-settings-import-export' );
						echo ' ';
						esc_html_e( 'Here you can import the settings of all sub-sites at once with the appropriate file.', 'tribe-ext-settings-import-export' );
						echo '</strong></p>';
					} elseif ( is_multisite() ) {
						echo '<p><strong>';
						esc_html_e( 'Note: You are on a sub-site in a multi-site network.', 'tribe-ext-settings-import-export' );
						echo ' ';
						esc_html_e( 'Here you can import the settings for this sub-site only.', 'tribe-ext-settings-import-export' );
						echo '</strong></p>';
					}
					?>

					<p>
						<input type="file" name="import_file" />
					</p>
					<p>
						<?php submit_button( esc_html__( 'Import', 'tribe-ext-settings-import-export' ), 'secondary', 'import', false ); ?>
					</p>
					<?php $this->render_post_import_note(); ?>
				</div><!-- .inside -->
			</div><!-- .postbox -->
			<?php
		}

		/**
		 * Renders the box for importing the settings from copy-paste.
		 */
		public function render_import_from_text_settings() {
			?>
			<div class="postbox">
				<h3>
					<span><?php esc_html_e( 'Import Settings From System Information - Experimental Feature', 'tribe-ext-settings-import-export' ); ?></span>
				</h3>
				<div class="inside">
					<div>
						<p><?php esc_html_e( 'Copy the system information starting with the "SETTINGS" string and ending with the "WP TIMEZONE" string in the text area and watch the magic happen. :)', 'tribe-ext-settings-import-export' ); ?></p>
					</div>
					<div class="tribe-ext-sie-column tribe-ext-sie-column--notes">
						<p>
							<strong><?php esc_html_e( 'Please note the following:', 'tribe-ext-settings-import-export' ) ?></strong>
						</p>
						<ul>
							<li><?php printf( esc_html__( '%1$sThis is an experimental feature!%2$s It can break your settings.', 'tribe-ext-settings-import-export' ), '<span class="underlined">', '</span>' ); ?></li>
							<li><?php printf( esc_html__( 'Trimming is used in the process, so on some values like the Date time separator %s the intended whitespace is trimmed off.', 'tribe-ext-settings-import-export' ), '<code>_@_</code>' ); ?></li>
							<li><?php esc_html_e( 'The Google Maps API key is not being imported.', 'tribe-ext-settings-import-export' ); ?></li>
							<li><?php esc_html_e( 'The PayPal email address is going to be set to the site admin email address.', 'tribe-ext-settings-import-export' ); ?></li>
							<li><?php esc_html_e( 'Custom Fields are not going to be imported.', 'tribe-ext-settings-import-export' ); ?></li>
							<li><?php esc_html_e( 'This functionality is not tested on multi-site networks.', 'tribe-ext-settings-import-export' ); ?></li>
						</ul>
					</div>
					<div class="tribe-ext-sie-column">
						<p>
							<label for="import_textarea">
								<?php esc_html_e( 'Paste the System Information into the box below.', 'tribe-ext-settings-import-export' ); ?>
							</label>
						</p>
						<p>
							<textarea name="import_textarea" id="import_textarea"></textarea>
						</p>
						<p>
							<?php submit_button( esc_html__( 'Import', 'tribe-ext-settings-import-export' ), 'secondary', 'import-from-text', false ); ?>
						</p>
					</div>
					<div class="tribe-ext-sie-column tribe-ext-sie-column--example">
						<p>Example:</p>
						<pre>
SETTINGS
	did_init = 1
	[...]
	single_geography_mode =
WP TIMEZONE
</pre>
					</div>
					<div class="tribe-ext-sie-clear">
						<?php $this->render_post_import_note(); ?>
					</div>
				</div><!-- .inside -->
			</div><!-- .postbox -->
			<?php
		}

		/**
		 * Renders the box for resetting the settings.
		 */
		public function render_reset_settings() {
			?>
			<div class="postbox">
				<h3>
					<span><?php esc_html_e( 'Delete / Reset Settings', 'tribe-ext-settings-import-export' ); ?></span>
				</h3>
				<div class="inside">
					<p><?php esc_html_e( 'Reset the plugin settings.', 'tribe-ext-settings-import-export' ); ?></p>
					<p>
						<strong><?php esc_html_e( 'Please note the following:', 'tribe-ext-settings-import-export' ) ?></strong>
					</p>
					<ul class="tribe-ext-sie-ul">
						<?php
						if ( is_network_admin() ) {
							echo '<li><strong>';
							esc_html_e( 'You are on the Network Admin Dashboard.', 'tribe-ext-settings-import-export' );
							echo ' ';
							esc_html_e( 'This will reset the calendar settings on ALL sub-sites in the network.', 'tribe-ext-settings-import-export' );
							echo '</strong></li>';
						} elseif ( is_multisite() ) {
							echo '<li><strong>';
							esc_html_e( 'You are on a sub-site in a multi-site network.', 'tribe-ext-settings-import-export' );
							echo ' ';
							esc_html_e( 'This will reset the calendar settings on this sub-sites only.', 'tribe-ext-settings-import-export' );
							echo '</strong></li>';
						}
						?>
						<li><?php printf( esc_html__( 'This operation %scannot be reversed%s. It is recommended that you create a backup of your database first.', 'tribe-ext-settings-import-export' ), '<span class="underlined">', '</span>' ); ?></li>
						<li><?php printf( esc_html__( 'This operation will %snot%s delete any event, venue, organizer, or ticket related data.', 'tribe-ext-settings-import-export' ), '<span class="underlined">', '</span>' ); ?></li>
						<li>
							<strong><?php esc_html_e( 'Modern Tribe takes no responsibility for lost data.', 'tribe-ext-settings-import-export' ); ?></strong>
						</li>
					</ul>

					<p>
						<input type="text" name="import_reset_confirmation" id="import_reset_confirmation" /><br />
						<label for="import_reset_confirmation">
							<?php printf( esc_html__( 'Enter "%s" into the above field if you would like to reset the settings.', 'tribe-ext-settings-import-export' ), $this->get_reset_keyword() ); ?>
						</label>
					</p>
					<p>
						<?php submit_button( esc_html__( 'Reset', 'tribe-ext-settings-import-export' ), 'secondary', 'reset', false ); ?>
					</p>

				</div><!-- .inside -->
			</div><!-- .postbox -->
			<?php
		}

		/**
		 * Process a settings export that generates a .json file of the shop settings.
		 */
		function process_settings_action() {

			$settings        = [];
			$action          = '';
			$success_message = '';

			// Bail if no action.
			if (
				empty ( $_POST['export'] )
				&& empty ( $_POST['import'] )
				&& empty ( $_POST['import_textarea'] )
				&& empty ( $_POST['reset'] )
			) {
				return;
			}

			// Bail if no nonce
			if ( ! wp_verify_nonce( $_POST['nonce'], 'nonce' ) ) {
				return;
			}

			// Bail if no capability
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			/**
			 * Export actions.
			 * Process a settings export that generates a .json file of the shop settings.
			 */
			if ( ! empty ( $_POST['export'] ) ) {

				if ( is_network_admin() ) {

					// Getting all blogs.
					$blogs = $this->get_blogs();

					$original_blog_id = get_current_blog_id();

					// Iterating through all blogs.
					foreach ( $blogs as $blog_id ) {
						switch_to_blog( $blog_id->blog_id );
						// Add the events settings of the blog to the array.
						$settings[ $blog_id->blog_id ] = get_option( 'tribe_events_calendar_options' );
					}
					switch_to_blog( $original_blog_id );
				} else {
					$settings = get_option( 'tribe_events_calendar_options' );
				}

				// TEC - widget_tribe-events-list-widget
				// PRO - widget_tribe-events-adv-list-widget
				// PRO - widget_tribe-events-countdown-widget
				// PRO - widget_tribe-mini-calendar
				// PRO - widget_tribe-events-venue-widget
				// PRO - widget_tribe-events-venue-widget
				// CE  - tribe_community_events_options
				// CE  - Tribe__Events__Community__Schemaschema_version

				nocache_headers();
				header( 'Content-Type: application/json; charset=utf-8' );

				// Filename, default.
				$export_filename_base = '';
				// Multi-site, on network admin page
				if ( is_network_admin() ) {
					$export_filename_base = 'multisite-';
				} // Multi-site, sub-site
				elseif ( is_multisite() ) {
					$export_filename_base = 'blog-id-' . get_current_blog_id() . '-';
				}
				$export_filename = 'tribe-settings-export-' . $export_filename_base . date( 'Y-m-d' ) . '.json';

				/**
				 * Filters the export file name.
				 *
				 * @var string $export_filename_base
				 */
				$export_filename = apply_filters( 'tribe_ext_settings_export_filename', $export_filename );

				header( 'Content-Disposition: attachment; filename=' . $export_filename );
				header( "Expires: 0" );
				echo json_encode( $settings );
			}

			/**
			 * Import actions.
			 * Process a settings import from a json file.
			 */
			if ( ! empty ( $_POST['import'] ) ) {

				$import_file     = Tribe__Utils__Array::get( $_FILES, [ 'import_file', 'tmp_name' ], false );
				$import_filename = Tribe__Utils__Array::get( $_FILES, [ 'import_file', 'name' ], false );

				if ( empty( $import_file ) ) {
					wp_die( __( 'Please upload a file to import.', 'tribe-ext-settings-import-export' ), __( 'Import error', 'tribe-ext-settings-import-export' ), [ 'back_link' => true ] );
				}

				if ( ! empty( $import_filename ) ) {
					$extension = pathinfo( $import_filename, PATHINFO_EXTENSION );
				}

				if ( ! isset ( $extension ) || $extension != 'json' ) {
					wp_die( __( 'Please upload a valid .json file.', 'tribe-ext-settings-import-export' ), __( 'Import error', 'tribe-ext-settings-import-export' ), [ 'back_link' => true ] );
				}

				// Retrieve the settings from the file and convert the json object to an array.
				$settings = json_decode( file_get_contents( $import_file ), true );

				if ( false === $settings ) {
					wp_die( __( 'Sorry, we could not decode the file.', 'tribe-ext-settings-import-export' ), __( 'Import error', 'tribe-ext-settings-import-export' ), [ 'back_link' => true ] );
				} elseif ( ! is_array( $settings ) ) {
					wp_die( __( 'Sorry, the decoded data is not an array.', 'tribe-ext-settings-import-export' ), __( 'Import error', 'tribe-ext-settings-import-export' ), [ 'back_link' => true ] );
				}

				if ( is_network_admin() ) {

					// Getting all blogs.
					$blogs = $this->get_blogs();

					$original_blog_id = get_current_blog_id();

					// Variable to count fails.
					$failed_imports = 0;

					// Iterating through all blogs.
					foreach ( $blogs as $blog_id ) {
						$the_blog_id = $blog_id->blog_id;
						switch_to_blog( $the_blog_id );

						/* translators: the ID of the blog in the network. */
						$success_message .= sprintf( esc_html__( 'Import for blog %s', 'tribe-ext-settings-import-export' ), $the_blog_id ) . ' ';

						// Check if settings for given blog_id exist
						if ( ! empty( $settings[ $the_blog_id ] ) ) {
							// Add the events settings of the blog to the array.
							if ( update_option( 'tribe_events_calendar_options', $settings[ $the_blog_id ] ) ) {
								$success_message .= esc_html__( 'successful.', 'tribe-ext-settings-import-export' );
							} else {
								$success_message .= sprintf(
									esc_html__(
										'%sfailed%s (or settings were the same).',
										'tribe-ext-settings-import-export'
									),
									'<strong>',
									'</strong>'
								);
								$failed_imports ++;
							}
						} else {
							$success_message .= sprintf(
								esc_html__(
									'%snot found%s.',
									'tribe-ext-settings-import-export'
								),
								'<strong>',
								'</strong>'
							);
							$failed_imports ++;
						}
						$success_message .= '<br>';
					}

					switch_to_blog( $original_blog_id );

					// If there are no fails then the import is a full success.
					if ( 0 === $failed_imports ) {
						$action = 'import_success';
					} else {
						$action = 'import_failed';
					}

					wp_safe_redirect( network_admin_url( 'settings.php?page=tribe_import_export&action=' . $action . '&msg=' . urlencode( $success_message ) ) );
				} // If not multi-site.
				else {
					/**
					 * Check if we're uploading a export of a multi-site.
					 * The first key on a multi-site export is a blog ID, thus integer
					 * The first key of a single site export is usually 'schema-version', thus string
					 */
					if ( is_int( array_key_first( $settings ) ) ) {
						$message = '<p>' . esc_html__( 'The settings you wanted to import seem to be of a multi-site network.', 'tribe-ext-settings-import-export' ) . '</p>';
						$message .= '<p>' . esc_html__( 'If you would like to import settings for one site only, then please upload the appropriate file.', 'tribe-ext-settings-import-export' ) . '</p>';
						$message .= '<p>' . esc_html__( 'If you would like to import settings for the whole multi-site network, you can do that on the Network Admin dashboard.', 'tribe-ext-settings-import-export' ) . '</p>';
						wp_die( $message, __( 'Import error', 'tribe-ext-settings-import-export' ), [ 'back_link' => true ] );
					}

					if ( update_option( 'tribe_events_calendar_options', $settings ) ) {
						$action = 'import_success';
					} else {
						$action          = 'import_failed';
						$success_message .= sprintf(
							esc_html__(
								'Import %sfailed%s (or settings were the same).',
								'tribe-ext-settings-import-export'
							),
							'<strong>',
							'</strong>'
						);
					}
					wp_safe_redirect( admin_url( 'edit.php?post_type=tribe_events&page=tribe_import_export&action=' . $action . '&msg=' . urlencode( $success_message ) ) );
				}

			}

			/**
			 * Import from text actions.
			 * Process a settings import from a json file.
			 */
			if ( ! empty ( $_POST['import_textarea'] ) ) {

				$sysinfo = $this->treat_sysinfo_for_import( $_POST['import_textarea'] );

				// Save the data in the database
				if ( update_option( 'tribe_events_calendar_options', $sysinfo ) ) {
					$action = 'import_success';
				} else {
					$action          = 'import_failed';
					$success_message .= sprintf(
						esc_html__(
							'Import %sfailed%s (or settings were the same).',
							'tribe-ext-settings-import-export'
						),
						'<strong>',
						'</strong>'
					);
				}
				wp_safe_redirect( admin_url( 'edit.php?post_type=tribe_events&page=tribe_import_export&action=' . $action . '&msg=' . urlencode( $success_message ) ) );

			}

			/**
			 * Reset actions.
			 * Reset Modern Tribe calendar and ticketing plugins.
			 */
			if ( ! empty( $_POST['reset'] ) ) {

				// If multi-site.
				if ( is_network_admin() ) {

					// Return if not reset
					if ( $_POST['import_reset_confirmation'] !== 'reset all' ) {
						$action = 'reset_no';
					} // Reset
					elseif ( $_POST['import_reset_confirmation'] === 'reset all' ) {

						// Getting all blogs.
						$blogs = $this->get_blogs();

						$original_blog_id = get_current_blog_id();

						// Variable to count fails.
						$failed_resets = 0;

						// Iterating through all blogs.
						foreach ( $blogs as $blog_id ) {

							$the_blog_id = $blog_id->blog_id;
							switch_to_blog( $the_blog_id );

							/* translators: the ID of the blog in the network. */
							$success_message .= sprintf( esc_html__( 'Resetting blog %s', 'tribe-ext-settings-import-export' ), $the_blog_id ) . ' ';

							// Add the events settings of the blog to the array.
							if ( delete_option( 'tribe_events_calendar_options' ) ) {
								$success_message .= esc_html__( 'successful.', 'tribe-ext-settings-import-export' );
							} else {
								$success_message .= sprintf(
									esc_html__(
										'%sfailed%s.',
										'tribe-ext-settings-import-export'
									),
									'<strong>',
									'</strong>'
								);
								$failed_resets ++;
							}
							$success_message .= '<br>';
						}

						switch_to_blog( $original_blog_id );

						// If there are no fails then the reset is a full success.
						if ( 0 === $failed_resets ) {
							$action = 'reset_success';
						} else {
							$action = 'reset_failed';
						}
					}
					wp_safe_redirect( network_admin_url( 'settings.php?page=tribe_import_export&action=' . $action . '&msg=' . urlencode( $success_message ) ) );
				} // If not multi-site.
				else {
					if ( ! empty ( $_POST['reset'] ) ) {
						// Return if not reset
						if ( $_POST['import_reset_confirmation'] !== 'reset' ) {
							$action = 'reset_no';
						} // Reset
						elseif ( $_POST['import_reset_confirmation'] === 'reset' ) {
							if ( delete_option( 'tribe_events_calendar_options' ) ) {
								$action = 'reset_success';
							} else {
								$action = 'reset_failed';
							}
						}

						wp_safe_redirect( admin_url( 'edit.php?post_type=tribe_events&page=tribe_import_export&action=' . $action ) );
					}
				}
			}

			exit;
		}

		/**
		 * Get all blogs from the multi-site network.
		 *
		 * @return array|object|null
		 */
		private function get_blogs() {
			global $wpdb;

			return $wpdb->get_results(
				"
                        SELECT blog_id
                        FROM {$wpdb->blogs}
                        WHERE site_id = '{$wpdb->siteid}'
                        AND spam = '0'
                        AND deleted = '0'
                        AND archived = '0'
                    "
			);
		}

		/**
		 * Returns the keyword for resetting the settings.
		 *
		 * @return string
		 */
		private function get_reset_keyword() {
			$keyword = 'reset';
			if ( is_network_admin() ) {
				$keyword = 'reset all';
			}

			return $keyword;
		}

		/**
		 * Treat the system information string and create an array from it.
		 *
		 * @param $sysinfo    The submitted system information string.
		 *
		 * @return array
		 */
		private function treat_sysinfo_for_import( $sysinfo ) {
			$new_sysinfo = [];

			// Check if some strings are in the sysinfo. If not, bail.
			if (
				strpos( $sysinfo, 'Settings' ) != 0
				|| strtolower( substr( $sysinfo, - 11 ) ) != strtolower( 'WP Timezone' )
			) {
				$message = '<p>' . esc_html__( 'There was an error. Try again.', 'tribe-ext-settings-import-export' ) . '</p>';
				wp_die( $message, __( 'Import error', 'tribe-ext-settings-import-export' ), [ 'back_link' => true ] );
			}

			// Treat sysinfo
			$boolean_value = [
				'did_init',
				'views_v2_enabled',
				'enable_month_view_cache',
				'tribeDisableTribeBar',
				'hideLocationSearch',
				'hideRelatedEvents',
				'week_view_hide_weekends',
				'tribeEventsShortcodeBeforeHTML',
				'tribeEventsShortcodeAfterHTML',
				'ticket-attendee-modal',
				'ticket-paypal-enable',
				'ticket-paypal-sandbox',
				'tickets-enable-qr-codes',
				'donate-link',
				'hideSubsequentRecurrencesDefault',
				'userToggleSubsequentRecurrences',
				'toggle_blocks_editor',
				'toggle_blocks_editor_hidden_field',
				'showComments',
				'showEventsInMainLoop',
				'reverseCurrencyPosition',
				'embedGoogleMaps',
				'debugEvents',
				'tribe_events_timezones_show_zone',
				'disable_metabox_custom_fields',
			];

			$integer_value = [
				'custom-fields-max-index',
				'tribe_tickets_migrate_offset_',
			];

			$delimiter = ';';

			$count = 1;
			// Remove starting and ending string
			$sysinfo = str_replace( 'Settings', '', $sysinfo, $count );
			$sysinfo = str_ireplace( 'WP Timezone', '', $sysinfo, $count );

			// Trim
			$sysinfo = trim( $sysinfo );

			// Need to handle arrays and more
			$patterns = [
				'/\(\s*\[/',                // Square brackets at the beginning of the array
				'/\s{2,}\[/',               // Square brackets
				'/\s*\)\s\n/',              // Closing parentheses
				'/(\s*\n*)(Array)\s*\(/',   // Array
				'/\r\n/',                   // Newline with separator
				'/(\s{2,})/',               // Spaces
			];

			$replacements = [
				'( [',                      // Square brackets at the beginning of the array
				', [',                      // Square brackets
				' )',                       // Closing parentheses
				' Array(',                  // Array
				' ' . $delimiter,           // Newline with separator
				'',                         // Spaces
			];

			$sysinfo = preg_replace( $patterns, $replacements, $sysinfo );

			// Create an array from the values
			$sysinfo = explode( $delimiter, $sysinfo );

			// Explode each attrib=value into an array and create a new array that we can serialize.
			foreach ( $sysinfo as $item ) {

				$item  = explode( ' = ', $item );
				$key   = $item[0];
				$value = $item[1];

				// If value is null, skip.
				if ( null == $value ) {
					continue;
				}

				// We don't want to use the user's Google Maps API key.
				if ( $key == 'google_maps_js_api_key' ) {
					continue;
				}

				// We don't want to use the user's PayPal email address. We will use admin email instead.
				if ( $key == 'ticket-paypal-email' ) {
					$key = get_option( 'admin_email' );
				}

				// If Array is twice, do some magic (custom fields)
				// Skip for now
				if ( substr_count( $value, 'Array' ) > 1 ) {
					continue;
				}

				// If it is supposed to be an Array, then make it so.
				// Sample: previous_ecp_versions = Array( [0] => 0, [1] => 5.1.6, [2] => 5.2.0 ) '
				if ( is_int( strpos( $value, 'Array' ) ) ) {

					$patterns = [
						'/(Array\()*( \[\d\] => )((Array))*/',    // "Array( [0] =>" and "[2] =>"
						'/(\s+\))/',                            // Closing parenthesis
					];

					$replacements = [
						'',
						'',
					];

					$value = trim( preg_replace( $patterns, $replacements, $value ) );

					// Create the real array
					$value = explode( ',', $value );
				}

				// If the value is not an array, trim it.
				if ( ! is_array( $value ) ) {
					$value = trim( $value );
				}

				$key = trim( $key );

				// If integer then type set
				if ( in_array( $value, $integer_value ) ) {
					$value = (int) $value;
				}

				// If a boolean...
				if ( in_array( $key, $boolean_value ) ) {
					if ( $value == '1' ) {
						$value = true;
					} elseif ( $value == '0' || empty( $value ) ) {
						$value = false;
					}
				}

				$new_sysinfo[ $key ] = $value;
			}

			return $new_sysinfo;
		}

		/**
		 * Renders the post import note.
		 */
		private function render_post_import_note() {
			?>
			<p class="important">
				<?php esc_html_e( 'After running the import it is recommended to re-save the calendar settings and to flush permalinks.', 'tribe-ext-settings-import-export' ); ?>
			</p>
			<?php
		}

		/**
		 * Enqueuing stylesheet
		 */
		public function enquque_styles() {
			wp_enqueue_style(
				'tribe-ext-settings-import-export-css',
				plugin_dir_url( __FILE__ ) . 'src/resources/style.css'
			);
		}


	} // end class
} // end if class_exists check
