<?php
defined( "ABSPATH" ) or exit;

class SpinDbGh {
	private $table;
	private $db;

	private $usersListPage;

	public function __construct() {

		global $wpdb;
		$this->db            = $wpdb;
		$this->table         = $wpdb->prefix . 'spin_members';
		$this->usersListPage = 1;

		register_activation_hook( SPIN_ROOT_FILE, array( $this, 'createTable' ) );


	}

	public function createTable() {
		$charset_collate = $this->db->get_charset_collate();
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `{$this->table}`(
					`id` BIGINT(20) NOT NULL AUTO_INCREMENT, 
					`spin_id` BIGINT(20) NOT NULL, 
					`user_name` VARCHAR(255) NOT NULL,
					`email` VARCHAR(255) NOT NULL, 
					`win` VARCHAR(255) NOT NULL, 
					`spin_date` DATETIME NOT NULL, 
					`spin_count` INT(1), 
					`claimed` enum('0', '1') DEFAULT '0',
					PRIMARY KEY (`id`)
				) $charset_collate"
		);
	}

	public function getData( $spin_id, $user_email ) {

		$sql = $this->db->prepare( "SELECT * FROM `{$this->table}` 
											WHERE `email` = %s AND `spin_id` = %d 
											ORDER BY `spin_date` DESC LIMIT 1", $user_email, $spin_id );

		return $this->db->get_row( $sql );
	}


	public function getUserSpinsCount( $spin_id, $user_email, $is_claimed = true ) {
		global $sp_gh;
		$options            = $sp_gh->getSpinOptions( $spin_id );
		$period             = '-' . $options['spins_per_period'] . ' ' . $options['spins_per_period_type'];
		$allowed_date       = new DateTime( $period );
		$allowed_date_mysql = $allowed_date->format( 'Y-m-d H:i:s' );

		$data = $this->getData( $spin_id, $user_email );

		if ( ! empty( $data ) ) {
			if ( $is_claimed && $data->claimed == 1 ) {
				return - 1;
			}

			if ( $data->spin_date < $allowed_date_mysql ) {
				return 0;
			}

			return (int) $data->spin_count;
		}

		return 0;
	}

	public function getAvailableSpinsCount( $spin_id, $user_email, $max_spin_limit ) {
		$spin_count = $this->getUserSpinsCount( $spin_id, $user_email );
		if ( $spin_count == - 1 ) {
			return 0;
		}

		return $max_spin_limit - $spin_count;
	}

	public function getAllowedDate( $spin_id ) {
		global $sp_gh;
		$options      = $sp_gh->getSpinOptions( $spin_id );
		$period       = '-' . $options['spins_per_period'] . ' ' . $options['spins_per_period_type'];
		$allowed_date = new DateTime( $period );

		return $allowed_date->format( 'Y-m-d H:i:s' );
	}

	public function insertUserSpin( $spin_id, $user_email, $user_name, $max_spin_limit ) {
		global $sp_gh;
		$data = $this->getData( $spin_id, $user_email );

		$options = $sp_gh->getSpinOptions( $spin_id );

		$allowed_date = $this->getAllowedDate( $spin_id );

		$spin_count = 1 + $this->getUserSpinsCount( $spin_id, $user_email );

//		if ( ! empty( $data ) ) {
//			if ( $data->spin_date < $allowed_date ) {
//				$spin_count = 1;
//			} else {
//				$spin_count = (int) $data->spin_count;
//				$spin_count ++;
//			}
//
//		} else {
//			$spin_count = 1;
//		}


		if ( $spin_count > $max_spin_limit ) {
			return array(
				'success'   => false,
				'error_msg' => $sp_gh->doShortCode( $options['result']['max_try_message'] )
			);
		} elseif ( $data->claimed == 1 ) {
			return array(
				'success'   => false,
				'error_msg' => $sp_gh->doShortCode( $options['result']['claimed_error'] )
			);
		}


		$new_data = array(
			'spin_id'    => $spin_id,
			'user_name'  => esc_sql( $user_name ),
			'email'      => esc_sql( $user_email ),
			'win'        => '',
			'spin_date'  => date( 'Y-m-d H:i:s' ),
			'spin_count' => $spin_count,
			'claimed'    => '0'
		);


		if ( empty( $data ) || $data->spin_date < $allowed_date ) {
			$this->db->insert( $this->table, $new_data );
		} else {
			$this->db->update( $this->table, $new_data, array( 'id' => $data->id ) );
		}

		return array( 'success' => true, 'is_last_spin' => $spin_count == $max_spin_limit ? true : false );
	}

	public function claim( $spin_id, $user_email, $data ) {

		$this->db->update( $this->table,
			array(
				'win'     => serialize( $data ),
				'claimed' => '1'
			),
			array(
				'spin_id' => $spin_id,
				'email'   => $user_email,
				'claimed' => '0'
			)
		);
	}

	public function getRowsCount() {
		return $this->db->get_var( "SELECT COUNT(id) FROM `{$this->table}`" );
	}

	public function getUsers( $ids = null, $per_page = 20 ) {
		$paged = $per_page * max( 0, intval( $this->usersListPage ) - 1 );
		if ( is_array( $ids ) && ! empty( $ids ) ) {
			$_sql = " WHERE id IN(" . implode( ', ', $ids ) . ")";
		} elseif ( 'all' == $ids ) {
			$_sql = '';
		} else {
			return array();
		}


		$sql = $this->db->prepare( "SELECT * FROM `{$this->table}` {$_sql}  GROUP BY `email` ORDER BY `id` LIMIT %d OFFSET %d", $per_page, $paged );

		$result = $this->db->get_results( $sql, ARRAY_A );

		$this->usersListPage = $result ? $this->usersListPage + 1 : 1;

		return $result;
	}

	public function insertUserWin( $spin_id, $user_email, $win ) {
		$data = $this->getData( $spin_id, $user_email );

		if ( $data ) {
			$this->db->update( $this->table,
				array( 'win' => $win ),
				array( 'id' => $data->id )
			);
		}

	}

	public function deleteUsers( $ids ) {
		if ( is_array( $ids ) && ! empty( $ids ) ) {
			$_sql = " WHERE id IN(" . implode( ', ', $ids ) . ")";
		} elseif ( 'all' == $ids ) {
			$_sql = '';
		} else {
			return;
		}
		$this->db->query( "DELETE FROM `{$this->table}` {$_sql}" );
	}


	public function getUserTryAgainTime( $spin_id, $user_email ) {
		$allowed_date   = $this->getAllowedDate( $spin_id );
		$spin_data      = $this->getData( $spin_id, $user_email );
		$last_spin_date = $spin_data->spin_date;
		$data           = '';
		if ( $allowed_date < $last_spin_date ) {
			$date1 = date_create( $allowed_date );
			$date2 = date_create( $last_spin_date );
			$diff  = date_diff( $date1, $date2 );
			if ( $diff->y ) {
				$data .= $diff->y . _n( 'year', 'years', (int) $diff->y, 'wp-spinner' ) . ' ';
			}
			if ( $diff->m ) {
				$data .= $diff->m . _n( 'month', 'months', (int) $diff->m, 'wp-spinner' ) . ' ';
			}
			if ( $diff->d ) {
				$data .= $diff->d . _n( 'day', 'days', (int) $diff->d, 'wp-spinner' ) . ' ';
			}
			$data .= $diff->h . __( 'hr.', 'wp-spinner' ) . ' ';
			$data .= $diff->i . __( 'min.', 'wp-spinner' ) . ' ';
			$data .= $diff->s . __( 'sec.', 'wp-spinner' ) . ' ';
		} else {
			$data .= '0 ' . __( 'sec', 'wp-spinner' );
		}

		return $data;

	}
}

$sp_db = new SpinDbGh();