<?php
defined( "ABSPATH" ) or exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class SpinMembersGh extends WP_List_Table {

	public function __construct() {
		global $status, $page;

		parent::__construct( array(
			'singular' => 'spin_history',
			'plural'   => 'spins_history',
		) );
	}

	protected function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	protected function column_email( $item ) {
		return '<strong>' . $item['email'] . '</strong>';
	}

	protected function column_win( $item ) {
		$win_data = maybe_unserialize( $item['win'] );
		if ( is_array( $win_data ) ) {
			if ( 'product' == $win_data['type'] ) {
				$product  = wc_get_product( (int) $win_data['obj_id'] );
				$win_text = '<a href="' . get_permalink( $win_data['obj_id'] ) . '" target="_blank">' . $product->get_title() . '</a>';
			} elseif ( 'no_prize' == $win_data['type'] ) {
				return '<strong class="red">' . __( 'No Prize', 'wp-spinner' ) . '</strong>';
			} else {
				$win_text = $win_data['text'];
			}

			return '<strong class="green">' . $win_text . '</strong>';
		}

		return '<strong>' . $item['win'] . '</strong>';
	}


	protected function column_spin_id( $item ) {
		return '<a href="' . admin_url( "post.php?post={$item['spin_id']}&action=edit" ) . '" target="_blank">' . get_the_title( $item['spin_id'] ) . ' #' . $item['spin_id'] . '</a>';
	}


	protected function column_claimed( $item ) {
		if ( $item['claimed'] == 1 ) {
			return '<span class="yes">' . __( 'Yes', 'wp-spinner' ) . '</span>';
		}

		return '<span class="no">' . __( 'No', 'wp-spinner' ) . '</span>';
	}


	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="id[]" value="%s" />',
			$item['id']
		);
	}


	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />', //Render a checkbox instead of text
			'id'         => __( 'ID', 'wp-spinner' ),
			'spin_id'    => __( 'Spin', 'wp-spinner' ),
			'email'      => __( 'E-Mail', 'wp-spinner' ),
			'user_name'  => __( 'Name', 'wp-spinner' ),
			'win'        => __( 'Win', 'wp-spinner' ),
			'spin_date'  => __( 'Date', 'wp-spinner' ),
			'spin_count' => __( 'Spins Count', 'wp-spinner' ),
			'claimed'    => __( 'Claimed', 'wp-spinner' ),
		);

		return $columns;
	}


	public function get_sortable_columns() {
		$sortable_columns = array(
			'id'         => array( 'spin_date', false ),
			'spin_date'  => array( 'spin_date', true ),
			'email'      => array( 'email', false ),
			'win'        => array( 'win', false ),
			'spin_count' => array( 'spin_count', false ),
			'spin_id'    => array( 'spin_id', false ),
		);

		return $sortable_columns;
	}

	protected function get_bulk_actions() {
		$actions = array(
			'csv'             => __( 'Export CSV', 'wp-spinner' ) . __( ' (PRO)', 'wp-spinner' ),
			'mailchimp'       => __( 'Send to Mailchimp', 'wp-spinner' ) . __( ' (PRO)', 'wp-spinner' ),
			'zapier'          => __( 'Send to Zapier', 'wp-spinner' ) . __( ' (PRO)', 'wp-spinner' ),
			'remarkety'       => __( 'Send to Remarkety', 'wp-spinner' ) . __( ' (PRO)', 'wp-spinner' ),
			'active_campaign' => __( 'Send to ActiveCampaign', 'wp-spinner' ) . __( ' (PRO)', 'wp-spinner' ),
			'delete'          => __( 'Delete', 'wp-spinner' ),
		);

		return $actions;
	}


	private function getActonIds() {
		if ( isset( $_REQUEST['all_items'] ) && '1' == $_REQUEST['all_items'] ) {
			$ids = 'all';
		} else if ( isset( $_REQUEST['id'] ) ) {
			$ids = $_REQUEST['id'];
		} else {
			$ids = null;
		}

		return $ids;
	}

	protected function process_bulk_action() {
		global $sp_db, $sp_gh;
		$this->cleanUrl();
		$ids    = $this->getActonIds();
		$action = $this->current_action();
		if ( $action && ! $ids ) {
			$this->alert( 'warning', __( 'No Members selected', 'wp-spinner' ) );

			return false;
		}
		if ( 'delete' == $action ) {
			$sp_db->deleteUsers( $ids );
			$this->alert( 'success', __( 'Members deleted', 'wp-spinner' ) );
		} elseif ( $action ) {
			$this->alert( 'error', __( 'Only available in "PRO" version', 'wp-spinner' ) );
		}
	}

	private function cleanUrl() {
		?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (document.getElementById('spin_history_form'))
                    window.history.pushState(null, null, "<?php echo admin_url( 'edit.php?post_type=spin_game_gh&page=spin_history_gh' ) ?>")
            }, false)
        </script>
		<?php
	}

	public function prepare_items() {
		global $wpdb, $sp_db;
		$table_name = $wpdb->prefix . 'spin_members';

		$per_page = 20;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$total_items = $sp_db->getRowsCount();

		$paged   = isset( $_REQUEST['paged'] ) ? ( $per_page * max( 0, intval( $_REQUEST['paged'] ) - 1 ) ) : 0;
		$orderby = ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ) ) ) ? $_REQUEST['orderby'] : 'spin_date';
		$order   = ( isset( $_REQUEST['order'] ) &&
		             in_array( $_REQUEST['order'], array( 'asc', 'desc' ) ) ) ? $_REQUEST['order'] : 'asc';

		$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged ), ARRAY_A );


		$this->set_pagination_args( array(
			'total_items' => $total_items, // total Members defined above
			'per_page'    => $per_page, // per page constant defined at top of method
			'total_pages' => ceil( $total_items / $per_page ) // calculate pages count
		) );
	}


	private function alert( $type, $text ) {
		?>
        <div class="notice notice-<?php echo $type ?> is-dismissible">
            <p><strong><?php echo $text ?></strong></p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">Dismiss this notice.</span>
            </button>
        </div>
		<?php
	}
}
