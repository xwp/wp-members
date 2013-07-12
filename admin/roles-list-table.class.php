<?php

/**
 * Roles table
 */

require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class Members_Roles_List_Table extends WP_List_Table {

	private $_data = array(
		'status'   => 'all',
		'statuses' => 'all',
		'order'    => 'asc',
		'orderby'  => 'label',
		'totals'   => array(
			'all'      => 0,
			'active'   => 0,
			'inactive' => 0,
		),
	);


	function __construct( $args = array() ) {
		parent::__construct( $args );

		if ( !empty( $_REQUEST['role_status'] ) && in_array( $_REQUEST['role_status'], array( 'active', 'inactive', 'search' ) ) )
			$this->_data['status'] = $_REQUEST['role_status'];

		if ( !empty( $_REQUEST['order'] ) && in_array( strtolower($_REQUEST['order']), array( 'asc', 'desc' ) ) )
			$this->_data['order'] = $_REQUEST['order'];

		if ( !empty( $_REQUEST['orderby'] ) && array_key_exists( strtolower($_REQUEST['orderby']), $this->get_sortable_columns() ) )
			$this->_data['orderby'] = $_REQUEST['orderby'];
	}

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
			'users' => array( 'users', false ),
		);
	}


	function _order_callback( $a, $b ) {
		$result  = strcmp( $a[ $this->_data['orderby'] ], $b[ $this->_data['orderby'] ] );

		return ( 'asc' === $this->_data['order'] ) ? $result : -$result;
	}


	function prepare_items() {
		$all_roles = array(
			'all'      => array(),
			'active'   => array(),
			'inactive' => array(),
		);

		foreach ( get_editable_roles() as $role => $role_details ) {
			$user_count = intval( members_get_role_user_count( $role ) );
			$item       = array(
				'name'       => $role,
				'label'      => translate_user_role( $role_details['name'] ),
				'edit_url'   => admin_url( wp_nonce_url( "users.php?page=roles&amp;action=edit&amp;role={$role}", members_get_nonce( 'edit-roles' ) ) ),
				'delete_url' => admin_url( wp_nonce_url( "users.php?page=roles&amp;action=delete&amp;role={$role}", members_get_nonce( 'edit-roles' ) ) ),
				'users'      => $user_count,
				'cap_count'  => count( $role_details['capabilities'] ),
			);

			$all_roles['all'][ $role ] = $item;
			$this->_data['totals']['all']++;

			if ( $user_count > 0 ) {
				$all_roles['active'][ $role ] = $item;
				$this->_data['totals']['active']++;
			}
			else {
				$all_roles['inactive'][ $role ] = $item;
				$this->_data['totals']['inactive']++;
			}
		}

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns()
		);

		$this->items = $all_roles[ $this->_data['status'] ];
		usort( $this->items, array( $this, '_order_callback' ) );
		$total_this_page = count( $this->items );

		// Pagination
		$roles_per_page = $this->get_items_per_page( sprintf( '%s_per_page', get_current_screen()->id ), 999 );
		$start = ( $this->get_pagenum() - 1 ) * $roles_per_page;

		if ( $total_this_page > $roles_per_page )
			$this->items = array_slice( $this->items, $start, $roles_per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_this_page,
			'per_page'    => $roles_per_page,
		) );

	}


	function get_views() {
		$links = array();

		foreach ( $this->_data['totals'] as $type => $count ) {
			if ( !$count )
				continue;

			switch ( $type ) {
				case 'all':
					$text = _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $count, 'roles', 'members' );
					break;
				case 'active':
					$text = _n( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', $count, 'roles', 'members' );
					break;
				case 'inactive':
					$text = _n( 'Inctive <span class="count">(%s)</span>', 'Inctive <span class="count">(%s)</span>', $count, 'roles', 'members' );
					break;
			}

			$links[ $type ] = sprintf(
				'<a href="%s"%s>%s</a>',
				esc_url( add_query_arg( array('page' => 'roles', 'role_status' => $type), 'users.php' ) ),
				( $type === $this->_data['status'] ) ? ' class="current"' : '',
				sprintf( $text, number_format_i18n( $count ) )
			);

		}

		return $links;
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
				sprintf( _n( '%s User', '%s Users', $item['users'], 'members' ), $item['users'] )
			);
		}
		else {
			$out = sprintf( _n( '%s User', '%s Users', $item['users'], 'members' ), $item['users'] );
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
		_e('No roles found.', 'members');
	}


	function single_row( $item ) {
		$row_class = ( $item['users'] > 0 ) ? 'alternate active' : 'inactive';
		?>
		<tr class="<?php echo esc_attr($row_class) ?>">
			<?php echo $this->single_row_columns( $item ); ?>
		</tr>
		<?php
	}
}
