<?php
/**
 * Admin Settings
 *
 * @package BadgeOS
 * @subpackage Admin
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Settings Menu
 *
 * @since  1.0.0
 */
function bp_invite_codes_create_settings_menu() {
	$plugin_page = add_menu_page( 'BP Invite Codes', 'BP Invite Codes', 'manage_options', 'bp_invite_codes_settings_menu', 'bp_invite_codes_settings_page', plugins_url( 'learningtimes_icon.png', __FILE__ ) );
}
add_action( 'admin_menu', 'bp_invite_codes_create_settings_menu', 8 );

/**
 * Settings Page
 *
 * @since  1.0.0
 */
function bp_invite_codes_settings_page() {
?>
	<div id="icon-options-general" class="icon32"></div>
	<h2>BuddyPress Group Invite Codes</h2>
	<form method='post'>
	<?php
	//set defaults
	$require_code = get_option( 'bp_invite_codes_require_code' );
	$default_bp_groups = get_option( 'bp_invite_codes_default_bp_groups' );

	//save options
	if ( $_POST['sitewide_input'] == "sitewide_input" ) {
		$require_code = $_POST['require_code'];
		update_option( 'bp_invite_codes_require_code', $require_code );
		$default_bp_groups = $_POST['default_bp_groups'];
		update_option( 'bp_invite_codes_default_bp_groups', $default_bp_groups );
	}
?>
	<p><label>Require invite code for registration?</label>
	<select name='require_code'>
				<option value='Yes' checked>Yes</option>
				<option value='No' <?php if ( $require_code=="No" ) {echo "selected";}?>>No</option>
	</select></p>

	<p><label>Default group(s) to add new members to when registered:</label></p>
	<div class="checkbox-overflow">
	  <?php
	if ( bp_has_groups( "per_page=1000" ) ) {
		while ( bp_groups() ) : bp_the_group();
		$post_id = bp_get_group_id();
		$checked="";
		if ( $default_bp_groups ) {
			if ( is_array( $default_bp_groups ) && in_array( $post_id, $default_bp_groups ) ) {
				$checked="checked";
			}
		}?>
		  <li class="popular-category">
			  <input type="checkbox" name="default_bp_groups[]" value="<?php echo absint( $post_id );?>" <?php echo $checked;?>>
			  <a target="_blank" href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a>
		  </li>
	  <?php
		endwhile;
	}?>
	</div>
	<?php
	echo "<input type='hidden' name='sitewide_input' value='sitewide_input'>";
	echo "<input type='submit' class='button-primary' name='submit_options' value='Save'>";
	echo "</form>";
	echo "</div>";
}

/**
 * Save group_ids against post_ids (Associating BP Groups with invite codes)
 *
 * @since  1.0.0
 */
function bp_invite_codes_groups_2_cpt( $form_ids, $meta_key, $post_ID ) {
	$this_ids=get_post_meta( $post_ID, $meta_key, true );
	if ( is_array( $this_ids ) ) {
		foreach ( $this_ids as $key=>$value ) {
			$this_id=$value;
			$that_ids=get_post_meta( $this_id, $meta_key, true );
			if ( is_array( $that_ids ) ) {
				$key = array_search( $post_ID, $that_ids );
				unset( $that_ids[$key] );
			}
			update_post_meta( $this_id, $meta_key, $that_ids );
		}
	}
	//update array
	update_post_meta( $post_ID, $meta_key, $form_ids );
	if ( is_array( $form_ids ) ) {
		foreach ( $form_ids as $key=>$value ) {
			$this_id=$value;
			$that_ids=get_post_meta( $this_id, $meta_key, true );
			if ( !is_array( $that_ids ) ) {
				$that_ids=array( $post_ID );
			}else {
				array_push( $that_ids, $post_ID );
			}
			update_post_meta( $this_id, $meta_key, $that_ids );
		}
	}
}

/**
 * Groups meta box (UI for adding groups to invite codes)
 *
 * @since  1.0.0
 */
add_action( 'admin_init', 'bp_invite_codes_admin_init' );
function bp_invite_codes_admin_init() {
	add_meta_box( 'bp_invite_codes_groups_meta_box', 'Groups', 'bp_invite_codes_groups_checkboxes', 'bp-invite-codes', 'side', 'low' );
	add_action( 'save_post', 'bp_invite_codes_groups_update' );
}

/**
 * Checkboxes for each group
 *
 * @since  1.0.0
 */
