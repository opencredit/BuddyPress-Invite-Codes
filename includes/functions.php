<?php
/**
 * Register bp-invite-codes Post Type
 *
 * @since  1.0.0
 */
function bp_invite_codes_register_post_type() {

	$labels = array(
		'name' => 'Invite Code',
		'singular_name' => 'Invite Code',
		'add_new' => 'Add New Invite Code',
		'add_new_item' => 'Add New Invite Code',
		'edit_item' => 'Edit Invite Code',
		'new_item' => 'New Invite Code',
		'all_items' => 'All Invite Codes',
		'view_item' => 'View Invite Code',
		'search_items' => 'Search Invite Codes',
		'not_found' =>  'No Invite Codes found',
		'not_found_in_trash' => 'No Invite Codes found in Trash',
		'menu_name' => 'BP Invite Codes'
	);

	$args = array(
		'labels' => $labels,
		'public' => false,
		'publicly_queryable' => false,
		'show_ui' => true,
		'show_in_menu' => 'bp_invite_codes_settings_menu',
		'query_var' => true,
		'rewrite' => array( 'slug' => 'bp-invite-codes' ),
		'has_archive' => false,
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array( 'title', 'excerpt' )
	);

	register_post_type( 'bp-invite-codes', $args );
	$GLOBALS['bp_invite_codes_post_types'][] = $slug;
}
add_action( 'init', 'bp_invite_codes_register_post_type' );

/**
 * Invite code text box on BuddyPress registration page.
 *
 * @since  1.0.0
 */
function bp_invite_codes_bp_after_signup_profile_fields() {
	$entered_code = $_POST['bp_invite_code'];?>
	<div class="register-section">
	<div class="editfield">
	<label for="bp_invite_code"><?php echo _e( 'Invitation Code', 'bp-invite-codes' ); ?> <?php if ( get_option( 'bp_invite_codes_require_code' ) == 'Yes' ) : ?><?php _e( '(required)', 'buddypress' ); ?><?php endif; ?></label>
	<?php do_action( 'bp_invite_codes_errors' ); ?>
	<input type="text" name="bp_invite_code" id="bp_invite_code" value="<?php echo $entered_code;?>" />
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
	if ( empty( $_POST['bp_invite_code'] ) && get_option( 'bp_invite_codes_require_code' ) == 'Yes' ) {
		$bp->signup->errors['bp_invite_codes_error'] = __( 'An invitation code is required to register, please enter one.', 'bp-invite-codes' );
	}else {
		$message = bp_invites_codes_get_code( NULL, $_POST['bp_invite_code'] );
		if ( 'join' != $message )
			$bp->signup->errors['bp_invite_codes_error'] = $message;
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
	if ( $bp->signup->errors['bp_invite_codes_error'] )
		echo '<div class="error">'. $bp->signup->errors['bp_invite_codes_error'] .'</div>';
}

/**
 * Process a new user that has gotten past the invite code validation.
 *
 * @since  1.0.0
 */
function bp_invite_codes_bp_core_signup_user( $user_id, $user_login, $user_password, $user_email, $usermeta ) {
	if ( !empty( $_POST['bp_invite_code'] ) )
		$message = bp_invites_codes_get_code( NULL, $_POST['bp_invite_code'], NULL, $user_id );
}
add_action( 'bp_core_signup_user', 'bp_invite_codes_bp_core_signup_user', 1, 5 );

/**
 * Checks if an invite code is already being used (Site and Group Admins can only use unique invite codes)
 *
 * @since  1.0.0
 * @return a custom message if the code is already being used
 */
function bp_invite_codes_check_code( $post_id ) {
	global $wpdb;
	$code = get_post_meta( $post_id, '_bp_invite_codes_code', 1 );
	$code_post_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_bp_invite_codes_code' AND meta_value <>'' AND post_id <> %d AND meta_value = %s",
			$post_id,
			$code
		) );
	if ( $code_post_id ) {
		$new_code = wp_generate_password( 7, 0 );
		update_post_meta( $post_id, '_bp_invite_codes_code', $new_code );
		$message = 'The invitation code <b>' . $code . '</b> is already being used, changed invitation code to <b>' . $new_code . '</b>.';
	}
	return $message;
}

/**
 * Processes an invite code being created or edited by a group admin
 *
 * @since  1.0.0
 */
