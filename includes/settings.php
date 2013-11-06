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

	add_menu_page( 'BP Invite Codes', 'BP Invite Codes', 'manage_options', 'bp_invite_codes_settings', 'bp_invite_codes_settings_page', plugins_url( 'learningtimes_icon.png', __FILE__ ) );
	add_submenu_page( 'bp_invite_codes_settings', 'BP Invite Code Settings', 'Settings', 'manage_options', 'bp_invite_codes_settings', 'bp_invite_codes_settings_page' );

}
add_action( 'admin_menu', 'bp_invite_codes_create_settings_menu', 8 );

/**
 * Settings Page
 *
 * @since  1.0.0
 */
function bp_invite_codes_settings_page() {

?>
	<div id="wrap" class="bp-invite-codes-admin">
		<div id="icon-options-general" class="icon32"></div>
		<h2><?php _e( 'BuddyPress Invite Code Settings', 'bp-invite-codes' ); ?></h2>

		<form method='post'>
			<?php
				$require_code = get_option( 'bp_invite_codes_require_code' );

				if ( !in_array( $require_code, array( 'Yes', 'No' ) ) ) {
					$require_code = 'Yes';
				}

				$bp_invite_codes_default_bp_groups = get_option( 'bp_invite_codes_default_bp_groups' );

				if ( empty( $bp_invite_codes_default_bp_groups ) ) {
					$bp_invite_codes_default_bp_groups = array();
				}
				elseif ( !is_array( $bp_invite_codes_default_bp_groups ) ) {
					$bp_invite_codes_default_bp_groups = (array) $bp_invite_codes_default_bp_groups;
				}

				$bp_invite_codes_default_bp_groups = array_map( 'absint', $bp_invite_codes_default_bp_groups );

				// save options
				if ( isset( $_POST[ 'sitewide_input' ] ) && 'sitewide_input' == $_POST[ 'sitewide_input' ] ) {
					$require_code = $_POST[ 'require_code' ];
					update_option( 'bp_invite_codes_require_code', $require_code );

					$bp_invite_codes_default_bp_groups = array();

					if ( isset( $_POST[ 'bp_invite_codes_default_bp_groups' ] ) ) {
						$bp_invite_codes_default_bp_groups = (array) $_POST[ 'bp_invite_codes_default_bp_groups' ];

						$bp_invite_codes_default_bp_groups = array_map( 'absint', $bp_invite_codes_default_bp_groups );
					}

					update_option( 'bp_invite_codes_default_bp_groups', $bp_invite_codes_default_bp_groups );

					echo '<div id="message" class="updated fade"><p>' . __( 'Settings saved.', 'bp-invite-codes' ) . '</p></div>';
				}
			?>
			<p>
				<label for="bp-invite-codes-require_code">
					<?php _e( 'Require invite code for registration?', 'bp-invite-codes' ); ?>
				</label>

				<select name="require_code" id="bp-invite-codes-require_code">
					<option value="Yes"<?php selected( 'Yes', $require_code ); ?>><?php esc_attr_e( 'Yes', 'bp-invite-codes' ); ?></option>
					<option value="No"<?php selected( 'No', $require_code ); ?>><?php esc_attr_e( 'No', 'bp-invite-codes' ); ?></option>
				</select>
			</p>

			<p>
				<label><?php _e( 'Default group(s) to add new members to when registered:', 'bp-invite-codes' ); ?></label>
			</p>

			<div class="checkbox-overflow">
				<?php
					if ( bp_has_groups( 'per_page=1000&show_hidden=1' ) ) {
						while ( bp_groups() ) {
							bp_the_group();

							$post_id = absint( bp_get_group_id() );
				?>
					<li class="popular-category">
						<label for="bp-invite-codes-group_<?php echo $post_id; ?>">
							<input type="checkbox" name="bp_invite_codes_default_bp_groups[]" id="bp-invite-codes-group_<?php echo $post_id; ?>" value="<?php echo $post_id; ?>"<?php checked( in_array( $post_id, $bp_invite_codes_default_bp_groups ) ); ?> />

							<?php bp_group_name() ?>
							<span class="actions">[<a href="<?php bp_group_permalink() ?>" target="_blank"><?php _e( 'View', 'bp-invite-codes' ); ?></a>]</span>
						</label>
					</li>
				<?php
						}
					}
				?>
			</div>

			<input type="hidden" name="sitewide_input" value="sitewide_input" />

			<?php submit_button(); ?>
		</form>
	</div>
<?php

}

/**
 * Save group_ids against post_ids (Associating BP Groups with invite codes)
 *
 * @since  1.0.0
 */
