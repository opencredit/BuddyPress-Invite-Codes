<?php
/**
 * Plugin Name: BuddyPress Invite Codes
 * Plugin URI:
 * Description: Add members to groups based on invite codes.
 * Tags: buddypress
 * Author: Credly
 * Version: 1.0.0
 * Author URI: https://credly.com/
 * License: GNU AGPL
 *
 * @package BuddyPress Invite Codes
 * @version 1.0.0
 */

/*
 * Copyright © 2012-2013 Credly, LLC
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Affero General
 * Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>;.
*/

class BuddyPress_Invite_Codes {

	function __construct() {

		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugins_url( 'bp-invite-codes/' );

		// Load translations
		load_plugin_textdomain( 'bp-invite-codes', false, 'bp-invite-codes/languages' );

		// Run our activation
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// If BadgeOS is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
		
		add_action( 'bp_include', array( $this, 'bp_include' ) );

		// Load custom js and css
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 999 );

	}

	/**
	 * Files to include with BuddyPress
	 *
	 * @since 1.0.0
	 */
	public function bp_include() {

		if ( $this->meets_requirements() ) {
			if ( is_admin() )
				require_once( $this->directory_path . '/includes/settings.php' );

			require_once( $this->directory_path . '/includes/functions.php' );
			
		}
	}

	/**
	 * Enqueue custom scripts and styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		// Grab the global BuddyPress object
		global $bp;
		// TODO: Make this work!
		// If we're on a BP group page
		/*if ( isset( $bp->current_component ) && 'groups' == $bp->current_component )
			wp_enqueue_script( 'bp-invite-codes', $this->directory_url . 'js/bp-invites-codes.js', array( 'jquery' ) );*/

		// If need to load css for admin pages
		//if ( is_admin() )
			//wp_enqueue_style( 'bp-invite-codes-admin', $this->directory_url . 'css/admin.css' );
	}

	/**
	 * Activation hook for the plugin.
	 *
	 * @since 1.0.0
	 */
	public function activate() {}

	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS is available, false otherwise
	 */
	public static function meets_requirements() {

		if ( class_exists('BadgeOS') && function_exists('badgeos_get_user_earned_achievement_types') && class_exists('BuddyPress') )
			return true;
		else
			return false;

	}

	/**
	 * Generate a custom error message and deactivates the plugin if we don't meet requirements
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( ! $this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';
				if ( !class_exists('BadgeOS') || !function_exists('badgeos_get_user_earned_achievement_types') )
					echo '<p>' . sprintf( __( 'BuddyPress Invite Codes requires BadgeOS and has been <a href="%s">deactivated</a>. Please install and activate BadgeOS and then reactivate this plugin.', 'badgeos-community' ), admin_url( 'plugins.php' ) ) . '</p>';
				elseif ( !class_exists('BuddyPress') )
					echo '<p>' . sprintf( __( 'BuddyPress Invite Codes requires BuddyPress and has been <a href="%s">deactivated</a>. Please install and activate BuddyPress and then reactivate this plugin.', 'badgeos-community' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}
	}
}
$buddypress_invite_codes = new BuddyPress_Invite_Codes();