function bp_invite_codes_group_admin_action( $group_id ) {
	$bp_invite_codes_code = $_POST['bp_invite_codes_code'];
	$bp_invite_codes_code_hidden = $_POST['bp_invite_codes_code_hidden'];
	if ( $bp_invite_codes_code != $bp_invite_codes_code_hidden ) {
		global $bp, $user_ID;
		if(!$group_id)
			$group_id = $bp->groups->current_group->id;
		$code_post_id = groups_get_groupmeta( $group_id, '_bp_invite_codes_post_id' );
		// Delete invite code meta if form code is blank
		if ( !$bp_invite_codes_code ) {
			delete_post_meta( $code_post_id, '_bp_invite_codes_code' );
			return;
		}

		$upost = array(
			'post_title'    => 'Group Generated: ' . $bp->groups->current_group->name,
			'post_status'   => 'publish',
			'post_author'   => $user_ID,
			'post_type' => 'bp-invite-codes'
		);
		// Update Code
		if ( $code_post_id && get_post( $code_post_id ) ) {
			$upost = array_merge( $upost, array( 'ID' => $code_post_id ) );
			wp_update_post( $upost );
		// Insert new Code
		} else {
			$code_post_id = wp_insert_post( $upost );
		}
		// Update post and group meta
		update_post_meta( $code_post_id, '_bp_invite_codes_group_id', $group_id );
		groups_update_groupmeta( $group_id, '_bp_invite_codes_post_id', $code_post_id );
		// check if code is being used already
		update_post_meta( $code_post_id, '_bp_invite_codes_code', $bp_invite_codes_code );
		$message = bp_invite_codes_check_code( $code_post_id );
		if ( $message )
			bp_core_add_message( $message, 'error' );

	}
}
// Creating a new group
add_action( 'groups_create_group_step_save_group-settings', 'bp_invite_codes_group_admin_action' );
// Editing an existing group
add_action( 'groups_group_settings_edited', 'bp_invite_codes_group_admin_action' );


/**
 * Adds invite code text box to group admin settings page/group creation page
 *
 * @since  1.0.0
 */
function bp_invite_codes_bp_after_group_settings_admin() {
	global $bp;
	$code = bp_invites_codes_get_code( $bp->groups->current_group->id, NULL, 'code' );?>
	<h4><?php _e( 'Require invitation code to join this group', 'bp-invite-codes' ); ?></h4>
	<p><?php _e( 'When set members will be required to enter this code before they join this group.', 'bp-invite-codes' ); ?></p>
	<p><input type="text" id="bp_invite_codes_code" name="bp_invite_codes_code" value="<?php echo $code;?>"></p>
	<input type="hidden" id="bp_invite_codes_code_hidden" name="bp_invite_codes_code_hidden" value="<?php echo $code;?>">
	<?php
}
add_action( 'bp_after_group_settings_admin', 'bp_invite_codes_bp_after_group_settings_admin' );
add_action( 'bp_after_group_settings_creation_step', 'bp_invite_codes_bp_after_group_settings_admin' );


/**
 * Return invite code by group and/or validate an actual code against an entered code
 *
 * @since  1.0.0
 */
function bp_invites_codes_get_code( $group_id = NULL, $entered_code = NULL, $return = 'message', $user_id = NULL ) {
	//get code from group
	if ( $group_id ) {
		$code_post_id = groups_get_groupmeta( $group_id, '_bp_invite_codes_post_id' );
		if ( $code_post_id )
			$code = get_post_meta( $code_post_id, '_bp_invite_codes_code', 1 );
		//check matches on entered code
	}else {
		global $wpdb;
		$code_post_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_bp_invite_codes_code' AND meta_value <>'' AND meta_value = %s",
				$entered_code
			) );
		if ( $code_post_id )
			$code = get_post_meta( $code_post_id, '_bp_invite_codes_code', 1 );
	}
	// just return the code (used for admins and checking if a group requires a code)
	if ( $code && $return == 'code' ) {
		return $code;
		// code matches
	}elseif ( $code == $entered_code && $code_post_id ) {
		// check if endered code is valid
		$invite_limit=get_post_meta( $code_post_id, '_bp_invite_codes_limit', true );
		$invite_expiration=get_post_meta( $code_post_id, '_bp_invite_codes_expiration', true );
		$invites_used=get_post_meta( $code_post_id, '_bp_invite_codes_used', true );
		if ( $invites_used )
			$invites_used=0;
		if ( is_numeric( $invite_limit ) && $invites_used >= $invite_limit ) {
			$return = __( 'The code you entered "'.$entered_code.'" is maxed out.', 'bp-invite-codes' );
		}elseif ( $invite_expiration &&  date( 'm/d/Y' ) >= $invite_expiration ) {
			$return = __( 'The code you entered "'.$entered_code.'" has expired.', 'bp-invite-codes' );
		}else {
			$return = 'join';
			// get group ids attached to current code and add user to them (this will only happen on registration as group_id is null).
			if ( bp_is_active( 'groups' ) ) {
				if ( !$group_id && $user_id ) {
					$group_ids=get_post_meta( $code_post_id, '_bp_invite_codes_groups', true );
					// add default groups to join if any
					$default_group_ids = get_option( 'bp_invite_codes_default_bp_groups' );
					if ( is_array( $group_ids ) && is_array( $default_group_ids ) ) {
						$group_ids = array_merge( $group_ids, $default_group_ids );
					}elseif ( !is_array( $group_ids ) && is_array( $default_group_ids ) ) {
						$group_ids = $default_group_ids;
					}else{
						$group_ids = array(0);
					}
					// get group_id if code only set by group admin
					$post_group_id = get_post_meta( $code_post_id, '_bp_invite_codes_group_id', 1 );
					if($post_group_id)
						array_push( $group_ids, $post_group_id );
					// Loop groups and ass user to each
					if ( $group_ids && is_array($group_ids) ) {
						$group_ids = explode(',', $group_ids);
						if ( bp_has_groups( "per_page=1000&include=".$group_ids ) ) {
							while ( bp_groups() ) : bp_the_group();
								$group_id = bp_get_group_id();
								groups_join_group( $group_id, $user_id );
							endwhile;
						}
					}
				}
			}
			// update number of times this code has been used
			update_post_meta( $code_post_id, '_bp_invite_codes_used', $invites_used + 1 );
		}
		// code not matching
	}elseif ( $code != $entered_code ) {
		$return = __( 'The code you entered "'.$entered_code.'" is an invalid code.', 'bp-invite-codes' );
	}
	if($return == 'code')
		$return = NULL;
	return $return;
}