function bp_invite_codes_groups_2_cpt( $form_ids, $meta_key, $post_ID ) {

	$this_ids = get_post_meta( $post_ID, $meta_key, true );

	if ( is_array( $this_ids ) ) {
		foreach ( $this_ids as $this_id ) {
			$that_ids = get_post_meta( $this_id, $meta_key, true );

			if ( is_array( $that_ids ) ) {
				$key = array_search( $post_ID, $that_ids );

				unset( $that_ids[ $key ] );
			}

			update_post_meta( $this_id, $meta_key, $that_ids );
		}
	}

	//update array
	update_post_meta( $post_ID, $meta_key, $form_ids );

	if ( is_array( $form_ids ) ) {
		foreach ( $form_ids as $this_id ) {
			$that_ids = get_post_meta( $this_id, $meta_key, true );

			if ( !is_array( $that_ids ) ) {
				$that_ids = array( $post_ID );
			}
			else {
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
function bp_invite_codes_meta_boxes() {

	add_meta_box( 'bp_invite_codes_groups_meta_box', __( 'Groups', 'buddypress' ), 'bp_invite_codes_groups_checkboxes', 'bp-invite-codes', 'side', 'low' );

}
add_action( 'add_meta_boxes', 'bp_invite_codes_meta_boxes' );

/**
 * Groups meta box (UI for adding groups to invite codes)
 *
 * @since  1.0.0
 */
function bp_invite_codes_admin_init() {

	add_action( 'save_post', 'bp_invite_codes_groups_update', 10, 2 );

}
add_action( 'admin_init', 'bp_invite_codes_admin_init' );

/**
 * Checkboxes for each group
 *
 * @since  1.0.0
 */
function bp_invite_codes_groups_checkboxes( $post ) {

	global $buddypress_invite_codes;

	wp_enqueue_style( 'bp-invite-codes-admin', $buddypress_invite_codes->directory_url . 'css/admin.css', array(), '1.0' );

	$selected_group_ids = get_post_meta( $post->ID, '_bp_invite_codes_groups', true );

	if ( empty( $selected_group_ids ) ) {
		$selected_group_ids = array();
	}
	elseif ( !is_array( $selected_group_ids ) ) {
		$selected_group_ids = (array) $selected_group_ids;

		$selected_group_ids = array_map( 'absint', $selected_group_ids );
	}

	// check if post is attached to a group from a group admin
	$post_group_ids = get_post_meta( $post->ID, '_bp_invite_codes_group_id', true );

	if ( empty( $post_group_ids ) ) {
		$post_group_ids = array();
	}
	elseif ( !is_array( $post_group_ids ) ) {
		$post_group_ids = (array) $post_group_ids;
	}

	$post_group_ids = array_map( 'absint', $post_group_ids );

	if ( !empty( $post_group_ids ) ) {
		$selected_group_ids = array_merge( $selected_group_ids, $post_group_ids );
		$selected_group_ids = array_unique( $selected_group_ids );
	}

	$input_type = apply_filters( 'bp_invite_codes_group_input_type', 'checkbox', $post->ID );

	if ( !empty( $post_group_ids ) ) {
		$input_type = 'checkbox';
	}
?>
	<div id="posttype-clients" class="categorydiv">
		<div id="clients-all" class="tabs-panel">
			<?php wp_nonce_field( 'bp_invite_codes_post_save', 'bp_invite_codes_post_save' ); ?>

			<ul id="clientschecklist" class="list:category categorychecklist form-no-clear">
				<?php
					if ( bp_has_groups( 'per_page=1000&show_hidden=1' ) ) {
						while ( bp_groups() ) {
							bp_the_group();

							$group_id = (int) bp_get_group_id(); // real group_id
				?>
					<li class="popular-category">
						<label for="bp-invite-codes-group_<?php echo $group_id; ?>">
							<input type="<?php echo $input_type; ?>" name="bp_invite_codes_groups[]" id="bp-invite-codes-group_<?php echo $group_id; ?>"
								value="<?php echo $group_id; ?>"
								<?php checked( in_array( $group_id, $selected_group_ids ) ); ?>
								<?php disabled( in_array( $group_id, $post_group_ids ) ); ?> />

							<?php bp_group_name() ?>
							<span class="actions">[<a href="<?php bp_group_permalink() ?>" target="_blank"><?php _e( 'View', 'bp-invite-codes' ); ?></a>]</span>
						</label>
					</li>
				<?php
						}
					}
				?>
			</ul>
		</div>
	</div>
<?php
	wp_reset_postdata();

}

/**
 * Save meta box values
 *
 * @since  1.0.0
 */
function bp_invite_codes_groups_update( $post_id, $post ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}
	// verify if this is quick-edit routine. If it is, we don't want to do anything.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return $post_id;
	}

	if ( 'bp-invite-codes' != $post->post_type || !isset( $_POST[ 'bp_invite_codes_post_save' ] ) || !wp_verify_nonce( $_POST[ 'bp_invite_codes_post_save' ], 'bp_invite_codes_post_save' ) ) {
		return $post_id;
	}

	$form_ids = array();

	if ( isset( $_POST[ 'bp_invite_codes_groups' ] ) ) {
		$form_ids = (array) $_POST[ 'bp_invite_codes_groups' ];
	}

	bp_invite_codes_groups_2_cpt( $form_ids, '_bp_invite_codes_groups', $post_id );

	return $post_id;

}

/**
 * Post redirect to see if entered invite code is already being used
 *
 * @since  1.0.0
 */
function bp_invite_codes_redirect_post_location_filter( $location ) {

	remove_filter( 'redirect_post_location', __FUNCTION__, 999 );

	$location = add_query_arg( 'message', 999, $location );

	return $location;

}
add_filter( 'redirect_post_location', 'bp_invite_codes_redirect_post_location_filter', 999 );

/**
 * Actual check if entered invite code is already being used
 *
 * @since  1.0.0
 */
function bp_invite_codes_post_updated_messages_filter( $messages ) {

	global $post;

	// Get this posts code and check if it already exists
	$message = bp_invite_codes_check_code( $post->ID );
	$messages[ 'post' ][ 999 ] = $message;

	return $messages;

}
add_filter( 'post_updated_messages', 'bp_invite_codes_post_updated_messages_filter' );

/**
 * Invite Code Meta Fields
 *
 * @since  1.0.0
 */
function bp_invite_codes_meta_box( array $meta_boxes ) {

	global $post_ID;

	$prefix = '_bp_invite_codes_';

	$default_invite_code = wp_generate_password( 7, false );

	while ( bp_invite_codes_check_code( $default_invite_code ) ) {
		$default_invite_code = wp_generate_password( 7, false );
	}

	$meta_boxes[] = array(
		'id' => 'invitation_metabox',
		'title' => 'Invitation Data',
		'pages' => array( 'bp-invite-codes' ), // Post type
		'context' => 'normal',
		'priority' => 'high',
		'show_names' => true, // Show field names on the left
		'fields' => array(
			array(
				'name' => __( 'Invite Code', 'bp-invite-codes' ),
				'desc' => __( '(e.g. H9eJl32)', 'bp-invite-codes' ),
				'id' => $prefix . 'code',
				'std' => $default_invite_code,
				'type' => 'text_medium',
			),
			array(
				'name' => __( 'Limit', 'bp-invite-codes' ),
				'desc' => __( 'Max number of times code may be used, leave blank for no limit.', 'bp-invite-codes' ),
				'id' => $prefix . 'limit',
				'type' => 'text_small',
			),
			array(
				'name' => __( 'Current # of Uses', 'bp-invite-codes' ),
				'desc' => __( 'This is how many times the invite has been used, you can reset it to zero if needed.', 'bp-invite-codes' ),
				'id' => $prefix . 'used',
				'type' => 'text_small',
			),
			array(
				'name' => __( 'Expiration', 'bp-invite-codes' ),
				'desc' => __( 'Date on which code expires, leave blank for no expiration.', 'bp-invite-codes' ),
				'id' => $prefix . 'expiration',
				'type' => 'text_date',
			),
		),
	);

	return $meta_boxes;

}
add_filter( 'cmb_meta_boxes', 'bp_invite_codes_meta_box' );

/**
 * Show custom columns for bp-invite-codes cpt
 *
 * @since  1.0.0
 */
function bp_invite_codes_invitations_columns( $columns ) {

	$newcolumns = array(
		'invitation_code' => 'Invitation Code',
	);

	$columns = array_merge( $columns, $newcolumns );

	return $columns;

}
add_filter( 'manage_edit-bp-invite-codes_columns', 'bp_invite_codes_invitations_columns' );

/**
 * Custom Column Row Data
 *
 * @since  1.0.0
 */
function bp_invite_codes_column_display( $column ) {

	global $post;

	$pre = '_bp_invite_codes_';

	switch ( $column ) {
		case 'invitation_code':
			$codes = array(
				'code' => array(
					'meta' => $code = get_post_meta( $post->ID, $pre . 'code', true ),
					'full' => __( 'Code:', 'bp-invite-codes' ) . ' <b>' . esc_attr( $code ) . '</b>',
				),
				'limit' => array(
					'meta' => $limit = get_post_meta( $post->ID, $pre . 'limit', true ),
					'full' => __( 'Limit:', 'bp-invite-codes' ) . ' ' . esc_attr( $limit ),
				),
				'expiration' => array(
					'meta' => $expiration = get_post_meta( $post->ID, $pre . 'expiration', true ),
					'full' => __( 'Expiration:', 'bp-invite-codes' ) . ' ' . esc_attr( $expiration ),
				),
				'uses' => array(
					'meta' => $uses = get_post_meta( $post->ID, $pre . 'used', true ),
					'full' => __( 'Uses:', 'bp-invite-codes' ) . ' ' . esc_attr( $uses ),
				)
			);

			$output = array();

			foreach ( $codes as $key => $code ) {
				if ( !empty( $code[ 'meta' ] ) ) {
					$output[] = $code[ 'full' ];
				}
			}

			echo implode( ' | ', $output );

			break;
	}

}
add_action( 'manage_posts_custom_column', 'bp_invite_codes_column_display' );