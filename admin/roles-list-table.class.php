<?php

/**
 * Roles table
 */

require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class Members_Roles_List_Table extends WP_List_Table {

	function get_bulk_actions() {
		$actions = array();

		if ( current_user_can( 'delete_roles' ) ) {
			$actions['bulk-delete'] = __('Delete', 'members');
		}

		return apply_filters( 'members_role_table_bulk_actions', $actions );
	}


	function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'label'        => __('Role Label', 'members'),
			'name'         => __('Role Name', 'members'),
			'users'        => __('Users', 'members'),
			'capabilities' => __('Capabilities', 'members'),
		);

		return $columns;
	}


	function get_sortable_columns() {
		return array(
			'label' => array( 'label', false ),
			'name'  => array( 'name',  false ),
		);
	}


	function usort_reorder( $a, $b ) {
		$orderby = ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], array_keys( $this->get_sortable_columns() ) ) ) ? $_GET['orderby'] : 'label';
		$order   = ( isset( $_GET['order'] ) && in_array( strtolower($_GET['order']), array('asc', 'desc') ) ) ? $_GET['order'] : 'asc';
		$result  = strcmp( $a[$orderby], $b[$orderby] );

		return ( $order === 'asc' ) ? $result : -$result;
	}

	function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = array();
		foreach ( get_editable_roles() as $role => $role_details ) {
			$role_label = translate_user_role( $role_details['name'] );
			$user_count = intval( members_get_role_user_count( $role ) );

			$this->items[ sanitize_title( $role_label ) ] = array(
				'name'       => $role,
				'label'      => $role_label,
				'edit_url'   => admin_url( wp_nonce_url( "users.php?page=roles&amp;action=edit&amp;role={$role}", members_get_nonce( 'edit-roles' ) ) ),
				'delete_url' => admin_url( wp_nonce_url( "users.php?page=roles&amp;action=delete&amp;role={$role}", members_get_nonce( 'edit-roles' ) ) ),
				'user_count' => $user_count,
				'cap_count'  => count( $role_details['capabilities'] ),
			);
		}

		usort( $this->items, array( &$this, 'usort_reorder' ) );
	}


	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="roles[]" id="role-%1$s" value="%1$s" />', esc_attr( $item['name'] ) );
	}


	private function _is_role_removable( $role ) {
		return (
			is_multisite() && is_super_admin() && $role !== get_option( 'default_role' ) )
			|| ( current_user_can( 'delete_roles' ) && $role !== get_option( 'default_role' ) && !current_user_can( $role )
		);
	}


	private function _get_actions( $item ) {
		$actions = array();

		if ( current_user_can( 'edit_roles' ) ) {
			$actions['edit'] = sprintf(
				'<a href="%s" title="%s">%s</a>',
				esc_url( $item['edit_url'] ),
				sprintf( esc_attr__( 'Edit the %s role', 'members' ), $item['label'] ),
				esc_html__( 'Edit', 'members' )
			);
		}

		if ( $this->_is_role_removable( $item['name'] ) ) {
			$actions['delete'] = sprintf(
				'<a href="%s" title="%s">%s</a>',
				$item['delete_url'],
				sprintf( esc_attr__( 'Delete the %s role', 'members' ), $item['label'] ),
				esc_html__( 'Delete', 'members' )
			);
		}

		if ( !is_multisite() && current_user_can( 'manage_options' ) && $item['name'] == get_option( 'default_role' ) ) {
			$actions['set_default'] = sprintf(
				'<a href="%s" title="%s">%s</a>',
				admin_url( 'options-general.php' ),
				esc_attr__( 'Change default role', 'members' ),
				esc_html__( 'Default Role', 'members' )
			);
		}

		return apply_filters( 'members_role_row_actions', $actions, $item );
	}


	function column_label( $item ) {
		$actions = $this->_get_actions( $item );

		$out = sprintf(
			'<a href="%s" title="%s"><strong>%s</strong></a> %s',
			esc_url( $item['edit_url'] ),
			sprintf( esc_attr__( 'Edit the %s role', 'members' ), $item['label'] ),
			esc_html( $item['label'] ),
			$this->row_actions( $actions )
		);

		return $out;
	}


	function column_users( $item ) {
		if ( current_user_can( 'list_users' ) ) {
			$out = sprintf(
				'<a href="%s" title="%s">%s</a>',
				admin_url( esc_url( "users.php?role={$item['name']}" ) ),
				sprintf( __( 'View all users with the %s role', 'members' ), $item['label'] ),
				sprintf( _n( '%s User', '%s Users', $item['user_count'], 'members' ), $item['user_count'] )
			);
		}
		else {
			$out = sprintf( _n( '%s User', '%s Users', $item['user_count'], 'members' ), $item['user_count'] );
		}

		return $out;
	}


	function column_capabilities( $item ) {
		return sprintf( _n( '%s Capability', '%s Capabilities', $item['cap_count'], 'members' ), $item['cap_count'] );
	}


	function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
	}


	function no_items() {
		_e('No language found.', 'members');
	}


	function single_row( $item ) {
		$row_class = ( $item['user_count'] > 0 ) ? 'alternate active' : 'inactive';
		?>
		<tr class="<?php echo esc_attr($row_class) ?>">
			<?php echo $this->single_row_columns( $item ); ?>
		</tr>
		<?php
	}
}
