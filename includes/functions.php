<?php
/**
 * Invitation Functionality
 *
 * @package BadgeOS
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Register bp-invite-codes Post Type
 *
 * @since  1.0.0
 */
function bp_invite_codes_register_post_type() {

	register_post_type( 'bp-invite-codes', array(
		'labels' => array(
			'name' => __( 'Invite Code', 'bp-invite-codes' ),
			'singular_name' => __( 'Invite Code', 'bp-invite-codes' ),
			'add_new' => __( 'Add New Invite Code', 'bp-invite-codes' ),
			'add_new_item' => __( 'Add New Invite Code', 'bp-invite-codes' ),
			'edit_item' => __( 'Edit Invite Code', 'bp-invite-codes' ),
			'new_item' => __( 'New Invite Code', 'bp-invite-codes' ),
			'all_items' => __( 'All Invite Codes', 'bp-invite-codes' ),
			'view_item' => __( 'View Invite Code', 'bp-invite-codes' ),
			'search_items' => __( 'Search Invite Codes', 'bp-invite-codes' ),
			'not_found' => __( 'No Invite Codes found', 'bp-invite-codes' ),
			'not_found_in_trash' => __( 'No Invite Codes found in Trash', 'bp-invite-codes' ),
			'menu_name' => __( 'BP Invite Codes', 'bp-invite-codes' ),
		),
		'public' => false,
		'publicly_queryable' => false,
		'show_ui' => true,
		'show_in_menu' => 'bp_invite_codes_settings',
		'query_var' => true,
		'rewrite' => array( 'slug' => 'bp-invite-codes' ),
		'has_archive' => false,
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array( 'title', 'excerpt' )
	) );

}
add_action( 'init', 'bp_invite_codes_register_post_type' );

/**
 * Invite code text box on BuddyPress registration page.
 *
 * @since  1.0.0
 */
function bp_invite_codes_bp_after_signup_profile_fields() {

	$entered_code = '';

	if ( isset( $_POST[ 'bp_invite_code' ] ) ) {
		$entered_code = $_POST[ 'bp_invite_code' ];
	}

	$require_code = get_option( 'bp_invite_codes_require_code' );

	if ( !in_array( $require_code, array( 'Yes', 'No' ) ) ) {
		$require_code = 'Yes';
	}
?>
	<div class="register-section">
		<div class="editfield">
			<label for="bp_invite_code">
				<?php
					_e( 'Invitation Code', 'bp-invite-codes' );

					if ( 'Yes' == $require_code ) {
						echo ' ' . __( '(required)', 'buddypress' );
					}
				?>
			</label>

			<?php do_action( 'bp_invite_codes_errors' ); ?>

			<input type="text" name="bp_invite_code" id="bp_invite_code" value="<?php echo esc_attr( $entered_code ); ?>" />
		</div>
	</div>
<?php

}
add_action( 'bp_before_account_details_fields', 'bp_invite_codes_bp_after_signup_profile_fields' );

/**
 * Validate registration page invite code
 *
 * @since  1.0.0
 */
function bp_invite_codes_bp_core_screen_signup() {

	global $bp;

	$require_code = get_option( 'bp_invite_codes_require_code' );

	if ( !in_array( $require_code, array( 'Yes', 'No' ) ) ) {
		$require_code = 'Yes';
	}

	if ( empty( $_POST[ 'bp_invite_code' ] ) && 'Yes' == $require_code ) {
		$bp->signup->errors[ 'bp_invite_codes_error' ] = __( 'An invitation code is required to register, please enter one.', 'bp-invite-codes' );
	}
	else {
		$message = bp_invite_codes_get_code( null, $_POST[ 'bp_invite_code' ] );

		if ( 'join' != $message ) {
			$bp->signup->errors[ 'bp_invite_codes_error' ] = $message;
		}
	}

	// Use custom hook before invite code textbox on registration page
	add_action( 'bp_invite_codes_errors', 'bp_invite_codes_errors_message' );

}
add_action( 'bp_signup_validate', 'bp_invite_codes_bp_core_screen_signup' );

/**
 * Display error message if user entered an inlaid code or if an invite code is required.
 *
 * @since  1.0.0
 */
function bp_invite_codes_errors_message() {

	global $bp;

	if ( $bp->signup->errors[ 'bp_invite_codes_error' ] ) {
		echo '<div class="error">' . $bp->signup->errors[ 'bp_invite_codes_error' ] . '</div>';
	}

}

