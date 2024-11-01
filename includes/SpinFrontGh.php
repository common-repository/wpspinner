<?php
defined( "ABSPATH" ) or exit;

class SpinFrontGh {

	public function __construct() {
		session_id() == '' or session_start();

		add_action( 'wp_enqueue_scripts', array( $this, 'registerScripts' ) );

		add_action( 'wp_ajax_spin_start_gh', array( $this, 'startSpin' ) );
		add_action( 'wp_ajax_nopriv_spin_start_gh', array( $this, 'startSpin' ) );

		add_action( 'wp_ajax_spin_claim_gh', array( $this, 'spinClaim' ) );
		add_action( 'wp_ajax_nopriv_spin_claim_gh', array( $this, 'spinClaim' ) );

		add_shortcode( 'spin_gh', array( $this, 'spinFrontHtml' ) );
		add_shortcode( 'spin-gh', array( $this, 'spinFrontHtml' ) );

		add_action( 'wp_footer', array( $this, 'spinFrontGlobalHtml' ) );
	}

	public function registerScripts() {
		global $post;
		$min = WP_DEBUG ? '' : '.min';
		if ( isset( $post->ID ) ) {
			wp_register_style( 'spin_style_gh', get_site_url() . "?gh_spin_style&post_id={$post->ID}" );
		}
		wp_register_style( 'spin_front_gh', SPIN_URL . "assets/styles/style{$min}.css" );
		wp_register_script( 'spin_script_gh', SPIN_URL . "assets/scripts/script{$min}.js", array( 'jquery' ), null, true );
		wp_localize_script( 'spin_script_gh', 'spinGh', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'l10n'    => array(
				'nameRequired'  => __( 'Your name is required', 'wp-spinner' ),
				'emailRequired' => __( 'Your email is required', 'wp-spinner' ),
				'emailNotValid' => __( 'Not a valid email', 'wp-spinner' ),
			)
		) );
	}


	public function spinClaim() {
		$nonce = isset( $_POST['_gh'] ) ? $_POST['_gh'] : '';
		if ( wp_verify_nonce( $nonce, 'spin_mail_gh_none' ) ) {
			if ( session_id() == '' ) {
				session_start();
			}


			$spin_id    = $_SESSION['spin_gh']['spin_id'];
			$user_email = $_SESSION['spin_gh']['user_email'];
			$win_number = $_SESSION['spin_gh']['win_number'];


			if ( $spin_id && $user_email ) {
				global $sp_gh, $sp_db, $wc_gh;

				$option = $sp_gh->getSpinOptions( $spin_id );
				$user   = $sp_db->getData( $spin_id, $user_email );
				$prize  = $option['items'][ $win_number ];


				$prize_data = array(
					'text' => $prize['text'],
					'type' => $prize['type'],
				);


				if ( $prize['type'] == 'product' ) {
					$wc_gh->generateOrder( $prize['obj_id'], $user_email, $user->user_name );
					$prize_data['obj_id'] = $prize['obj_id'];
				}


				$this->mail( $option, $user );

				$sp_db->claim( $spin_id, $user_email, $prize_data );

				unset( $_SESSION['spin_gh'] );

				wp_die( json_encode( array(
					'status'  => 'ok',
					'message' => '',
				) ) );

			} else {
				wp_die( json_encode( array(
					'status'  => 'error',
					'message' => __( 'Something Went Wrong!', 'wp-spinner' )
				) ) );
			}
		} else {
			wp_die( json_encode( array(
				'status'  => 'error',
				'message' => __( 'Nonce Error ', 'wp-spinner' )
			) ) );
		}
	}


	private function mail( $option, $user ) {
		global $sp_gh;
		if ( session_id() == '' ) {
			session_start();
		}

		$win_number  = $_SESSION['spin_gh']['win_number'];
		$current_win = $option['items'][ $win_number ];

		if ( $current_win != 'no_prize' ) {
			$message = $option['email']['lose'];
			$subject = $option['email']['lose_subject'];
		} else {
			$message = $option['email']['win'];
			$subject = $option['email']['win_subject'];
		}


		$message = $sp_gh->doShortCode( $message, $current_win, true );
		$subject = $sp_gh->doShortCode( $subject, $current_win, true );


		$header = "MIME-Version: 1.0" . "\r\n" .
		          "Content-type:text/html;charset=UTF-8" . "\r\n";


		if ( wp_mail( $user->email, $subject, $message, $header ) ) {
			return true;
		}

		return false;
	}

	public function startSpin() {
		if ( session_id() == '' ) {
			session_start();
		}

		global $sp_gh, $sp_db;


		$spin_id = isset( $_POST['spin_id'] ) ? (int) $_POST['spin_id'] : exit( json_encode( array(
			'status'  => 'error',
			'message' => __( 'Something went wrong', 'wp-spinner' )
		) ) );

		$user_name = isset( $_POST['name'] ) ? $_POST['name'] : exit( json_encode( array(
			'status'  => 'error',
			'message' => __( 'Something went wrong', 'wp-spinner' )
		) ) );

		$user_email = isset( $_POST['email'] ) ? $_POST['email'] : exit( json_encode( array(
			'status'  => 'error',
			'message' => __( 'Something went wrong', 'wp-spinner' )
		) ) );


		$_SESSION['spin_gh'] = array(
			'user_email' => $user_email,
			'user_name'  => $user_name,
			'spin_id'    => $spin_id,
			'win_number' => null
		);


		$options    = $sp_gh->getSpinOptions( $spin_id );
		$win_number = $this->generateWinNumber( $options['items'] );
		$win        = $options['items'][ $win_number ];


		$status = $sp_db->insertUserSpin( $spin_id, $user_email, $user_name, $options['max_spin_count'] );

		if ( isset( $status['success'] ) && $status['success'] ) {
			$win_message = $win['type'] != 'no_prize' ? $options['result']['win_message'] : $options['result']['lose_message'];

			$_SESSION['spin_gh']['win_number'] = $win_number;
			$item_image                        = $win['image'] ? wp_get_attachment_image( $win['image'], 'medium', false, array( 'class' => 'win_image' ) ) :
				'<img src="' . SPIN_URL . 'assets/images/no-image.png"  class="win_image"/>';
			$data                              = array(
				'status'    => 'ok',
				'winNumber' => $win_number,
				'itemText'  => $win['text'],
				'type'      => $win['type'],
				'itemImage' => $item_image,
				'lastSpin'  => $status['is_last_spin'],
				'message'   => $sp_gh->doShortCode( $win_message, $win )
			);
		} else {
//            unset($_SESSION['spin_gh']);
			$data = array(
				'status'  => 'error',
				'message' => $sp_gh->doShortCode( $status['error_msg'] )
			);
		}

		wp_die( json_encode( $data ) );
	}


	private function generateWinNumber( $spin_items ) {
		$priorities = array();

		if ( ! empty( $spin_items ) && is_array( $spin_items ) ) {
			foreach ( $spin_items as $key => $val ) {
				$priorities[ $key ] = $val['priority'];
			}
		}

		$numbers = array();
		foreach ( $priorities as $k => $v ) {
			for ( $i = 0; $i < $v; $i ++ ) {
				$numbers[] = $k;
			}
		}

		$win_number = ! empty( $numbers ) ? $numbers[ array_rand( $numbers ) ] : '';

		return $win_number;
	}


	public function spinFrontHtml( $params ) {
		global $sp_gh;
		$spin_id = $params['id'];
		if ( 'publish' != get_post_status( $spin_id ) ) {
			return '';
		}

		$global  = isset( $params['global'] ) ? 'global' : '';
		$options = $sp_gh->getSpinOptions( $spin_id );

		wp_enqueue_script( 'spin_script_gh' );
		if ( ! $global ) {
			wp_enqueue_style( 'spin_style_gh' );
		}
		wp_enqueue_style( 'spin_front_gh' );
		ob_start();
		?>
        <div class="spin_game_gh template_<?php echo $options['template'] ?>"
             id="spin_game_gh_<?php echo $spin_id . '_' . $global ?>"
             data-duration="<?php echo $options['duration'] ?>">
            <div class="spin_overlay">
				<?php
				$this->spinResult( $spin_id, $options['result'] );
				$this->spinForm( $spin_id, $options['form'] );
				?>
            </div>
            <div class="spinwheel">
				<?php $sp_gh->spinHtml( $spin_id, $global ); ?>
            </div>

        </div>

		<?php
		return ob_get_clean();
	}

	private function spinForm( $spin_id, $form ) {
		global $sp_gh;
		$bg_image = $bg = '';
		if ( $form['bg_image'] ) {
			$img_op   = $form['bg_image_visibility'] / 100;
			$bg_image = '<div class="bg_image" style="background-image: url(' . $form['bg_image'] . '); opacity: ' . $img_op . '"></div>';
		}
		if ( $form['bg_color'] ) {
			$op = $form['bg_color_visibility'] / 100;
			$bg = '<div class="bg" style="background-color:' . $form['bg_color'] . '; opacity: ' . $op . '"></div>';
		}
		?>

        <div class="gh_spin_form">
			<?php echo $bg_image . $bg ?>
            <div class="center">
                <div class="welcome_message">
					<?php echo wpautop( $form['text'] ); ?>
                </div>
                <form class="claimform" type="POST">
                    <input type="hidden" name="spin_id" value="<?php echo $spin_id ?>"/>
                    <input type="hidden" name="action" value="spin_claim_gh"/>
					<?php wp_nonce_field( 'spin_mail_gh_none', '_gh' ) ?>
                    <label>
                        <input type="text" name="name" placeholder=" " required>
                        <span class="placeholder"><?php _e( 'Name', 'wp-spinner' ) ?></span>
                        <span class="error_message"></span>
                    </label>
                    <label>
                        <input type="email" name="email" placeholder=" " required>
                        <span class="placeholder"><?php _e( 'E-mail', 'wp-spinner' ) ?></span>
                        <span class="error_message"></span>
                    </label>
                    <input type="button" value="<?php _e( 'Play!', 'wp-spinner' ) ?>" class="playnow"
                           style="<?php echo 'background-color:' . $form['play_button_color']; ?>">
                    <br>
                </form>
            </div>

        </div>
		<?php
	}

	private function spinResult( $spin_id, $result ) {
		$bg_image = $bg = '';
		if ( $result['bg_image'] ) {
			$img_op   = $result['bg_image_visibility'] / 100;
			$bg_image = '<div class="bg_image" style="background-image: url(' . $result['bg_image'] . '); opacity: ' . $img_op . '"></div>';
		}
		if ( $result['bg_color'] ) {
			$op = $result['bg_color_visibility'] / 100;
			$bg = '<div class="bg" style="background-color:' . $result['bg_color'] . '; opacity: ' . $op . '"></div>';
		}
		?>
        <div class="spin_result leave">
			<?php echo $bg_image . $bg ?>
            <div class="image_wrapper">
                <img src="<?php echo SPIN_URL ?>assets/images/success.png" alt="success" class="success leave"/>
            </div>
            <div class="message_area"></div>
            <div class="buttons">
                <input type="button" class="claim" value="<?php _e( 'CLAIM', 'wp-spinner' ) ?>"
                       style="<?php echo 'background-color:' . $result['claim_color']; ?>">
                <input type="button" class="restart" value="<?php _e( 'Try again', 'wp-spinner' ) ?>"
                       style="<?php echo 'background-color:' . $result['retry_color']; ?>">
            </div>
        </div>

		<?php
	}


	function spinFrontGlobalHtml() {
		global $sp_gh;

		$spin_id = null;
		if ( is_singular() ) {
			$spin_id = (int) get_post_meta( get_the_ID(), 'gh_spin_id', true );
		}


		if ( ! $spin_id || 'publish' != get_post_status( $spin_id ) ) {
			$opt = $sp_gh->getGlobalOptions();

			if ( $opt['show_spin_popup'] ) {
				$exclude_ids = $opt['spin_exclude_ids'] ? array_map( function ( $id ) {
					return (int) $id;
				}, $opt['spin_exclude_ids'] ) : array();

				if ( ! in_array( get_the_ID(), $exclude_ids ) ) {
					$spin_id = (int) $opt['popup_spin_id'];
				}
			}
		}

		if ( ! $spin_id || 'publish' != get_post_status( $spin_id ) ) {
			return;
		}

		$option = $sp_gh->getSpinOptions( $spin_id );


		wp_enqueue_style( 'spin_style_global_gh', get_site_url() . "?gh_spin_style&spin_id={$spin_id}" );

		?>
        <div id="spin_open_global" class="<?php echo $option['open_button']['position'] ?>"
             data-timeout="<?php echo (int) $option['open_button']['open_timeout'] ?>">
            <span class="text"><?php echo $option['open_button']['text'] ?></span>
            <span class="open">
                <img class="spin" width="100" height="100" alt="spin button"
                     src="<?php echo SPIN_URL ?>assets/images/spin_button.png">
                <img class="spin_border" width="100" height="100" alt="spin button"
                     src="<?php echo SPIN_URL ?>assets/images/spin_button_border.png">
            </span>
        </div>
        <div id="spin_global_popup" style="display: none">
            <div id="spin_close_global">
                <img width="50" height="50" src="<?php echo SPIN_URL ?>/assets/images/close.png" alt="close">
            </div>
			<?php echo do_shortcode( '[spin_gh id="' . $spin_id . '" global="true"]' ) ?>
        </div>
		<?php
	}
}

$sp_front = new SpinFrontGh();