<?php
/**
 * Plugin Name:       The Events Calendar Extension: Settings Import / Export
 * Plugin URI:        https://theeventscalendar.com/extensions/settings-import-export/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-settings-import-export
 * Description:       You can import and export the settings of The Events Calendar
 * Version:           1.1.0-beta
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

/**
 * Define Constants
 */

if ( ! defined( __NAMESPACE__ . '\NS' ) ) {
	define( __NAMESPACE__ . '\NS', __NAMESPACE__ . '\\' );
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
			add_action( 'admin_menu', [ $this, 'tribe_settings_menu' ], 99 );
			add_action( 'admin_init', [ $this, 'tribe_sie_process_settings_action' ] );
			//add_action( 'admin_init', [ $this, 'tribe_sie_process_settings_export' ] );
			//add_action( 'admin_init', [ $this, 'tribe_sie_process_settings_import' ] );
			//add_action( 'admin_init', [ $this, 'tribe_sie_process_settings_reset' ] );
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
		 * Register the settings page
		 */
		function tribe_settings_menu() {
			add_submenu_page(
				'edit.php?post_type=tribe_events',
				__( 'Settings Import / Export', 'tribe-ext-settings-import-export' ),
				__( 'Settings Import / Export', 'tribe-ext-settings-import-export' ),
				'manage_options',
				'tribe_import_export', [
					$this,
					'tribe_sie_settings_page'
				]
			);
		}

		/**
		 * Render the settings page
		 */
		public function tribe_sie_settings_page() {
			?>
			<div class="wrap">
				<h2><?php esc_html_e( 'Settings Import / Export', 'tribe-ext-settings-import-export' ); ?></h2>

				<?php
				// Success and error messages
				if ( ! empty( $_GET['action'] ) ) {

					if ( $_GET['action'] == 'import_success' ) {
						$msg = esc_html__( 'Settings imported.', 'tribe-ext-settings-import-export' );
						$notice_class = 'notice-success ';
					} elseif ( $_GET['action'] == 'import_failed' ) {
						$msg = esc_html__( 'Import failed Please try again.', 'tribe-ext-settings-import-export' );
						$notice_class = 'notice-error ';
					} elseif ( $_GET['action'] == 'reset_success' ) {
						$msg = esc_html__( 'Reset successful.', 'tribe-ext-settings-import-export' );
						$notice_class = 'notice-success ';
					} elseif ( $_GET['action'] == 'reset_failed' ) {
						$msg = esc_html__( 'Settings reset failed.', 'tribe-ext-settings-import-export' );
						$notice_class = 'notice-error ';
					} elseif ( $_GET['action'] == 'reset_no' ) {
						$msg = esc_html__( 'Reset failed. Please enter "reset" in the text field to reset the settings.', 'tribe-ext-settings-import-export' );
						$notice_class = 'notice-error ';
					}
					?>
					<div class="notice <?php echo $notice_class; ?> is-dismissible"><p><?php echo $msg; ?></p></div>
				<?php } ?>

				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'tribe_sie_nonce', 'tribe_sie_nonce' ); ?>
				<div class="metabox-holder">
					<div class="postbox">
						<h3><span><?php esc_html_e( 'Export Settings', 'tribe-ext-settings-import-export' ); ?></span></h3>
						<div class="inside">
							<p><?php esc_html_e( 'Export the setting of The Events Calendar, Event Tickets and add-ons for this site as a .json file. This allows you to easily import the configuration into another site.', 'tribe-ext-settings-import-export' ); ?></p>

								<p><input type="hidden" name="tribe_sie_action" value="export_settings"/></p>
								<p>
									<?php wp_nonce_field( 'tribe_sie_export_nonce', 'tribe_sie_export_nonce' ); ?>
									<?php submit_button( esc_html__( 'Export', 'tribe-ext-settings-import-export' ), 'secondary', 'export', false ); ?>
								</p>
						</div><!-- .inside -->
					</div><!-- .postbox -->

					<div class="postbox">
						<h3><span><?php esc_html_e( 'Import Settings', 'tribe-ext-settings-import-export' ); ?></span></h3>
						<div class="inside">
							<p><?php esc_html_e( 'Import the plugin settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.', 'tribe-ext-settings-import-export' ); ?></p>

								<p>
									<input type="file" name="import_file"/>
								</p>
								<p>
									<input type="hidden" name="tribe_sie_action" value="import_settings"/>
									<?php wp_nonce_field( 'tribe_sie_import_nonce', 'tribe_sie_import_nonce' ); ?>
									<?php submit_button( esc_html__( 'Import', 'tribe-ext-settings-import-export' ), 'secondary', 'import', false ); ?>
								</p>

						</div><!-- .inside -->
					</div><!-- .postbox -->

					<div class="postbox">
						<h3><span><?php esc_html_e( 'Delete / Reset Settings', 'tribe-ext-settings-import-export' ); ?></span></h3>
						<div class="inside">
							<p><?php esc_html_e( 'Reset the plugin settings.', 'tribe-ext-settings-import-export' ); ?></p>
							<p>
								<strong><?php esc_html_e( 'Please note the following:', 'tribe-ext-settings-import-export' ) ?></strong>
							</p>
							<ul style="list-style: disc inside">
								<li><?php printf( esc_html__( 'This operation %scannot be reversed%s. It is recommended that you create a backup of your database first.', 'tribe-ext-settings-import-export' ), '<span style="text-decoration: underline;">', '</span>' ); ?></li>
								<li><?php printf( esc_html__( 'This operation will %snot%s delete any event, venue, organizer, or ticket related data.', 'tribe-ext-settings-import-export' ), '<span style="text-decoration: underline;">', '</span>' ); ?></li>
								<li>
									<strong><?php esc_html_e( 'Modern Tribe takes no responsibility for lost data.', 'tribe-ext-settings-import-export' ); ?></strong>
								</li>
							</ul>

								<p>
									<input type="text" name="import_reset_confirmation"/><br/>
									<?php esc_html_e( 'Enter "reset" into the above field if you would like to reset the settings.', 'tribe-ext-settings-import-export' ); ?>
								</p>
								<p>
									<input type="hidden" name="tribe_sie_action" value="reset_settings"/>
									<?php wp_nonce_field( 'tribe_sie_import_nonce', 'tribe_sie_import_nonce' ); ?>
									<?php submit_button( esc_html__( 'Reset', 'tribe-ext-settings-import-export' ), 'secondary', 'reset', false ); ?>
								</p>

						</div><!-- .inside -->
					</div><!-- .postbox -->
				</div><!-- .metabox-holder -->
				</form>
			</div><!--end .wrap-->

			<?php
		}

		/**
		 * Process a settings export that generates a .json file of the shop settings
		 */
		function tribe_sie_process_settings_action() {

			$va = empty( $_POST['export'] );
			$vb = empty( $_POST['import'] );
			$vc = empty ( $_POST['reset'] );

			// Bail if no action.
			if (
				empty ( $_POST['export'] )
				&& empty ( $_POST['import'] )
				&& empty ( $_POST['reset'] )
			) {
				return;
			}

			// Bail if no nonce
			if ( ! wp_verify_nonce( $_POST['tribe_sie_nonce'], 'tribe_sie_nonce' ) ) {
				return;
			}

			// Bail if no capability
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Export actions.
			if ( ! empty ( $_POST['export'] ) ) {
				$settings = get_option( 'tribe_events_calendar_options' );

				// TEC - widget_tribe-events-list-widget
				// PRO - widget_tribe-events-adv-list-widget
				// PRO - widget_tribe-events-countdown-widget
				// PRO - widget_tribe-mini-calendar
				// PRO - widget_tribe-events-venue-widget
				// PRO - widget_tribe-events-venue-widget
				// CE  - tribe_community_events_options
				// CE  - Tribe__Events__Community__Schemaschema_version

				ignore_user_abort( true );
				nocache_headers();
				header( 'Content-Type: application/json; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename=tribe-settings-export-' . date( 'm-d-Y' ) . '.json' );
				header( "Expires: 0" );
				echo json_encode( $settings );
				exit;
			}

			// Import actions.
			if ( ! empty ( $_POST['import'] ) ) {

				$import_file = $_FILES['import_file']['tmp_name'];
				$import_filename = $_FILES['import_file']['name'];

				if ( empty( $import_file ) ) {
					wp_die( __( 'Please upload a file to import.', 'tribe-ext-settings-import-export' ) );
				}

				if ( ! empty( $import_filename ) ) {
					$tmp = explode( '.', $import_filename );
					$extension = end( $tmp );
				}

				if ( ! isset ( $extension ) || $extension != 'json' ) {
					wp_die( __( 'Please upload a valid .json file.', 'tribe-ext-settings-import-export' ) );
				}

				// Retrieve the settings from the file and convert the json object to an array.
				$settings = json_decode( file_get_contents( $import_file ), true );

				if ( false === $settings ) {
					wp_die( __( 'Sorry, we could not decode the file.', 'tribe-ext-settings-import-export' ) );
				}
				elseif ( ! is_array( $settings ) ) {
					wp_die( __( 'Sorry, the decoded data is not an array', 'tribe-ext-settings-import-export' ) );
				}

				if ( update_option( 'tribe_events_calendar_options', $settings ) ) {
					$action = 'import_success';
				} else {
					$action = 'import_failed';
				}

				wp_safe_redirect( admin_url( 'edit.php?post_type=tribe_events&page=tribe_import_export&action=' . $action ) );
				exit;
			}

			// Reset actions.

		}

		/**
		 * Process a settings export that generates a .json file of the shop settings
		 */
		function tribe_sie_process_settings_export() {
			// Bail if no action
			if ( empty( $_POST['tribe_sie_action'] ) || 'export_settings' != $_POST['tribe_sie_action'] ) {
				return;
			}
			$varr = $_POST['export'];
			// Bail if no nonce
			if ( ! wp_verify_nonce( $_POST['tribe_sie_export_nonce'], 'tribe_sie_export_nonce' ) ) {
				return;
			}
			// Bail if no capability
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$settings = get_option( 'tribe_events_calendar_options' );

			// TEC - widget_tribe-events-list-widget
			// PRO - widget_tribe-events-adv-list-widget
			// PRO - widget_tribe-events-countdown-widget
			// PRO - widget_tribe-mini-calendar
			// PRO - widget_tribe-events-venue-widget
			// PRO - widget_tribe-events-venue-widget
			// CE  - tribe_community_events_options
			// CE  - Tribe__Events__Community__Schemaschema_version

			ignore_user_abort( true );
			nocache_headers();
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=tribe-settings-export-' . date( 'm-d-Y' ) . '.json' );
			header( "Expires: 0" );
			echo json_encode( $settings );
			exit;
		}

		/**
		 * Process a settings import from a json file
		 */
		function tribe_sie_process_settings_import() {

			// Bail if no action
			if ( empty( $_POST['tribe_sie_action'] ) || 'import_settings' != $_POST['tribe_sie_action'] ) {
				return;
			}

			// Bail if no nonce
			if ( ! wp_verify_nonce( $_POST['tribe_sie_import_nonce'], 'tribe_sie_import_nonce' ) ) {
				return;
			}

			// Bail if no capability
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$import_file = $_FILES['import_file']['tmp_name'];
			$import_filename = $_FILES['import_file']['name'];

			if ( empty( $import_file ) ) {
				wp_die( __( 'Please upload a file to import.', 'tribe-ext-settings-import-export' ) );
			}

			if ( ! empty( $import_filename ) ) {
				$tmp = explode( '.', $import_filename );
				$extension = end( $tmp );
			}

			if ( ! isset ( $extension ) || $extension != 'json' ) {
				wp_die( __( 'Please upload a valid .json file.', 'tribe-ext-settings-import-export' ) );
			}

			// Retrieve the settings from the file and convert the json object to an array.
			$settings = json_decode( file_get_contents( $import_file ), true );

			if ( false === $settings ) {
				wp_die( __( 'Sorry, we could not decode the file.', 'tribe-ext-settings-import-export' ) );
			}
			elseif ( ! is_array( $settings ) ) {
				wp_die( __( 'Sorry, the decoded data is not an array', 'tribe-ext-settings-import-export' ) );
			}

			if ( update_option( 'tribe_events_calendar_options', $settings ) ) {
				$action = 'import_success';
			} else {
				$action = 'import_failed';
			}

			wp_safe_redirect( admin_url( 'edit.php?post_type=tribe_events&page=tribe_import_export&action=' . $action ) );
			exit;
		}

		/**
		 * Reset Modern Tribe calendar and ticketing plugins
		 */
		function tribe_sie_process_settings_reset() {

			// Bail if no action
			if ( empty( $_POST['tribe_sie_action'] ) || 'reset_settings' != $_POST['tribe_sie_action'] ) {
				return;
			}

			// Bail if no nonce
			if ( ! wp_verify_nonce( $_POST['tribe_sie_import_nonce'], 'tribe_sie_import_nonce' ) ) {
				return;
			}

			// Bail if no capability
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Return if not reset
			if ( $_POST['import_reset_confirmation'] != 'reset' ) {
				$action = 'reset_no';
			} // Reset
			elseif ( $_POST['import_reset_confirmation'] == 'reset' ) {
				if ( delete_option( 'tribe_events_calendar_options' ) ) {
					$action = 'reset_success';
				} else {
					$action = 'reset_failed';
				}
			};

			wp_safe_redirect( admin_url( 'edit.php?post_type=tribe_events&page=tribe_import_export&action=' . $action ) );
			exit;
		}
	} // end class
} // end if class_exists check
