<?php
/**
 * This file handles the display of the 'Roles' page in the admin.
 *
 * @package Members
 * @subpackage Admin
 */
?>

<div class="wrap">

	<?php screen_icon(); ?>

	<h2>
		<?php _e( 'Roles', 'members' ); ?>
		<?php if ( current_user_can( 'create_roles' ) ) echo '<a href="' . admin_url( 'users.php?page=role-new' ) . '" class="add-new-h2">' . __( 'Add New', 'members' ) . '</a>'; ?>
	</h2>

	<?php do_action( 'members_pre_edit_roles_form' ); // Available action hook for displaying messages. ?>

	<div id="poststuff">

		<form id="roles" action="<?php echo $current_page; ?>" method="post">

			<?php $roles_table->prepare_items(); ?>

			<?php $roles_table->views(); ?>

			<?php $roles_table->display(); ?>

		</form><!-- #roles -->

	</div><!-- #poststuff -->

</div><!-- .wrap -->