/**
 * Process a new user that has gotten past the invite code validation.
 *
 * @since  1.0.0
 */
function bp_invite_codes_bp_core_signup_user( $user_id, $user_login, $user_password, $user_email, $usermeta ) {

	if ( isset( $_POST[ 'bp_invite_code' ] ) && !empty( $_POST[ 'bp_invite_code' ] ) ) {
		// New User
		if ( !empty( $user_id ) ) {
			bp_invites_code_join( $_POST[ 'bp_invite_code' ], 0, $user_id );

			setcookie( 'bp_invite_code', '', time() + 60 * 60 * 72, COOKIEPATH );
		}
		// Multisite activation
		else {
			setcookie( 'bp_invite_code', $_POST[ 'bp_invite_code' ], time() + 60 * 60 * 72, COOKIEPATH );
		}
	}

}
add_action( 'bp_core_signup_user', 'bp_invite_codes_bp_core_signup_user', 1, 5 );

/**
 * Process a new user activation if invite code present.
 *
 * @since  1.1.0
 */
function bp_invite_codes_bp_core_activate_user( $user_id ) {

	if ( isset( $_COOKIE[ 'bp_invite_code' ] ) ) {
		bp_invites_code_join( $_COOKIE[ 'bp_invite_code' ], 0, $user_id );

		setcookie( 'bp_invite_code', '', time() + 60 * 60 * 72, COOKIEPATH );
	}

}
add_action( 'wpmu_activate_user', 'bp_invite_codes_bp_core_activate_user', 10, 1 );

/**
 * Process a new user activation if invite code present.
 *
 * @since  1.1.0
 */
function bp_invite_codes_bp_core_activate_blog( $blog_id, $user_id ) {

	if ( isset( $_COOKIE[ 'bp_invite_code' ] ) ) {
		bp_invites_code_join( $_COOKIE[ 'bp_invite_code' ], 0, $user_id );

		setcookie( 'bp_invite_code', '', time() + 60 * 60 * 72, COOKIEPATH );
	}

}
add_action( 'wpmu_activate_blog', 'bp_invite_codes_bp_core_activate_blog', 10, 2 );

/**
 * Checks if an invite code is already being used
 * (Site and Group Admins can only use unique invite codes)
 *
 * @since  1.0.0
 * @return bool|int Whether the code is already in use, or if $return_id then the matching Post ID (if found)
 */
