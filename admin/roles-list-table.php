<?php
/**
 * This file handles the display of the 'Roles' page in the admin.
 *
 * @package Members
 * @subpackage Admin
 */

/* Get a count of all the roles available. */
$roles_count = members_count_roles();

/* Get all of the active and inactive roles. */
$active_roles = members_get_active_roles();
$inactive_roles = members_get_inactive_roles();

/* Get a count of the active and inactive roles. */
$active_roles_count = count( $active_roles );
$inactive_roles_count = count( $inactive_roles );

/* If we're viewing 'active' or 'inactive' roles. */
if ( !empty( $_GET['role_status'] ) && in_array( $_GET['role_status'], array( 'active', 'inactive' ) ) ) {

	/* Get the role status ('active' or 'inactive'). */
	$role_status = esc_attr( $_GET['role_status'] );

	/* Set up the roles array. */
	$list_roles = ( ( 'active' == $role_status ) ? $active_roles : $inactive_roles );

	/* Set the current page URL. */
	$current_page = admin_url( "users.php?page=roles&role_status={$role_status}" );
}

/* If viewing the regular role list table. */
else {

	/* Get the role status ('active' or 'inactive'). */
	$role_status = 'all';

	/* Set up the roles array. */
	$list_roles = array_merge( $active_roles, $inactive_roles );

	/* Set the current page URL. */
	$current_page = $current_page = admin_url( 'users.php?page=roles' );
}

/* Sort the roles array into alphabetical order. */
ksort( $list_roles ); ?>

<div class="wrap">

	<?php screen_icon(); ?>

	<h2>
		<?php _e( 'Roles', 'members' ); ?>
		<?php if ( current_user_can( 'create_roles' ) ) echo '<a href="' . admin_url( 'users.php?page=role-new' ) . '" class="add-new-h2">' . __( 'Add New', 'members' ) . '</a>'; ?>
	</h2>

	<?php do_action( 'members_pre_edit_roles_form' ); // Available action hook for displaying messages. ?>

	<div id="poststuff">

		<form id="roles" action="<?php echo $current_page; ?>" method="post">

			<?php
				$roles_table->prepare_items();
				$roles_table->display();
			?>

		</form><!-- #roles -->

	</div><!-- #poststuff -->

</div><!-- .wrap -->