function bp_invite_codes_groups_checkboxes() {
	global $post_ID, $post;
	if ( bp_is_active( 'groups' ) ) {
		$tmp_post = $post;
		$this_post = get_post( $post_ID );
		$ids=get_post_meta( $post_ID, '_bp_invite_codes_groups', true );
		echo '<div id="posttype-clients" class="categorydiv">';
		echo '<div id="clients-all" class="tabs-panel">';
		echo '<ul id="clientschecklist" class="list:category categorychecklist form-no-clear">';
		$input_type="checkbox";

		// check if post is attached to a group from a group admin
		$post_group_id = get_post_meta( $post_ID, '_bp_invite_codes_group_id', 1 );
		if ( bp_has_groups( "per_page=1000" ) ) {
			while ( bp_groups() ) : bp_the_group();
			$group_id = bp_get_group_id();//really group_id
			$checked="";
			if ( $ids ) {
				if ( is_array( $ids ) && in_array( $group_id, $ids ) ) {
					$checked="checked";
				}
			}
			$locked = NULL;
			if ( (int)$post_group_id == (int)$group_id ) {
				$checked=' checked';
				$locked=' disabled';
			}?>
				<li class="popular-category">
					<input type="<?php echo $input_type;?>" name="groupslist[]" value="<?php echo absint( $group_id );?>"<?php echo $checked . $locked;?>>
					<a target="_blank" href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a>
				</li>
			<?php
			endwhile;
		}
		echo '</ul>';
		echo '</div>';
		echo '</div>';
		$post = $tmp_post;
		setup_postdata( $post );
	}else {
		echo 'You dont have the BuddyPress Groups Component turned on.';
	}

}

/**
 * Save meta box values
 *
 * @since  1.0.0
 */
function bp_invite_codes_groups_update( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	// verify if this is quick-edit routine. If it is, we don't want to do anything.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		return;

	global $post_ID;
	$this_post = get_post( $post_ID );
	if ( 'bp-invite-codes' != $this_post->post_type )
		return;

	$form_ids = $_POST['groupslist'];
	$meta_key = "_bp_invite_codes_groups";
	bp_invite_codes_groups_2_cpt( $form_ids, $meta_key, $post_ID );
}

/**
 * Post redirect to see if entered invite code is already being used
 *
 * @since  1.0.0
 */
function my_redirect_post_location_filter( $location ) {
	remove_filter( 'redirect_post_location', __FUNCTION__, 999 );
	$location = add_query_arg( 'message', 999, $location );
	return $location;
}
add_filter( 'redirect_post_location', 'my_redirect_post_location_filter', 999 );

/**
 * Actual check if entered invite code is already being used
 *
 * @since  1.0.0
 */
function my_post_updated_messages_filter( $messages ) {
	global $post;
	// Get this posts code and check if it already exists
	$message = bp_invite_codes_check_code( $post->ID );
	$messages['post'][999] = $message;
	return $messages;
}
add_filter( 'post_updated_messages', 'my_post_updated_messages_filter' );

/**
 * Invite Code Meta Fields
 *
 * @since  1.0.0
 */
function meetingstack_sessions_meta( array $meta_boxes ) {

	$prefix = '_bp_invite_codes_';
	$meta_boxes[] = array(
		'id'         => 'invitation_metabox',
		'title'      => 'Invitation Data',
		'pages'      => array( 'bp-invite-codes', ), // Post type
		'context'    => 'normal',
		'priority'   => 'high',
		'show_names' => true, // Show field names on the left
		'fields'     => array(
			array(
				'name' => 'Invite Code',
				'desc' => 'example: H9eJl32',
				'id'   => $prefix . 'code',
				'type' => 'text_medium',
			),
			array(
				'name' => 'Limit',
				'desc' => 'Code can be used x times, leave blank for no limit.',
				'id'   => $prefix . 'limit',
				'type' => 'text_small',
			),
			array(
				'name' => 'Expiration',
				'desc' => 'Date which code expires, leave blank for no expiration.',
				'id'   => $prefix . 'expiration',
				'type' => 'text_date',
			),
		),
	);

	return $meta_boxes;
}
add_filter( 'cmb_meta_boxes', 'meetingstack_sessions_meta' );

/**
 * Show custom columns for bp-invite-codes cpt
 *
 * @since  1.0.0
 */
add_filter( 'manage_edit-bp-invite-codes_columns', 'badgestack_invitations_columns' );
function badgestack_invitations_columns( $columns ) {
	$newcolumns = array(
		'invitation_code' => 'Invitation Code',
		// 'invitation_groups' => 'Applicable Groups',
	);
	$columns = array_merge( $columns, $newcolumns );
	return $columns;
}

/**
 * Custom Column Row Data
 *
 * @since  1.0.0
 */
add_action( 'manage_posts_custom_column' , 'badgestack_slides_columns_display' );
function badgestack_slides_columns_display( $column ) {
	global $post;
	$pre = '_bp_invite_codes_';
	switch ( $column ) {
	case 'invitation_code':
		$codes = array(
			'code' => array(
				'meta' => $code = get_post_meta( $post->ID, $pre .'code', true ),
				'full' => 'Code: <b>'. esc_attr( $code ) .'</b>',
			),
			'limit' => array(
				'meta' => $limit = get_post_meta( $post->ID, $pre .'limit', true ),
				'full' => ' | Limit: '. esc_attr( $limit ),
			),
			'expiration' => array(
				'meta' => $expiration = get_post_meta( $post->ID, $pre .'expiration', true ),
				'full' => ' | Expiration: '. esc_attr( $expiration ),
			)
		);
		foreach ( $codes as $key => $code ) {
			if ( !empty( $code['meta'] ) )
				echo $code['full'];
		}
		break;
	}
}
