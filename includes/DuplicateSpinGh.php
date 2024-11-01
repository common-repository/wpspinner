<?php

class DuplicateSpinGh {

	public function __construct() {
		add_filter( 'post_row_actions', array( $this, 'duplicatePostLink' ), 10, 2 );
	}

	public function duplicatePostLink( $actions, $post ) {

		if ( current_user_can( 'edit_posts' ) && 'spin_game_gh' == $post->post_type ) {
			$actions['duplicate'] = '<span title="' . __( 'Duplicate this item', 'wp-spinner' ) . '">' . __( 'Duplicate', 'wp-spinner' ) . __( ' (PRO)', 'wp-spinner' ) . '</span>';
		}

		return $actions;
	}

}

new DuplicateSpinGh();