function bp_invite_codes_check_code( $code, $exclude_post_id = 0, $return_id = false ) {

	global $wpdb;

	if ( !empty( $code ) ) {
		if ( $exclude_post_id ) {
			$existing_post = $wpdb->get_var( $wpdb->prepare( "
				SELECT post_id
				FROM   $wpdb->postmeta
				WHERE  meta_key = '_bp_invite_codes_code'
					   AND post_id != %d
					   AND meta_value = %s
				", $exclude_post_id, $code ) );
		}
		else {
			$existing_post = $wpdb->get_var( $wpdb->prepare( "
				SELECT post_id
				FROM   $wpdb->postmeta
				WHERE  meta_key = '_bp_invite_codes_code'
					   AND meta_value = %s
				", $code ) );
		}

		if ( $existing_post ) {
			if ( $return_id ) {
				return (int) $existing_post;
			}

			return true;
		}
	}

	return false;

}

/**
 * Processes an invite code being created or edited by a group admin
 *
 * @since  1.0.0
 */
function bp_invite_codes_group_admin_action( $group_id ) {

	// Grab our submitted codes
	$bp_invite_codes_code = $bp_invite_codes_code_hidden = '';

	if ( isset( $_POST[ 'bp_invite_codes_code' ] ) ) {
		$bp_invite_codes_code = $_POST[ 'bp_invite_codes_code' ];
	}

	if ( isset( $_POST[ 'bp_invite_codes_code_hidden' ] ) ) {
		$bp_invite_codes_code_hidden = $_POST[ 'bp_invite_codes_code_hidden' ];
	}

	// If our codes don't match...
	if ( $bp_invite_codes_code != $bp_invite_codes_code_hidden ) {
		global $bp, $user_ID;

		if ( !$group_id ) {
			$group_id = $bp->groups->current_group->id;
		}

		$code_post_id = groups_get_groupmeta( $group_id, '_bp_invite_codes_post_id' );

		$post_group_ids = get_post_meta( $code_post_id, '_bp_invite_codes_group_id', true );

		if ( empty( $post_group_ids ) ) {
			$post_group_ids = array();
		}
		elseif ( !is_array( $post_group_ids ) ) {
			$post_group_ids = (array) $post_group_ids;

			$post_group_ids = array_map( 'absint', $post_group_ids );
		}

		$selected_group_ids = get_post_meta( $code_post_id, '_bp_invite_codes_groups', true );

		if ( empty( $selected_group_ids ) ) {
			$selected_group_ids = array();
		}
		elseif ( !is_array( $selected_group_ids ) ) {
			$selected_group_ids = (array) $selected_group_ids;

			$selected_group_ids = array_map( 'absint', $selected_group_ids );
		}

		// Delete invite code meta if form code is blank
		if ( empty( $bp_invite_codes_code ) ) {
			// Unset main group
			if ( in_array( $group_id, $post_group_ids ) ) {
				$post_group_ids = array_diff( $post_group_ids, array( $group_id ) );

				update_post_meta( $code_post_id, '_bp_invite_codes_group_id', $post_group_ids );
			}

			// Remove from group list
			if ( in_array( $group_id, $selected_group_ids ) ) {
				$selected_group_ids = array_diff( $selected_group_ids, array( $group_id ) );

				update_post_meta( $code_post_id, '_bp_invite_codes_groups', $selected_group_ids );
			}

			groups_delete_groupmeta( $group_id, '_bp_invite_codes_post_id' );

			return;
		}

		$upost = array(
			'post_status' => 'publish',
			'post_author' => $user_ID,
			'post_type' => 'bp-invite-codes'
		);

		$code_post = false;

		if ( $code_post_id ) {
			$code_post = get_post( $code_post_id );
		}

		// Don't update *old invite code* if it's used by other groups
		if ( $code_post_id && $code_post && !empty( $selected_group_ids ) && ( !in_array( $group_id, $selected_group_ids ) || 1 < count( $selected_group_ids ) ) ) {
			$code_post_id = 0;
			$code_post = false;
		}
		else {
			$upost[ 'post_title' ] = sprintf( __( 'Group Generated: %s', 'bp-invite-codes' ), $bp->groups->current_group->name );
		}

		// Update Code
		if ( $code_post_id && $code_post ) {
			$upost = array_merge( $upost, array( 'ID' => $code_post_id ) );

			wp_update_post( $upost );
		}
		// Create Code
		else {
			$code_post_id = wp_insert_post( $upost );
		}

		// Update main group
		if ( !in_array( $group_id, $post_group_ids ) ) {
			$post_group_ids[] = $group_id;

			update_post_meta( $code_post_id, '_bp_invite_codes_group_id', $post_group_ids );
		}

		// Update group list
		if ( !in_array( $group_id, $selected_group_ids ) ) {
			$selected_group_ids[] = $group_id;

			update_post_meta( $code_post_id, '_bp_invite_codes_groups', $selected_group_ids );
		}

		groups_update_groupmeta( $group_id, '_bp_invite_codes_post_id', $code_post_id );

		// check if code is being used already
		if ( bp_invite_codes_check_code( $bp_invite_codes_code, $code_post_id ) ) {
			$new_bp_invite_codes_code = wp_generate_password( 7, false );

			while ( bp_invite_codes_check_code( $new_bp_invite_codes_code, $code_post_id ) ) {
				$new_bp_invite_codes_code = wp_generate_password( 7, false );
			}

			update_post_meta( $code_post_id, '_bp_invite_codes_code', $new_bp_invite_codes_code );

			$message = sprintf(
				__( 'The invitation code %1$s is already being used, it has been changed to %2$s.', 'bp-invite-codes' ),
				'<b>' . esc_html( $bp_invite_codes_code ) . '</b>',
				'<b>' . esc_html( $new_bp_invite_codes_code ) . '</b>'
			);

			bp_core_add_message( $message, 'error' );
		}
		else {
			update_post_meta( $code_post_id, '_bp_invite_codes_code', $bp_invite_codes_code );
		}

	}
}
add_action( 'groups_create_group_step_save_group-settings', 'bp_invite_codes_group_admin_action' ); // Creating a new group
add_action( 'groups_group_settings_edited', 'bp_invite_codes_group_admin_action' ); // Editing an existing group

/**
 * Adds invite code text box to group admin settings page/group creation page
 *
 * @since  1.0.0
 */
function bp_invite_codes_bp_after_group_settings_admin() {

	global $bp;

	$code = bp_invite_codes_get_code( $bp->groups->current_group->id, null, 'code' );
?>
	<h4><?php _e( 'Require invitation code to join this Group', 'bp-invite-codes' ); ?></h4>

	<p><?php _e( 'When set, members will be required to enter this code before they may join this group:', 'bp-invite-codes' ); ?></p>

	<p><input type="text" id="bp_invite_codes_code" name="bp_invite_codes_code" value="<?php echo $code; ?>" /></p>

	<input type="hidden" id="bp_invite_codes_code_hidden" name="bp_invite_codes_code_hidden" value="<?php echo $code; ?>" />
<?php

}
add_action( 'bp_after_group_settings_admin', 'bp_invite_codes_bp_after_group_settings_admin' );
add_action( 'bp_after_group_settings_creation_step', 'bp_invite_codes_bp_after_group_settings_admin' );

/**
 * Attempt to join group(s) based on invite code
 *
 * @since  1.1.0
 */
function bp_invites_code_join( $code, $group_id = 0, $user_id = 0 ) {

	$return = bp_invite_codes_get_code( $group_id, $code, 'invite_id' );

	if ( is_int( $return ) ) {
		$code_post_id = $return;
		$return = 'join';

		$invites_used = (int) get_post_meta( $code_post_id, '_bp_invite_codes_used', true );
		$invites_used++;

		if ( $user_id ) {
			// get group ids attached to current code and add user to them (this will only happen on registration as group_id is null).
			if ( $group_id ) {
				$group_ids = implode( ',', $group_id );

				if ( bp_has_groups( 'per_page=1000&include=' . $group_ids ) ) {
					while ( bp_groups() ) {
						bp_the_group();

						groups_join_group( bp_get_group_id(), $user_id );
					}
				}
			}
			else {
				$group_ids = get_post_meta( $code_post_id, '_bp_invite_codes_groups', true );

				if ( empty( $group_ids ) ) {
					$group_ids = array();
				}
				elseif ( !is_array( $group_ids ) ) {
					$group_ids = (array) $group_ids;
				}

				$group_ids = array_map( 'absint', $group_ids );

				// add default groups to join if any
				$bp_invite_codes_default_bp_groups = get_option( 'bp_invite_codes_default_bp_groups' );

				if ( empty( $bp_invite_codes_default_bp_groups ) ) {
					$bp_invite_codes_default_bp_groups = array();
				}
				elseif ( !is_array( $bp_invite_codes_default_bp_groups ) ) {
					$bp_invite_codes_default_bp_groups = (array) $bp_invite_codes_default_bp_groups;
				}

				$bp_invite_codes_default_bp_groups = array_map( 'absint', $bp_invite_codes_default_bp_groups );

				$group_ids = array_merge( $group_ids, $bp_invite_codes_default_bp_groups );
				$group_ids = array_unique( $group_ids );

				// get group_id if code only set by group admin
				$post_group_ids = get_post_meta( $code_post_id, '_bp_invite_codes_group_id', true );

				if ( empty( $post_group_ids ) ) {
					$post_group_ids = array();
				}
				elseif ( !is_array( $post_group_ids ) ) {
					$post_group_ids = (array) $post_group_ids;
				}

				$post_group_ids = array_map( 'absint', $post_group_ids );

				if ( !empty( $post_group_ids ) ) {
					$group_ids = array_merge( $group_ids, $post_group_ids );
					$group_ids = array_unique( $group_ids );
				}

				// Loop groups and assigns user to each
				if ( !empty( $group_ids ) ) {
					$group_ids = implode( ',', $group_ids );

					if ( bp_has_groups( 'per_page=1000&include=' . $group_ids ) ) {
						while ( bp_groups() ) {
							bp_the_group();

							groups_join_group( bp_get_group_id(), $user_id );
						}
					}
				}
			}
		}

		// update number of times this code has been used
		update_post_meta( $code_post_id, '_bp_invite_codes_used', $invites_used );
	}

	return $return;

}

/**
 * Return invite code by group and/or validate an actual code against an entered code
 *
 * @since  1.0.0
 */
function bp_invite_codes_get_code( $group_id = null, $entered_code = null, $return = null ) {

	$code_post_id = false;

	// get code from group
	if ( $group_id ) {
		$code_post_id = (int) groups_get_groupmeta( $group_id, '_bp_invite_codes_post_id' );

		if ( $code_post_id ) {
			$code = get_post_meta( $code_post_id, '_bp_invite_codes_code', true );

			if ( 'code' == $return ) {
				return $code;
			}
			elseif ( $code != $entered_code ) {
				return sprintf( __( 'The code you entered, %s, does not match the group invite code.', 'bp-invite-codes' ), $entered_code );
			}
		}
		elseif ( 'code' == $return ) {
			return '';
		}
		else {
			return sprintf( __( 'The code you entered, %s, is invalid.', 'bp-invite-codes' ), $entered_code );
		}
	}
	// empty code
	elseif ( empty( $entered_code ) && 'code' != $return ) {
		return __( 'Please enter a code.', 'bp-invite-codes' );
	}
	// get matching post
	elseif ( !empty( $entered_code ) ) {
		$code_post_id = bp_invite_codes_check_code( $entered_code, 0, true );
	}

	// return code
	if ( 'code' == $return ) {
		$return = $entered_code;

		// No match
		if ( !$code_post_id ) {
			$return = '';
		}
	}
	// check if entered code is valid
	elseif ( !$code_post_id ) {
		$return = sprintf( __( 'The code you entered, %s, is invalid.', 'bp-invite-codes' ), $entered_code );
	}
	// check if entered code can be used
	else {
		$invite_limit = (int) get_post_meta( $code_post_id, '_bp_invite_codes_limit', true );
		$invite_expiration = strtotime( get_post_meta( $code_post_id, '_bp_invite_codes_expiration', true ) );
		$invites_used = (int) get_post_meta( $code_post_id, '_bp_invite_codes_used', true );

		if ( 0 < $invite_limit && $invite_limit <= $invites_used  ) {
			$return = sprintf( __( 'The code you entered, %s, is maxed out.', 'bp-invite-codes' ), $entered_code );
		}
		elseif ( 0 < $invite_expiration && $invite_expiration <= time() ) {
			$return = sprintf( __( 'The code you entered, %s, has expired.', 'bp-invite-codes' ), $entered_code );
		}
		elseif ( 'invite_id' == $return ) {
			$return = $code_post_id;
		}
		else {
			$return = true;
		}
	}

	return $return;

}

/**
 * Filter the group join button. Nulls out the link so we can hijack it with jquery to prompt for an invite code.
 *
 * @since  1.0.0
 */
function bp_invite_codes_bp_get_group_join_button( $button ) {

	if ( 'join_group' == $button[ 'id' ] || 'request_membership' == $button[ 'id' ] ) {
		// Get Group ID from $button array
		$group_id = $button[ 'wrapper_id' ];
		$group_id = str_replace( 'groupbutton-', '', $group_id );

		// Check if group needs an invite code
		if ( bp_invite_codes_get_code( $group_id, null, 'code' ) ) {
			// echo $code; // Debug
			$button[ 'link_class' ] = $button[ 'link_class' ] . ' class_bp_invite_codes ' . $group_id;

			echo '<input type="hidden" id="link_href_' . $group_id . '" value="' . $button[ 'link_href' ] . '" />';

			$button[ 'link_href' ] = null;
		}
	}

	return $button;

}
add_filter( 'bp_get_group_join_button', 'bp_invite_codes_bp_get_group_join_button', 1, 1 );

/**
 * AJAX Action for clicking Group Join Button
 *
 * @since  1.0.0
 */
function bp_invite_codes_bp_get_group_join_button_ajax_action() {

	$entered_code = isset( $_REQUEST[ 'entered_code' ] ) ? $_REQUEST[ 'entered_code' ] : false;
	$group_id = isset( $_REQUEST[ 'group_id' ] ) ? $_REQUEST[ 'group_id' ] : false;

	$message = bp_invites_code_join( $entered_code, $group_id );

	if ( 'join' != $message ) {
		wp_send_json_error( $message );
	}

	wp_send_json_success( $message );

}
add_action( 'wp_ajax_bp_invite_codes_bp_get_group_join_button', 'bp_invite_codes_bp_get_group_join_button_ajax_action' );
add_action( 'wp_ajax_nopriv_bp_invite_codes_bp_get_group_join_button', 'bp_invite_codes_bp_get_group_join_button_ajax_action' );