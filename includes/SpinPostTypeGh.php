<?php
defined( "ABSPATH" ) or exit;

class SpinPostTypeGh {

	public function __construct() {
		add_action( 'init', array( $this, 'postTypeRegister' ) );

		add_action( 'plugins_loaded', array( $this, 'textDomain' ) );
	}


	public function textDomain() {
		load_plugin_textdomain( 'wp-spinner', false, SPIN_DIR_NAME . '/languages/' );
	}


	public function postTypeRegister() {
		$args = array(
			'labels'              => array(
				'name'               => __( 'WpSpinner', 'wp-spinner' ),
				'singular_name'      => __( 'WpSpinner', 'wp-spinner' ),
				'menu_name'          => _x( 'WpSpinner', 'admin menu', 'wp-spinner' ),
				'name_admin_bar'     => _x( 'Spinner', 'add new on admin bar', 'wp-spinner' ),
				'add_new'            => _x( 'New Spinner', 'Spin', 'wp-spinner' ),
				'add_new_item'       => __( 'Add New', 'wp-spinner' ),
				'new_item'           => __( 'New Spinner', 'wp-spinner' ),
				'edit_item'          => __( 'Edit Spinner', 'wp-spinner' ),
				'view_item'          => __( 'View Spinner', 'wp-spinner' ),
				'all_items'          => __( 'All Spinners', 'wp-spinner' ),
				'not_found'          => __( 'No Spinners found.', 'wp-spinner' ),
				'not_found_in_trash' => __( 'No Spinners found in Trash.', 'wp-spinner' ),
			),
			'supports'            => array( 'title' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'menu_icon'           => 'dashicons-spin_gh',
		);

		register_post_type( 'spin_game_gh', $args );
	}


	public function generateSpinCssRequest( $option ) {
		$colors = array();
		foreach ( $option['items'] as $item ) {
			$colors[] = $item['color'];
		}
		$req = array(
			'colors'           => implode( ';', $colors ),
			'spin_items_count' => $option['spin_items_count'],
			'duration'         => $option['duration'],
			'size'             => $option['size'],
			'img_size'         => $option['img_size'],
			'img_y'            => $option['img_y'],
			'img_x'            => $option['img_x'],
			'text_position'    => $option['text_position'],
			'border'           => $option['border_size'] . ';' . $option['border_color'],
			'rotate'           => $option['rotate'],
			'font_size'        => $option['font_size'],
			'template'         => $option['template'],
		);

		return http_build_query( $req );
	}


	public static function getGlobalOptions() {
		static $globalOption = array();

		if ( empty( $globalOption ) ) {
			$defaults = array(
				'show_spin_popup'  => 0,
				'popup_spin_id'    => 0,
				'spin_exclude_ids' => array(),
				'mailchimp'        => array(
					'key'    => '',
					'list'   => '',
					'status' => false,
				),
				'zapier'           => array(
					'web_hook' => '',
					'status'   => false,
				),
				'remarkety'        => array(
					'store_id' => '',
					'status'   => false,
				),
				'active_campaign'  => array(
					'url'    => '',
					'key'    => '',
					'list'   => '',
					'form'   => '',
					'status' => false,
				),
			);

			$opt = get_option( 'spin_global_option_gh', array() );
			foreach ( $defaults as $key => $default ) {
				if ( ! empty( $opt[ $key ] ) ) {
					if ( is_array( $opt[ $key ] ) ) {
						foreach ( $defaults[ $key ] as $k => $v ) {
							if ( ! empty( $opt[ $key ][ $k ] ) ) {
								$globalOption[ $key ][ $k ] = $opt[ $key ][ $k ];
							} else {
								$globalOption[ $key ][ $k ] = $v;
							}
						}
					} else {
						$globalOption[ $key ] = $opt[ $key ];
					}
				} else {
					$globalOption[ $key ] = $default;
				}
			}
		}

		return $globalOption;

	}

	public static function getSpinOptions( $id ) {
		static $spinOption = array();

		if ( empty( $spinOption[ $id ] ) ) {
			$colors = array( '#dc0936', '#e5177b', '#be107f', '#881f7e', '#3f297e', '#1d61ac', '#169ed8', '#209b6c' );
			$items  = array();
			foreach ( $colors as $key => $color ) {
				$i           = $key + 1;
				$items[ $i ] = array(
					'color'    => $color,
					'obj_id'   => 0,
					'type'     => 'custom',
					'show'     => 'text',
					'text'     => 'Text ' . $i,
					'image'    => 0,
					'priority' => 1
				);
			}
			$spinOption[ $id ] = array(
				'items'                 => $items,
				'spin_items_count'      => 8,
				'duration'              => 10000,
				'size'                  => 500,
				'img_size'              => 30,
				'img_y'                 => - 8,
				'img_x'                 => 110,
				'text_position'         => 20,
				'template'              => 1,
				'border_size'           => 0,
				'border_color'          => '#8224e3',
				'rotate'                => - 90,
				'max_spin_count'        => 3,
				'font_size'             => 10,
				'spins_per_period'      => 1,
				'spins_per_period_type' => 'day',
				'result'                => array(
					'win_message'         => __( 'Congratulations! You won the [win] . Click on the Claim button and enjoy your prize.', 'wp-spinner' ),
					'lose_message'        => __( 'I\'m sorry but you have missed your chance, try again next time and probably you will win', 'wp-spinner' ),
					'max_try_message'     => __( 'You have [user_spins_count] more times  left to try your luck.', 'wp-spinner' ),
					'claimed_error'       => __( 'Congratulations, you have selected [win] prize!', 'wp-spinner' ),
					'bg_color'            => '#607d8b',
					'bg_color_visibility' => '95',
					'bg_image'            => '',
					'bg_image_visibility' => '95',
					'claim_color'         => '#2196f3',
					'retry_color'         => '#ff9800',
				),
				'form'                  => array(
					'bg_color'            => '#013993',
					'bg_color_visibility' => '95',
					'bg_image'            => '',
					'bg_image_visibility' => '95',
					'play_button_color'   => '#1abc2d',
					'text'                => '',
				),
				'email'                 => array(
					'win'          => '<table style="border-collapse:collapse;width:700px;font-family:Arial,Helvetica,sans-serif;background-color:#f0f0f0;border-radius:13px;"><tr style="background-color:#9e9e9e;"><td><img style="width:100px;height:34px;padding:20px 10px;" src="' . SPIN_URL . 'assets/images/wp-spinner.png" alt="wp-spinner" /></td><td style="font-size:22px;color:#fff;text-align:center;">If you have received this message then today is your day!</td></tr><tr><td style="font-size:28px;text-align:center;" colspan="2">Congratulations! You won [win] .</td></tr><tr><td style="font-size:18px;color:#949499;text-align:center;" colspan="2">You can use your prize within 2 days</td></tr><tr><td style="font-size:18px;height:75px;color:#c597f3;text-align:center;" colspan="2">If you are feeling lucky then you can come back and try your fortune after [try_again_time] days.</td></tr><tr><td style="height:50px;text-align:center;" colspan="2"><a style="text-decoration:none;color:#fff; padding:7px;border-radius:5px;background-color:darkgrey;cursor:pointer;object-fit:contain;" href="' . get_site_url() . '">Use Now</a></td></tr></table>',
					'win_subject'  => 'You Win!',
					'lose'         => '<table style="border-collapse:collapse;width:700px;font-family:Arial,Helvetica,sans-serif;background-color:#f0f0f0;border-radius:13px;"><tr style="background-color:#9e9e9e;"><td><img class="aligncenter" style="width:100px;height:34px;padding:20px 10px;" src="' . SPIN_URL . 'assets/images/wp-spinner.png" alt="wp-spinner" /></td><td style="font-size:22px;color:#fff;text-align:center;">Unfortunately you have not won anything today!</td></tr><tr><td style="font-size:18px;height:75px;color:#c597f3;text-align:center;" colspan="2">If you are feeling lucky then you can come back and try your fortune after [try_again_time] days.</td></tr><tr><td style="height:50px;text-align:center;" colspan="2"><a style="text-decoration:none;color:#fff;padding:7px;border-radius:5px;background-color:darkgrey;cursor:pointer;" href="' . get_site_url() . '">Go Back</a></td></tr></table>',
					'lose_subject' => 'You Lose!',
				),
				'related_posts'         => array(),
				'open_button'           => array(
					'position'     => 'bottom right',
					'text'         => __( 'Try your luck', 'wp-spinner' ),
					'open_timeout' => - 1
				),
				'sounds'                => array(
					'active'     => 0,
					'win'        => '',
					'lose'       => '',
					'background' => '',
					'spin'       => '',

				)
			);

			foreach ( $spinOption[ $id ] as $key => $default ) {

				$result = get_post_meta( $id, 'gh_spin_' . $key, true );
				if ( $result !== '' ) {
					if ( is_array( $result ) ) {
						if ( $key == 'items' ) {
							$spinOption[ $id ][ $key ] = array();
						}
						foreach ( $result as $k => $v ) {
							$spinOption[ $id ][ $key ][ $k ] = $v;
						}
					} else {
						$spinOption[ $id ][ $key ] = $result;
					}
				}
			}
		}

		return $spinOption[ $id ];
	}


	public function spinHtml( $id, $global = '' ) {
		$option = $this->getSpinOptions( $id );
		?>
        <div id="gh_roulette_<?php echo $id . '_' . $global ?>" class="roulette">
            <div class="pres_spin_anim">
                <div class="gh_spinner">
					<?php foreach ( $option['items'] as $key => $item ): ?>
                        <div class="triangle" data-id="<?php echo $key ?>">
                <span class="content">
                    <span>
                        <span class="text">
                            <?php
                            $text = $item['text'] ? $item['text'] : '&nbsp;';
                            if ( isset( $item['show'] ) && ( 'text' == $item['show'] || 'image_text' == $item['show'] ) ) {
	                            echo $text;
                            } else {
	                            echo '&nbsp;';
                            }
                            ?>
                        </span>
                        <span class="image">
                            <?php
                            if ( $option['template'] != 4 ) {
	                            if ( $item['show'] == 'image' || $item['show'] == 'image_text' ) {
		                            if ( $img = wp_get_attachment_image( $item['image'], 'thumbnail' ) ) {
			                            echo $img;
		                            } else {
			                            echo '<img src="' . SPIN_URL . 'assets/images/no-image.png" />';
		                            }
	                            }
                            }
                            ?>
                        </span>
                    </span>
                </span>
                        </div>
					<?php endforeach; ?>
                </div>
            </div>
            <div class="spin-start spin">
                <span class="t-spin"> <?php _e( 'Spin', 'wp-spinner' ) ?></span>
            </div>
        </div>

		<?php
	}


	public function doShortCode( $text, $win_data = false, $is_email = false ) {
		global $sp_db;
		if ( session_id() == '' ) {
			session_start();
		}

		$spin_gh = $_SESSION['spin_gh'];
		if ( ! $win_data ) {
			$spin_data = $sp_db->getData( $spin_gh['spin_id'], $spin_gh['user_email'] );

			if ( $spin_data ) {
				$win_data = maybe_unserialize( $spin_data->win );
			}
		}
		if ( ! empty( $win_data ) ) {
			if ( 'product' == $win_data['type'] ) {
				$product  = wc_get_product( (int) $win_data['obj_id'] );
				$win_text = '<a href="' . get_permalink( $win_data['obj_id'] ) . '" target="_blank">' . $product->get_title() . '</a>';
			} else {
				$win_text = $win_data['text'];
			}
			$text = str_replace( '[win]', $win_text, $text );
		} else {
			$text = str_replace( '[win]', '', $text );
		}

		if ( ! empty( $spin_gh['user_email'] ) ) {
			$text = str_replace( '[available_spins_count]', $sp_db->getAvailableSpinsCount( $spin_gh['spin_id'], $spin_gh['user_email'], $win_data['max_spin_count'] ), $text );
			$text = str_replace( '[user_spins_count]', $sp_db->getUserSpinsCount( $spin_gh['spin_id'], $spin_gh['user_email'], false ), $text );
			$text = str_replace( '[try_again_time]', $sp_db->getUserTryAgainTime( $spin_gh['spin_id'], $spin_gh['user_email'] ), $text );

			$text = str_replace( '[user_email]', $spin_gh['user_email'], $text );
			$text = str_replace( '[user_name]', $spin_gh['user_name'], $text );
		}


		//remove other shortCodes
		$text = preg_replace( '/\[[^\]]+\]/', '', $text );

		return $text;
	}


}

$sp_gh = new SpinPostTypeGh();

