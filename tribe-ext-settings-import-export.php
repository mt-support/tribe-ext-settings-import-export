<?php
/**
 * Plugin Name:       The Events Calendar Extension: Settings Import / Export
 * Plugin URI:        https://theeventscalendar.com/extensions/settings-import-export/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-settings-import-export
 * Description:       You can import and export the settings of The Events Calendar
 * Version:           0.9.0
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

use Tribe__Autoloader;
use Tribe__Dependency;
use Tribe__Extension;

/**
 * Define Constants
 */

if ( ! defined( __NAMESPACE__ . '\NS' ) ) {
	define( __NAMESPACE__ . '\NS', __NAMESPACE__ . '\\' );
}

if ( ! defined( NS . 'PLUGIN_TEXT_DOMAIN' ) ) {
	// `Tribe\Extensions\Settings_Import_Export\'PLUGIN_TEXT_DOMAIN'` is defined
	define( NS . 'PLUGIN_TEXT_DOMAIN', 'tribe-ext-settings-import-export' );
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
		 * @var Tribe__Autoloader
		 */
		private $class_loader;

		/**
		 * @var Settings
         *
         * @todo Delete. Probably not needed as there are no settings.
		 */
		private $settings;

		/**
		 * Custom options prefix (without trailing underscore).
		 *
		 * Should leave blank unless you want to set it to something custom, such as if migrated from old extension.
         *
         * @todo Delete. Probably not needed as there are no options.
		 */
		private $opts_prefix = '';

		/**
		 * Is Events Calendar PRO active. If yes, we will add some extra functionality.
		 *
		 * @return bool
         *
         * @todo Delete. Probably not needed as there are no plugin requirements.
		 */
		public $ecp_active = false;

		/**
		 * Setup the Extension's properties.
		 *
		 * This always executes even if the required plugins are not present.
		 */
		public function construct() {

		    // @todo Delete. Probably not needed.
			// Requirements and other properties such as the extension homepage can be defined here.

			/**
			 * Examples:
			 * All these version numbers are the ones on or after November 16, 2016, but you could remove the version
			 * number, as it's an optional parameter. Know that your extension code will not run at all (we won't even
			 * get this far) if you are not running The Events Calendar 4.3.3+ or Event Tickets 4.3.3+, as that is where
			 * the Tribe__Extension class exists, which is what we are extending.
			 */
			// $this->add_required_plugin( 'Tribe__Tickets__Main', '4.3.3' );
			// $this->add_required_plugin( 'Tribe__Tickets_Plus__Main', '4.3.3' );
			// $this->add_required_plugin( 'Tribe__Events__Main', '4.3.3' );
			// $this->add_required_plugin( 'Tribe__Events__Pro__Main', '4.3.3' );
			// $this->add_required_plugin( 'Tribe__Events__Community__Main', '4.3.2' );
			// $this->add_required_plugin( 'Tribe__Events__Community__Tickets__Main', '4.3.2' );
			// $this->add_required_plugin( 'Tribe__Events__Filterbar__View', '4.3.3' );
			// $this->add_required_plugin( 'Tribe__Events__Tickets__Eventbrite__Main', '4.3.2' );
			// $this->add_required_plugin( 'Tribe_APM', '4.4' );

			// Conditionally-require Events Calendar PRO. If it is active, run an extra bit of code.
			//add_action( 'tribe_plugins_loaded', [ $this, 'detect_tec_pro' ], 0 );
		}

		/**
		 * Check required plugins after all Tribe plugins have loaded.
		 *
		 * Useful for conditionally-requiring a Tribe plugin, whether to add extra functionality
		 * or require a certain version but only if it is active.
		 */
		public function detect_tec_pro() {
			if ( Tribe__Dependency::instance()->is_plugin_active( 'Tribe__Events__Pro__Main' ) ) {
				$this->add_required_plugin( 'Tribe__Events__Pro__Main', '4.3.3' );
				$this->ecp_active = true;
			}
		}

		/**
		 * Get Settings instance.
		 *
		 * @return Settings
         *
         * @todo Delete. Probably not needed as there are no settings.
		 */
		private function get_settings() {
			if ( empty( $this->settings ) ) {
				$this->settings = new Settings( $this->opts_prefix );
			}

			return $this->settings;
		}

		/**
		 * Extension initialization and hooks.
		 */
		public function init() {
			// Load plugin textdomain
			// Don't forget to generate the 'languages/tribe-ext-settings-import-export.pot' file
			load_plugin_textdomain( 'PLUGIN_TEXT_DOMAIN', false, basename( dirname( __FILE__ ) ) . '/languages/' );

			if ( ! $this->php_version_check() ) {
				return;
			}

			$this->class_loader();

			// @todo Delete. Probably not needed as there are no settings.
			$this->get_settings();

			// Insert filters and hooks here
			//add_filter( 'thing_we_are_filtering', [ $this, 'my_custom_function' ] );
			add_action( 'admin_menu', array( $this, 'tribe_settings_menu' ), 99 );
            add_action( 'admin_init', array( $this, 'tribe_sie_process_settings_export' ) );
			add_action( 'admin_init', array( $this, 'tribe_sie_process_settings_import' ) );
			add_action( 'admin_init', array( $this, 'tribe_sie_process_settings_reset' ) );
		}

		/**
		 * Check if we have a sufficient version of PHP. Admin notice if we don't and user should see it.
		 *
		 * @link https://theeventscalendar.com/knowledgebase/php-version-requirement-changes/ All extensions require PHP 5.6+.
		 *
		 * Delete this paragraph and the non-applicable comments below.
		 * Make sure to match the readme.txt header.
		 *
		 * Note that older version syntax errors may still throw fatals even
		 * if you implement this PHP version checking so QA it at least once.
		 *
		 * @link https://secure.php.net/manual/en/migration56.new-features.php
		 * 5.6: Variadic Functions, Argument Unpacking, and Constant Expressions
		 *
		 * @link https://secure.php.net/manual/en/migration70.new-features.php
		 * 7.0: Return Types, Scalar Type Hints, Spaceship Operator, Constant Arrays Using define(), Anonymous Classes, intdiv(), and preg_replace_callback_array()
		 *
		 * @link https://secure.php.net/manual/en/migration71.new-features.php
		 * 7.1: Class Constant Visibility, Nullable Types, Multiple Exceptions per Catch Block, `iterable` Pseudo-Type, and Negative String Offsets
		 *
		 * @link https://secure.php.net/manual/en/migration72.new-features.php
		 * 7.2: `object` Parameter and Covariant Return Typing, Abstract Function Override, and Allow Trailing Comma for Grouped Namespaces
		 *
		 * @return bool
		 */
		private function php_version_check() {
			$php_required_version = '5.6';

			if ( version_compare( PHP_VERSION, $php_required_version, '<' ) ) {
				if (
					is_admin()
					&& current_user_can( 'activate_plugins' )
				) {
					$message = '<p>';
					$message .= sprintf( __( '%s requires PHP version %s or newer to work. Please contact your website host and inquire about updating PHP.', 'PLUGIN_TEXT_DOMAIN' ), $this->get_name(), $php_required_version );
					$message .= sprintf( ' <a href="%1$s">%1$s</a>', 'https://wordpress.org/about/requirements/' );
					$message .= '</p>';
					tribe_notice( 'PLUGIN_TEXT_DOMAIN' . '-php-version', $message, [ 'type' => 'error' ] );
				}
				return false;
			}
			return true;
		}

		/**
		 * Use Tribe Autoloader for all class files within this namespace in the 'src' directory.
		 *
		 * TODO: Delete this method and its usage throughout this file if there is no `src` directory, such as if there are no settings being added to the admin UI.
		 *
		 * @return Tribe__Autoloader
		 */
		public function class_loader() {
			if ( empty( $this->class_loader ) ) {
				$this->class_loader = new Tribe__Autoloader;
				$this->class_loader->set_dir_separator( '\\' );
				$this->class_loader->register_prefix(
					NS,
					__DIR__ . DIRECTORY_SEPARATOR . 'src'
				);
			}

			$this->class_loader->register_autoloader();

			return $this->class_loader;
		}

		/**
		 * Register the settings page
		 */
		function tribe_settings_menu() {
			//add_options_page( __( 'Sample Settings Import and Export' ), __( 'Sample Settings Import and Export' ), 'manage_options', 'tribe-settings', 'tribe_settings_page' );
			add_submenu_page( 'edit.php?post_type=tribe_events', __( 'Settings Import / Export', 'PLUGIN_TEXT_DOMAIN' ), __( 'Settings Import / Export', 'PLUGIN_TEXT_DOMAIN' ), 'manage_options', 'tribe_import_export', array( $this, 'tribe_sie_settings_page' ) );
		}

		/**
		 * Render the settings page
		 */
		function tribe_sie_settings_page() {
		    ?>
			<div class="wrap">
				<h2><?php _e( 'Settings Import / Export', 'PLUGIN_TEXT_DOMAIN' ); ?></h2>

                <?php
                // Success and error messages
                if ( ! empty( $_GET['action'] ) ) {

                    if ( $_GET['action'] == 'import_success' ) {
	                    $msg = __( 'Settings imported.', 'PLUGIN_TEXT_DOMAIN' );
	                    $notice_class = 'notice-success ';
                    }
                    elseif ( $_GET['action'] == 'reset_success' ) {
	                    $msg = __( 'Reset successful.', 'PLUGIN_TEXT_DOMAIN' );
	                    $notice_class = 'notice-success ';
                    }
                    elseif ( $_GET['action'] == 'reset_failed' ) {
                        $msg = __( 'Settings reset failed.', 'PLUGIN_TEXT_DOMAIN' );
                        $notice_class = 'notice-error ';
                        }
                    elseif ( $_GET['action'] == 'reset_no' ) {
	                    $msg = __( 'Reset failed. Please enter "reset" in the text field to reset the settings.', 'PLUGIN_TEXT_DOMAIN' );
	                    $notice_class = 'notice-error ';
                    }

	                echo '<div class="notice ' . $notice_class . ' is-dismissible"><p>' . $msg . '</p></div>';
                } ?>

				<div class="metabox-holder">
					<div class="postbox">
						<h3><span><?php _e( 'Export Settings', 'PLUGIN_TEXT_DOMAIN' ); ?></span></h3>
						<div class="inside">
							<p><?php _e( 'Export the setting of The Events Calendar, Event Tickets and add-ons for this site as a .json file. This allows you to easily import the configuration into another site.', 'PLUGIN_TEXT_DOMAIN' ); ?></p>
							<form method="post">
								<p><input type="hidden" name="tribe_sie_action" value="export_settings" /></p>
								<p>
									<?php wp_nonce_field( 'tribe_sie_export_nonce', 'tribe_sie_export_nonce' ); ?>
									<?php submit_button( __( 'Export', 'PLUGIN_TEXT_DOMAIN' ), 'secondary', 'submit', false ); ?>
								</p>
							</form>
						</div><!-- .inside -->
					</div><!-- .postbox -->

					<div class="postbox">
						<h3><span><?php _e( 'Import Settings', 'PLUGIN_TEXT_DOMAIN' ); ?></span></h3>
						<div class="inside">
							<p><?php _e( 'Import the plugin settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.', 'PLUGIN_TEXT_DOMAIN' ); ?></p>
							<form method="post" enctype="multipart/form-data">
								<p>
									<input type="file" name="import_file"/>
								</p>
								<p>
									<input type="hidden" name="tribe_sie_action" value="import_settings" />
									<?php wp_nonce_field( 'tribe_sie_import_nonce', 'tribe_sie_import_nonce' ); ?>
									<?php submit_button( __( 'Import', 'PLUGIN_TEXT_DOMAIN' ), 'secondary', 'submit', false ); ?>
								</p>
							</form>
						</div><!-- .inside -->
					</div><!-- .postbox -->

                    <div class="postbox">
                        <h3><span><?php _e( 'Delete / Reset Settings', 'PLUGIN_TEXT_DOMAIN' ); ?></span></h3>
                        <div class="inside">
                            <p><?php _e( 'Reset the plugin settings.', 'PLUGIN_TEXT_DOMAIN'); ?></p>
                            <p style="font-weight: bold";><?php _e( 'Please note the following:', '' )?></p>
                            <ul style="list-style: disc inside">
                                <li><?php _e( 'This operation <span style="text-decoration: underline;">cannot be reversed</span>. It is recommended that you create a backup of your database first.', 'PLUGIN_TEXT_DOMAIN' ); ?></li>
                                <li><?php _e( 'This operation will <span style="text-decoration: underline;">not</span> delete any event, venue, organizer, or ticket related data.', 'PLUGIN_TEXT_DOMAIN' ); ?></li>
                                <li style="font-weight: bold;"><?php _e( 'Modern Tribe takes no responsibility for lost data.', 'PLUGIN_TEXT_DOMAIN' ); ?></li>
                            </ul>
                            <form method="post" enctype="multipart/form-data">
                                <p>
                                    <input type="text" name="import_reset_confirmation"/><br/>
                                    <?php _e( 'Enter "reset" into the above field if you would like to reset the settings.', 'PLUGIN_TEXT_DOMAIN' ); ?>
                                </p>
                                <p>
                                    <input type="hidden" name="tribe_sie_action" value="reset_settings" />
									<?php wp_nonce_field( 'tribe_sie_import_nonce', 'tribe_sie_import_nonce' ); ?>
									<?php submit_button( __( 'Reset', 'PLUGIN_TEXT_DOMAIN' ), 'secondary', 'submit', false ); ?>
                                </p>
                            </form>
                        </div><!-- .inside -->
                    </div><!-- .postbox -->
				</div><!-- .metabox-holder -->

			</div><!--end .wrap-->

			<?php
		}

		/**
		 * Process a settings export that generates a .json file of the shop settings
		 */
		function tribe_sie_process_settings_export() {
			if( empty( $_POST['tribe_sie_action'] ) || 'export_settings' != $_POST['tribe_sie_action'] )
				return;
			if( ! wp_verify_nonce( $_POST['tribe_sie_export_nonce'], 'tribe_sie_export_nonce' ) )
				return;
			if( ! current_user_can( 'manage_options' ) )
				return;
			$settings = get_option( 'tribe_events_calendar_options' );
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

			if( empty( $_POST['tribe_sie_action'] ) || 'import_settings' != $_POST['tribe_sie_action'] ) {
				return;
			}

			if( ! wp_verify_nonce( $_POST['tribe_sie_import_nonce'], 'tribe_sie_import_nonce' ) ) {
				return;
			}

			if( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$import_file = $_FILES['import_file']['tmp_name'];
			if( empty( $import_file ) ) {
				wp_die( __( 'Please upload a file to import.', 'PLUGIN_TEXT_DOMAIN' ) );
			}

			if ( ! empty( $_FILES['import_file']['name'] ) ) {
				$extension = end( explode( '.', $_FILES['import_file']['name'] ) );
			}

			if( ! isset ( $extension ) || $extension != 'json' ) {
				wp_die( __( 'Please upload a valid .json file.', 'PLUGIN_TEXT_DOMAIN' ) );
			}

			// Retrieve the settings from the file and convert the json object to an array.
			$settings = json_decode( file_get_contents( $import_file ), true );

			$action = '';
            if ( update_option( 'tribe_events_calendar_options', $settings ) ) {
                $action = 'import_success';
            };

			wp_safe_redirect( admin_url( 'edit.php?post_type=tribe_events&page=tribe_import_export&action=' . $action ) ); exit;
		}

		/**
		 * Reset Modern Tribe calendar and ticketing plugins
		 */
		function tribe_sie_process_settings_reset() {

		    // Bail if no action
			if( empty( $_POST['tribe_sie_action'] ) || 'reset_settings' != $_POST['tribe_sie_action'] ) {
				return;
			}

			// Bail if no nonce
			if( ! wp_verify_nonce( $_POST['tribe_sie_import_nonce'], 'tribe_sie_import_nonce' ) ) {
				return;
			}

			// Bail if no capability
			if( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Return is not reset
            if ( $_POST['import_reset_confirmation'] != 'reset' ) {
                $action = 'reset_no';
            }
            // Reset
            elseif ( $_POST['import_reset_confirmation'] == 'reset' ) {
			    if ( delete_option( 'tribe_events_calendar_options' ) ) {
				    $action = 'reset_success';
			    }
			    else {
			        $action = 'reset_failed';
                }
            };

			wp_safe_redirect( admin_url( 'edit.php?post_type=tribe_events&page=tribe_import_export&action=' . $action ) ); exit;
		}
	} // end class
} // end if class_exists check