/**
 * Filter the group join button. Nulls out the link so we can hijack it with jquery to prompt for an invite code.
 *
 * @since  1.0.0
 */
function bp_invite_codes_bp_get_group_join_button( $button ) {
	if ( $button['id'] == 'join_group' || $button['id'] == 'request_membership' ) {
		// Get Group ID from $button array
		$group_id = $button['wrapper_id'];
		$group_id = str_replace( 'groupbutton-', '', $group_id );
		// Check if group needs an invite code
		$code = bp_invites_codes_get_code( $group_id, NULL, 'code' );
		if ( $code ) {
			echo $code;
			$button['link_class'] = $button['link_class'] . ' class_bp_invite_codes ' . $group_id;
			echo '<input type="hidden" id="link_href_'.$group_id.'" value="'.$button['link_href'].'">';
			$button['link_href'] = NULL;
		}
	}
	return $button;
}
add_filter( 'bp_get_group_join_button', 'bp_invite_codes_bp_get_group_join_button', 1, 1 );

/**
 * JQUERY for group join button if the group needs an invite code to join. 
 *
 * @since  1.0.0
 */
function bp_invite_codes_jquery() { 
	// TODO: check if current bp component is on groups page so we only run this then.
	// TODO: Move to dedicated js file
	global $bp;
	if ( isset( $bp->current_component ) && 'groups' == $bp->current_component ){
		?>
		<script>
		jQuery( document ).ready( function($) {
				jQuery( '.class_bp_invite_codes').click( function() {
					var invite_code = prompt('<?php echo _e( 'You must enter an invite code to join this group.' );?>');
					if(!invite_code)
						return;
					var gid = jQuery(this).parent().attr('id');
					var thelink = jQuery(this);
					gid = gid.split('-');
					gid = gid[1];
					$.ajax({
						url: ajaxurl,
						data: {
							'action':      'bp_invite_codes_bp_get_group_join_button',
							'entered_code':   invite_code,
							'group_id':       gid
						},
						dataType: 'json',
						success: function( response ) {
							if ( response.message == 'join' ) {

								var nonce = $('#link_href_'+gid).val()
								nonce = nonce.split('?_wpnonce=');
								nonce = nonce[1].split('&');
								nonce = nonce[0];

								$.post( ajaxurl, {
									action: 'joinleave_group',
									'cookie': encodeURIComponent(document.cookie),
									'gid': gid,
									'_wpnonce': nonce
								},
								function( response )
								{
									var parentdiv = thelink.parent();
									if ( ! $('body.directory').length )
										location.href = location.href;
									else {
										$(parentdiv).fadeOut(200,
											function() {
												parentdiv.fadeIn(200).html(response);
											}
											);
									}
								});
								return false;

							}else {
								alert(response.message);
							}
						}
					});
				});
			});
		</script>
		<?php
	}
}
add_action( 'wp_head', 'bp_invite_codes_jquery', 999 );

/**
 * AJAX Action for clicking Group Join Button
 *
 * @since  1.0.0
 */
function bp_invite_codes_bp_get_group_join_button_ajax_action() {
	$entered_code = isset( $_REQUEST['entered_code'] ) ? $_REQUEST['entered_code'] : false;
	$group_id = isset( $_REQUEST['group_id'] ) ? $_REQUEST['group_id'] : false;
	$response['message'] = bp_invites_codes_get_code( $group_id, $entered_code );
	echo json_encode( $response );
	die();
}
add_action( 'wp_ajax_bp_invite_codes_bp_get_group_join_button', 'bp_invite_codes_bp_get_group_join_button_ajax_action' );
add_action( 'wp_ajax_nopriv_bp_invite_codes_bp_get_group_join_button', 'bp_invite_codes_bp_get_group_join_button_ajax_action' );
?>