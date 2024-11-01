<?php
defined( "ABSPATH" ) or exit;

class SpinAdminGh {

	function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'metaBoxes' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'registerScripts' ) );

		add_action( 'save_post', array( $this, 'saveOptions' ) );

		add_filter( 'manage_spin_game_gh_posts_columns', array( $this, 'shortCodeColumns' ) );

		add_action( 'manage_spin_game_gh_posts_custom_column', array( $this, 'manageShortCodeColumns' ), 10, 2 );

		add_action( 'admin_menu', array( $this, 'addSubmenuPage' ) );

		add_action( 'in_admin_footer', array( $this, 'spinIconStyle' ) );

		add_action( 'in_admin_header', array( $this, 'moveIcon' ) );

		add_action( 'init', array( $this, 'downloadSvg' ) );

		add_action( 'admin_notices', array( $this, 'noWooCommerceNotice' ) );

		add_action( 'check_ajax_referer', array( $this, 'disableMetaBoxesOrderChange' ) );


		add_filter( 'mce_buttons', array( $this, 'addShortCodeButton' ), 10, 2 );

		add_filter( 'mce_external_plugins', array( $this, 'registerShortCodeButtonScript' ) );
	}


	public function noWooCommerceNotice() {
		global $wc_gh;
		$current_screen = get_current_screen();
		if ( 'spin_game_gh' == $current_screen->id ) {
			if ( ! $wc_gh->wcActive() ) {
				$this->alert( 'warning', __( 'For full functionality please install WooCommerce plugin.', 'wp-spinner' ) );
			}
		}
	}

	public function downloadSvg() {
		if ( isset( $_REQUEST['download_spin_svg'] ) ) {
			if ( current_user_can( 'administrator' ) ) {
				$scv_file = SPIN_DIR . 'assets/csv/emails_list.csv';
				if ( file_exists( $scv_file ) ) {
					exit( @file_get_contents( $scv_file ) );
				}
				exit();
			} else {
				exit( __( 'Permission denied!', 'wp-spinner' ) );
			}
		}
	}

	public function addSubmenuPage() {
		add_submenu_page( 'edit.php?post_type=spin_game_gh', __( 'Spin Members', 'wp-spinner' ), __( 'Spin Members', 'wp-spinner' ),
			'manage_options', 'spin_history_gh', array( $this, 'spinHistory' ) );

		add_submenu_page( 'edit.php?post_type=spin_game_gh', __( 'Settings', 'wp-spinner' ), __( 'Settings', 'wp-spinner' ),
			'manage_options', 'spin_global_gh', array( $this, 'spinGlobal' ) );

	}

	public function disableMetaBoxesOrderChange( $action ) {
		if ( 'meta-box-order' == $action && 'spin_game_gh' == $_POST['page'] ) {
			die( '-1' );
		}
	}

	public function metaBoxes() {
		add_meta_box( 'spin_game_preview', __( 'Spin', 'wp-spinner' ), array( $this, 'previewArea' ),
			'spin_game_gh', 'normal' );

		add_meta_box( 'spin_game_items', __( 'Items', 'wp-spinner' ), array( $this, 'itemsArea' ),
			'spin_game_gh', 'normal' );

		add_meta_box( 'spin_game_options', __( 'Options', 'wp-spinner' ), array( $this, 'optionsArea' ),
			'spin_game_gh', 'side' );

		add_meta_box( 'spin_game_form', __( 'Form Options', 'wp-spinner' ), array( $this, 'formOptionsArea' ),
			'spin_game_gh', 'normal' );

		add_meta_box( 'spin_game_result', __( 'Spin Result', 'wp-spinner' ), array( $this, 'spinResultArea' ),
			'spin_game_gh', 'normal' );

		add_meta_box( 'spin_game_email', __( 'Email template', 'wp-spinner' ), array( $this, 'spinEmailArea' ),
			'spin_game_gh', 'normal' );

		add_meta_box( 'spin_game_sounds', __( 'Sounds', 'wp-spinner' ), array( $this, 'spinSoundsArea' ),
			'spin_game_gh', 'normal' );
	}

	public function spinHistory() {
		$table = new SpinMembersGh();
		$table->prepare_items();
		wp_enqueue_style( 'spin_admin-style-gh' );
		?>
        <div class="wrap">
            <h2>
				<?php _e( 'Spin Members', 'wp-spinner' ) ?>
            </h2>
            <form id="spin_history_form" method="GET">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
                <input type="hidden" name="post_type" value="<?php echo $_REQUEST['post_type'] ?>"/>

				<?php $table->display() ?>
            </form>

        </div>
		<?php
	}

	public function spinGlobal() {
		global $sp_gh;
		if ( isset( $_POST['spin_settings_edit'] ) ) {
			$this->saveGlobalOptions();
		}
		wp_enqueue_style( 'select2' );
		wp_enqueue_style( 'spin_admin-style-gh' );

		wp_enqueue_script( 'select2' );
		wp_enqueue_script( 'spin_admin-script-gh' );

		$opt = $sp_gh->getGlobalOptions();
		?>
        <div id="spin_global_gh" class="wrap">
            <h2><?php _e( 'Spinner Settings', 'wp-spinner' ) ?></h2>

            <form method="post" action="">
                <table cellpadding="10" class="wp-list-table widefat striped">
                    <tr>
                        <th colspan="2"><h3><?php _e( 'Spinner Global Settings', 'wp-spinner' ) ?></h3></th>
                    </tr>
                    <tr>
                        <td>
                            <label for="show_spin_popup">
                                <b><?php _e( 'Enables Spin in all the pages', 'wp-spinner' ) ?></b>
                            </label>
                        </td>
                        <td>
                            <input id="show_spin_popup" type="checkbox" name="show_spin_popup" value="1"
								<?php echo $opt['show_spin_popup'] ? 'checked=""' : '' ?>/>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:20%">
							<?php _e( 'Show spin popup button', 'wp-spinner' ) ?>
                        </td>
                        <td style="width:80%">
                            <select name="popup_spin_id">
								<?php
								$spins  = get_posts(
									array(
										'post_type'      => 'spin_game_gh',
										'post_status'    => 'publish',
										'posts_per_page' => - 1
									)
								);
								$sel_id = (int) $opt['popup_spin_id'];
								if ( ! empty( $spins ) ) {
									foreach ( $spins as $spin ) {
										$id       = $spin->ID;
										$title    = $spin->post_title ? $spin->post_title : __( '(no title)', 'wp-spinner' );
										$selected = $sel_id == $id ? 'selected=""' : '';
										echo '<option value="' . $id . '" ' . $selected . '>'
										     . esc_html( $title ) . " #$id"
										     . '</option>';
									}
								} ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
							<?php _e( 'Exclude post IDs', 'wp-spinner' ) ?>
                        </td>
                        <td>
							<?php echo $this->getPostsList( 'spin_exclude_ids[]', $opt['spin_exclude_ids'] ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding:0">
                            <img style="width:100%;object-fit:contain;max-height:100%;object-position:left top;"
                                 src="<?php echo SPIN_URL ?>assets/images/pro-settings.png"/>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
							<?php submit_button( __( 'Save', 'wp-spinner' ), 'primary large' ); ?>
							<?php wp_nonce_field( 'spin_global', '_gh' ); ?>
                            <input type="hidden" name="spin_settings_edit" value="1">
                        </td>
                    </tr>

                </table>
            </form>
        </div>


		<?php
	}


	public function registerScripts() {
		global $post, $sp_gh;

		if ( $post ) {
			$option  = $sp_gh->getSpinOptions( $post->ID );
			$request = $sp_gh->generateSpinCssRequest( $option );
			wp_register_style( 'spin_style_gh', SPIN_URL . "includes/style.php?{$request}" );

		}

		wp_register_style( 'spin_admin-style-gh', SPIN_URL . "assets/styles/admin-style.css" );

		wp_register_style( 'select2', SPIN_URL . 'assets/styles/select2.min.css', array(), '4.0.5' );


		wp_register_script( 'select2', SPIN_URL . 'assets/scripts/select2.full.min.js', array(), '4.0.5' );

		wp_register_script( 'spin_admin-script-gh', SPIN_URL . "assets/scripts/admin-script.js",
			array( 'jquery', 'wp-color-picker', 'select2' ) );

		wp_localize_script( 'spin_admin-script-gh', 'SpinData',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'pluginUrl'      => SPIN_URL,
				'noImage'        => SPIN_URL . 'assets/images/no-image.png',
				'noImageCropped' => SPIN_URL . 'assets/images/no-image-cropped.png',
				'l10n'           => array(
					'WinText'             => __( 'Win Text', 'wp-spinner' ),
					'AvailableSpinsCount' => __( 'Available Spins Count', 'wp-spinner' ),
					'UserSpinsCount'      => __( 'User Spins Count', 'wp-spinner' ),
					'UserName'            => __( 'User Name', 'wp-spinner' ),
					'UserEmail'           => __( 'User Email', 'wp-spinner' ),
					'TryAgainExpireTime'  => __( 'Try Again After Expire Time', 'wp-spinner' ),
					'InsertShortCode'     => __( 'Insert ShortCode', 'wp-spinner' ),
				)
			) );

	}

	public function moveIcon() {
		?>
        <img width="30" height="30" src="<?php echo SPIN_URL ?>assets/images/move.png" class="spin_image_move_gh"
             style="display: none"/>
		<?php
	}

	public function previewArea( $post ) {
		global $sp_gh;
		wp_enqueue_style( 'select2' );
		wp_enqueue_style( 'spin_style_gh' );
		wp_enqueue_style( 'spin_admin-style-gh' );
		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script( 'select2' );
		wp_enqueue_script( 'spin_admin-script-gh' );
		did_action( 'wp_enqueue_media' ) or wp_enqueue_media();


		$sp_gh->spinHtml( $post->ID );

	}

	public function itemsArea( $post ) {
		global $wc_gh, $sp_gh;
		$option = $sp_gh->getSpinOptions( $post->ID );
		?>
        <div id="spin_items_wrapper">
            <label class="tab_mode">
                <span>Tab mode:</span>
                <input id="gh_tab_mode" type="checkbox"/>
            </label>
            <div class="nav_bar_wrapper" style="display:none"></div>

			<?php foreach ( $option['items'] as $key => $item ): ?>
                <div class="item_row" data-id="<?php echo $key ?>">
                    <h4>
						<?php echo __( 'Item', 'wp-spinner' ) . ' ' . $key ?>
                    </h4>
                    <div class="opt">
                        <div class="gh_type">
                            <div><?php _e( 'Prize Type', 'wp-spinner' ) ?></div>
                            <select name="items[<?php echo $key ?>][type]">
								<?php
								$type        = $item['type'];
								$wc_disabled = $wc_gh->wcActive() ? '' : ' disabled=""';
								?>
                                <option value="product" <?php echo $type == 'product' ? "selected" : '' ?> <?php echo $wc_disabled ?>>
									<?php _e( 'Product', 'wp-spinner' ) ?>
                                </option>
                                <option disabled>
									<?php _e( 'Coupon', 'wp-spinner' ) ?>
									<?php _e( ' (PRO)', 'wp-spinner' ) ?>
                                </option>
                                <option value="custom" <?php echo $type == 'custom' ? "selected" : '' ?>>
									<?php _e( 'Custom', 'wp-spinner' ) ?>
                                </option>
                                <option value="no_prize" <?php echo $type == 'no_prize' ? "selected" : '' ?>>
									<?php _e( 'No Prize', 'wp-spinner' ) ?>
                                </option>
                            </select>

                        </div>
						<?php if ( $wc_gh->wcActive() ) : ?>
                            <div class="gh_product_wrapper select2_wrapper_gh">
                                <div><?php _e( 'Products', 'wp-spinner' ) ?></div>
								<?php echo $wc_gh->getProductList( (int) $item['obj_id'] ) ?>
                            </div>
						<?php endif; ?>
                        <input type="hidden" class="obj_id"
                               name="items[<?php echo $key ?>][obj_id]"
                               value="<?php echo (int) $item['obj_id'] ?>">
                        <div class="gh_show">
                            <div><?php _e( 'Display', 'wp-spinner' ) ?></div>
							<?php $s = ! empty( $item['show'] ) ? $item['show'] : '' ?>
                            <select name="items[<?php echo $key ?>][show]">
                                <option value="text" <?php echo $s == 'text' ? "selected" : '' ?> >
									<?php _e( 'Text', 'wp-spinner' ) ?>
                                </option>
                                <option value="image" <?php echo $s == 'image' ? "selected" : '' ?>>
									<?php _e( 'Image', 'wp-spinner' ) ?>
                                </option>
                                <option value="image_text" <?php echo $s == 'image_text' ? "selected" : '' ?>>
									<?php _e( 'Image And Name', 'wp-spinner' ) ?>
                                </option>
                            </select>
                        </div>

                        <div class="gh_text">
                            <div><?php _e( 'Name', 'wp-spinner' ) ?></div>
                            <input type="text" name="items[<?php echo $key ?>][text]"
                                   value="<?php echo $item['text'] ?>"/>
                        </div>
                        <div class="gh_color">
                            <div><?php _e( 'Color', 'wp-spinner' ) ?></div>
                            <input type="text" class="gh_color_picker"
                                   name="items[<?php echo $key ?>][color]"
                                   value="<?php echo $item['color'] ?>"/>
                        </div>
                        <div class="gh_priority">
                            <div><?php _e( 'Priority', 'wp-spinner' ) ?></div>
                            <input type="number" min="0" max="999" name="items[<?php echo $key ?>][priority]"
                                   value="<?php echo $item['priority'] ?>"/>
                        </div>
                        <div class="gh_image">
                            <div><?php _e( 'Image', 'wp-spinner' ) ?></div>
                            <div class="buttons">
                                <button class="add_image btn-green-gh">
                                    <span class="dashicons dashicons-plus"></span>
                                </button>
                                <button class="remove_image btn-red-gh">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                            <div class="image_wrapper">
								<?php echo wp_get_attachment_image( $item['image'], 'spin_gh', false, array( "class" => "custom_media_image" ) ); ?>
                            </div>
							<?php
							$real_image             = wp_get_attachment_image_url( $item['image'], 'thumbnail' );
							$cropped_image          = wp_get_attachment_image_url( $item['image'], 'spin_gh_cropped' );
							$cropped_image          = $cropped_image ? add_query_arg( array( 'v' => '0.' . rand( 0, 9999999 ) ), $cropped_image ) : '';
							$image_crop_details     = get_post_meta( $item['image'], 'spin_crop_details', true );
							$image_crop_details_str = $image_crop_details ? json_encode( $image_crop_details ) : '';
							?>
                            <input type="hidden" class="spin_crop_details"
                                   value="<?php echo esc_attr( $image_crop_details_str ) ?>"/>
                            <input type="hidden" class="real_image"
                                   value="<?php echo $real_image ?>"/>
                            <input type="hidden" class="cropped_image"
                                   value="<?php echo $cropped_image ?>"/>
                            <input class="image_id"
                                   type="hidden" name="items[<?php echo $key ?>][image]"
                                   value="<?php echo $item['image'] ?>"/>
                        </div>
                    </div>
                </div>
			<?php endforeach; ?>
        </div>
		<?php
	}


	public function formOptionsArea( $post ) {
		global $sp_gh;
		$option = $sp_gh->getSpinOptions( $post->ID );


		?>
        <div id="gh_welcome_section">
            <div class="flexBox">
                <div class="item">
                    <div class="gh_color item">
						<?php _e( 'Background Color', 'wp-spinner' ) ?>
                        <input type="text" class="gh_color_picker" name="form[bg_color]"
                               value="<?php echo $option['form']['bg_color'] ?>"/>
                    </div>
                    <div class="bg-item">
                        <div><?php _e( 'Visibility', 'wp-spinner' ) ?></div>
                        <input type="number" min="0" max="100"
                               name="form[bg_color_visibility]"
                               value="<?php echo $option['form']['bg_color_visibility'] ?>"/>%
                    </div>
                </div>
                <div class="item item-x2">
                    <div id="form_bg_image">
                        <div><?php _e( 'Background Image', 'wp-spinner' ) ?></div>

                        <input type="text" name="form[bg_image]"
                               value="<?php echo $option['form']['bg_image'] ?>"/>
                        <span class="buttons">
                            <button class="add_image btn-green-gh">
                                <span class="dashicons dashicons-plus"></span>
                            </button>
                            <button class="remove_image btn-red-gh">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </span>
                    </div>
                    <div class="bg-item">
                        <div><?php _e( 'Visibility', 'wp-spinner' ) ?></div>
                        <input type="number" min="0" max="100"
                               name="form[bg_image_visibility]"
                               value="<?php echo $option['form']['bg_image_visibility'] ?>"/>%
                    </div>
                </div>
                <div class="gh_color item">
                    <div><?php _e( 'Play Button Color', 'wp-spinner' ) ?></div>

                    <input type="text" name="form[play_button_color]" class="gh_color_picker"
                           value="<?php echo $option['form']['play_button_color'] ?>"/>
                </div>

            </div>


            <div>
                <div class="item">
                    <label><?php _e( 'Welcome Text', 'wp-spinner' ) ?></label>
					<?php wp_editor( $option['form']['text'], 'welcome_message', array(
						'textarea_name' => 'form[text]',
						'media_buttons' => false,
						'editor_height' => 200,
					) ) ?>
                </div>
            </div>
        </div>
        <div id="gh_respin_clame">

        </div>

		<?php
	}

	public function spinResultArea( $post ) {
		global $sp_gh;
		$option = $sp_gh->getSpinOptions( $post->ID );

		?>
        <div id="spin_messages_wrapper">
            <div class="flexBox">
                <div class="item">
                    <div class="gh_color item">
						<?php _e( 'Background Color', 'wp-spinner' ) ?>
                        <input type="text" class="gh_color_picker" name="result[bg_color]"
                               value="<?php echo $option['result']['bg_color'] ?>"/>
                    </div>
                    <div class="bg-item">
                        <div><?php _e( 'Visibility', 'wp-spinner' ) ?></div>
                        <input type="number" min="0" max="100"
                               name="result[bg_color_visibility]"
                               value="<?php echo $option['result']['bg_color_visibility'] ?>"/>%
                    </div>
                </div>
                <div class="item item-x2">
                    <div id="result_bg_image">
                        <div><?php _e( 'Background Image', 'wp-spinner' ) ?></div>

                        <input type="text" name="result[bg_image]"
                               value="<?php echo $option['result']['bg_image'] ?>"/>
                        <span class="buttons">
                        <button class="add_image btn-green-gh">
                            <span class="dashicons dashicons-plus"></span>
                        </button>
                        <button class="remove_image btn-red-gh">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </span>
                    </div>
                    <div class="bg-item">
                        <div><?php _e( 'Visibility', 'wp-spinner' ) ?></div>
                        <input type="number" min="0" max="100"
                               name="result[bg_image_visibility]"
                               value="<?php echo $option['result']['bg_image_visibility'] ?>"/>%
                    </div>
                </div>
                <div class="gh_color item">
                    <div><?php _e( 'Claim Button Color', 'wp-spinner' ) ?></div>

                    <input type="text" name="result[claim_color]" class="gh_color_picker"
                           value="<?php echo $option['result']['claim_color'] ?>"/>
                </div>

                <div class="gh_color item">
                    <div><?php _e( 'Retry Button Color', 'wp-spinner' ) ?></div>

                    <input type="text" name="result[retry_color]" class="gh_color_picker"
                           value="<?php echo $option['result']['retry_color'] ?>"/>
                </div>


            </div>

            <div>
                <label><?php _e( 'Win Message', 'wp-spinner' ) ?></label>
				<?php wp_editor( $option['result']['win_message'], 'win_message_gh', array(
					'textarea_name' => 'result[win_message]',
					'media_buttons' => false,
					'editor_height' => 200,
				) ) ?>
            </div>

            <div>
                <label><?php _e( 'Lose Message', 'wp-spinner' ) ?></label>
				<?php wp_editor( $option['result']['lose_message'], 'lose_message_gh', array(
					'textarea_name' => 'result[lose_message]',
					'media_buttons' => false,
					'editor_height' => 200,
				) ) ?>
            </div>

            <div>
                <label><?php _e( 'Max Try Message', 'wp-spinner' ) ?></label>
				<?php wp_editor( $option['result']['max_try_message'], 'max_try_message_gh', array(
					'textarea_name' => 'result[max_try_message]',
					'media_buttons' => false,
					'editor_height' => 200,
				) ) ?>
            </div>

            <div>
                <label><?php _e( 'Claimed Message', 'wp-spinner' ) ?></label>
				<?php wp_editor( $option['result']['claimed_error'], 'claimed_error_gh', array(
					'textarea_name' => 'result[claimed_error]',
					'media_buttons' => false,
					'editor_height' => 200,
				) ) ?>
            </div>

        </div>
		<?php

	}


	public function optionsArea( $post ) {
		global $sp_gh, $wc_gh;
		$option = $sp_gh->getSpinOptions( $post->ID );
		?>

        <div class="option_wrapper">
            <label>
                <span><?php _e( 'Template', 'wp-spinner' ) ?></span>
				<?php $t = $option['template'] ?>
                <select name="template">
                    <option value="1" <?php echo $t == 1 ? 'selected=""' : '' ?> >
						<?php _e( 'Template 1', 'wp-spinner' ) ?>
                    </option>
                    <option value="2" <?php echo $t == 2 ? 'selected=""' : '' ?>>
						<?php _e( 'Template 2', 'wp-spinner' ) ?>
                    </option>
                    <option value="3" <?php echo $t == 3 ? 'selected=""' : '' ?>>
						<?php _e( 'Template 3', 'wp-spinner' ) ?>
                    </option>
                    <option disabled>
						<?php _e( 'Template 4', 'wp-spinner' ) ?>
						<?php _e( ' (PRO)', 'wp-spinner' ) ?>
                    </option>
                </select>
            </label>
            <label>
                <span><?php _e( 'Items Count', 'wp-spinner' ) ?></span>
                <input type="number" name="spin_items_count" min="5" max="20"
                       id="spin_items_count"
                       value="<?php echo count( $option['items'] ) ?>"/>
            </label>
            <label>
                <span><?php _e( 'Size', 'wp-spinner' ) ?></span>
                <input type="number" name="size" min="350" value="<?php echo $option['size'] ?>"/>
            </label>


            <label>
                <span><?php _e( 'Font Size', 'wp-spinner' ) ?></span>
                <input type="number" name="font_size" min="5" value="<?php echo $option['font_size'] ?>"/>
            </label>


            <label>
                <span><?php _e( 'Text Position', 'wp-spinner' ) ?></span>
                <input type="number" name="text_position" value="<?php echo $option['text_position'] ?>"/>
            </label>
            <div class="border_option">
                <div class="label">
                    <span><?php _e( 'Border', 'wp-spinner' ) ?></span>
                    <input type="text" name="border_color" value="<?php echo $option['border_color'] ?>"
                           class="border_color gh_color_field">
                </div>
                <input type="number" name="border_size" value="<?php echo $option['border_size'] ?>" max="50" min="0"/>
            </div>

            <label>
                <span><?php _e( 'Image Size', 'wp-spinner' ) ?></span>
                <input type="number" name="img_size" min="10"
                       value="<?php echo $option['img_size'] ?>" <?php echo $t == 4 ? 'readonly' : '' ?>/>
            </label>

            <div style="display: flex">
                <label style="width:50%;margin-right:8px">
                    <span><?php _e( 'Images Position', 'wp-spinner' ) ?></span>
                    <input type="number" name="img_x" value="<?php echo $option['img_x'] ?>"/>
                </label>

                <label style="width: 50%">
                    <span>&nbsp;</span>
                    <input type="number" name="img_y"
                           value="<?php echo $option['img_y'] ?>" <?php echo $t == 4 ? 'readonly' : '' ?>/>
                </label>
            </div>


            <label>
                <span><?php _e( 'Duration (ms)', 'wp-spinner' ) ?></span>
                <input type="number" name="duration" value="<?php echo $option['duration'] ?>"/>
            </label>
            <label>
                <span><?php _e( 'Rotate', 'wp-spinner' ) ?></span>
				<?php $r = $option['rotate']; ?>
                <select name="rotate">
                    <option value="-90" <?php echo $r == - 90 ? 'selected=""' : '' ?> >
                        -90
                    </option>
                    <option value="0" <?php echo $r == 0 ? 'selected=""' : '' ?>>
                        0
                    </option>
                    <option value="90" <?php echo $r == 90 ? 'selected=""' : '' ?>>
                        90
                    </option>
                </select>
            </label>
            <div style="margin: 10px">
                <hr/>
            </div>
            <label>
                <span><?php _e( 'Max Spin Count', 'wp-spinner' ) ?></span>
                <input type="number" name="max_spin_count" value="<?php echo $option['max_spin_count'] ?>"/>
            </label>
            <div style="display: flex">
                <label style="width: 50%;margin-right:8px">
                    <span><?php _e( 'Spins per period', 'wp-spinner' ) ?></span>
                    <input type="number" name="spins_per_period" min="1"
                           value="<?php echo $option['spins_per_period'] ?>"/>
                </label>
                <label style="width: 50%">

                    <span>&nbsp;</span>
                    <select name="spins_per_period_type">
						<?php ?>
                        <option value="day" <?php echo 'day' == $option['spins_per_period_type'] ? 'selected=""' : '' ?>>
							<?php _e( 'Day', 'wp-spinner' ) ?>
                        </option>
                        <option value="week" <?php echo 'week' == $option['spins_per_period_type'] ? 'selected=""' : '' ?>>
							<?php _e( 'Weeks', 'wp-spinner' ) ?>
                        </option>
                        <option value="year" <?php echo 'year' == $option['spins_per_period_type'] ? 'selected=""' : '' ?>>
							<?php _e( 'Years', 'wp-spinner' ) ?>
                        </option>
                    </select>
                </label>
            </div>
            <div style="margin: 10px">
                <hr/>
            </div>
            <div class="bulk_action">
                <h3><?php _e( 'Bulk action', 'wp-spinner' ) ?></h3>

                <div class="bulk_prize_type">
                    <div>
						<?php _e( 'Prize Type', 'wp-spinner' ) ?>
                    </div>
                    <select>
						<?php $wc_disabled = $wc_gh->wcActive() ? '' : 'disabled=""'; ?>
                        <option value=""></option>
                        <option value="product" <?php echo $wc_disabled ?>>
							<?php _e( 'Product', 'wp-spinner' ) ?>
                        </option>
                        <option disabled>
							<?php _e( 'Coupon', 'wp-spinner' ) ?>
							<?php _e( ' (PRO)', 'wp-spinner' ) ?>
                        </option>
                        <option value="custom">
							<?php _e( 'Custom', 'wp-spinner' ) ?>
                        </option>
                        <option value="no_prize">
							<?php _e( 'No Prize', 'wp-spinner' ) ?>
                        </option>
                    </select>
                </div>
                <div class="bulk_show">
                    <div>
						<?php _e( 'Display', 'wp-spinner' ) ?>
                    </div>
                    <select>
                        <option value=""></option>
                        <option value="text">
							<?php _e( 'Text', 'wp-spinner' ) ?>
                        </option>
                        <option value="image">
							<?php _e( 'Image', 'wp-spinner' ) ?>
                        </option>
                        <option value="image_text">
							<?php _e( 'Image And Name', 'wp-spinner' ) ?>
                        </option>
                    </select>
                </div>
            </div>
            <div style="margin: 10px">
                <hr/>
            </div>
            <div class="post_relation">
                <h3><?php _e( 'Spin Relation', 'wp-spinner' ) ?></h3>
                <div>
                    <span><?php _e( 'Show spin in posts', 'wp-spinner' ) ?></span>
					<?php echo $this->getPostsList( 'related_posts[]', $option['related_posts'] ) ?>
                    <input type="hidden" name="old_related_posts"
                           value="<?php echo $option['related_posts'] && is_array( $option['related_posts'] ) ? implode( ',', $option['related_posts'] ) : '' ?>"/>
                </div>
                <label>
                    <span><?php _e( 'Button position', 'wp-spinner' ) ?></span>
                    <select name="open_button[position]">
						<?php $sel = $option['open_button']['position'] ?>
                        <option value="top left" <?php echo 'top left' == $sel ? 'selected=""' : '' ?>>
							<?php _e( 'Top and Left', 'wp-spinner' ) ?>
                        </option>
                        <option value="top right" <?php echo 'top right' == $sel ? 'selected=""' : '' ?>>
							<?php _e( 'Top and Right', 'wp-spinner' ) ?>
                        </option>
                        <option value="bottom left" <?php echo 'bottom left' == $sel ? 'selected=""' : '' ?>>
							<?php _e( 'Bottom and Left', 'wp-spinner' ) ?>
                        </option>
                        <option value="bottom right" <?php echo 'bottom right' == $sel ? 'selected=""' : '' ?>>
							<?php _e( 'Bottom and Right', 'wp-spinner' ) ?>
                        </option>
                    </select>
                </label>
                <label>
                    <span><?php _e( 'Button Text', 'wp-spinner' ) ?></span>
                    <input type="text" name="open_button[text]" value="<?php echo $option['open_button']['text'] ?>">
                </label>
                <label>
                    <span><?php _e( 'Auto open timeout (ms)', 'wp-spinner' ) ?></span>
                    <input type="number" name="open_button[open_timeout]"
                           value="<?php echo $option['open_button']['open_timeout'] ?>">
                </label>
            </div>
        </div>

		<?php
	}

	public function spinEmailArea( $post ) {

		global $sp_gh;
		$option = $sp_gh->getSpinOptions( $post->ID );
		?>
        <div id="gh_email_section">
            <div>
                <h1><?php _e( 'Win Email', 'wp-spinner' ) ?></h1>
                <div>
                    <h2><?php _e( 'Subject', 'wp-spinner' ) ?></h2>
                    <input style="width: 100%" type="text" value="<?php echo $option['email']['win_subject'] ?>"
                           name="email[win_subject]">
                </div>
                <div class="item">
                    <h2><?php _e( 'Message', 'wp-spinner' ) ?></h2>
					<?php wp_editor( $option['email']['win'], 'win_email_gh', array(
						'textarea_name' => 'email[win]',
						'media_buttons' => false,
						'editor_height' => 200,
					) ) ?>
                </div>
            </div>
            <div style="border-top: 2px dashed gray; margin:15px  0 ;"></div>
            <div>
                <h1><?php _e( 'Lose Email', 'wp-spinner' ) ?></h1>
                <div>
                    <h2><?php _e( 'Subject', 'wp-spinner' ) ?></h2>
                    <input style="width: 100%" type="text" value="<?php echo $option['email']['lose_subject'] ?>"
                           name="email[lose_subject]">
                </div>
                <div class="item">
                    <h2><?php _e( 'Message', 'wp-spinner' ) ?></h2>
					<?php wp_editor( $option['email']['lose'], 'lose_email_gh', array(
						'textarea_name' => 'email[lose]',
						'media_buttons' => false,
						'editor_height' => 200,
					) ) ?>
                </div>
            </div>
        </div>
		<?php
	}


	public function saveOptions( $spin_id ) {
		if ( ! isset( $_POST['post_type'] ) || $_POST['post_type'] != 'spin_game_gh' ) {
			return;
		}

		$prefix = 'gh_spin_';

		if ( ! empty( $_POST['items'] ) ) {
			foreach ( $_POST['items'] as $key => $item ) {
				$_POST['items'][ $key ]['text'] = str_replace( ' ', '&nbsp;', $item['text'] );
			}
		}


		update_post_meta( $spin_id, $prefix . 'template', (int) $_POST['template'] );
		update_post_meta( $spin_id, $prefix . 'size', (int) $_POST['size'] );
		update_post_meta( $spin_id, $prefix . 'font_size', (int) $_POST['font_size'] );
		update_post_meta( $spin_id, $prefix . 'text_position', (int) $_POST['text_position'] );
		update_post_meta( $spin_id, $prefix . 'border_color', $_POST['border_color'] );
		update_post_meta( $spin_id, $prefix . 'border_size', (int) $_POST['border_size'] );
		update_post_meta( $spin_id, $prefix . 'img_size', (int) $_POST['img_size'] );
		update_post_meta( $spin_id, $prefix . 'img_x', (int) $_POST['img_x'] );
		update_post_meta( $spin_id, $prefix . 'img_y', (int) $_POST['img_y'] );
		update_post_meta( $spin_id, $prefix . 'duration', (int) $_POST['duration'] );
		update_post_meta( $spin_id, $prefix . 'rotate', (int) $_POST['rotate'] );
		update_post_meta( $spin_id, $prefix . 'max_spin_count', (int) $_POST['max_spin_count'] );
		update_post_meta( $spin_id, $prefix . 'spins_per_period', (int) $_POST['spins_per_period'] );
		update_post_meta( $spin_id, $prefix . 'spins_per_period_type', $_POST['spins_per_period_type'] );

		update_post_meta( $spin_id, $prefix . 'items', $_POST['items'] );
		update_post_meta( $spin_id, $prefix . 'result', $_POST['result'] );
		update_post_meta( $spin_id, $prefix . 'form', $_POST['form'] );
		update_post_meta( $spin_id, $prefix . 'email', $_POST['email'] );


		update_post_meta( $spin_id, $prefix . 'related_posts', isset( $_POST['related_posts'] ) ? $_POST['related_posts'] : array() );
		update_post_meta( $spin_id, $prefix . 'open_button', $_POST['open_button'] );

		update_post_meta( $spin_id, $prefix . 'sounds', $_POST['sounds'] );

		$this->ghSpinPostRelationSave( $spin_id );
	}

	private function ghSpinPostRelationSave( $spin_id ) {
		$posts_list = isset( $_POST['related_posts'] ) ? array_map( function ( $id ) {
			return (int) $id;
		}, $_POST['related_posts'] ) : array();


		$old_posts_list = $_POST['old_related_posts'] ? array_map( function ( $id ) {
			return (int) $id;
		}, explode( ',', $_POST['old_related_posts'] ) ) : array();

		$deleted_posts_list = array_diff( $old_posts_list, $posts_list );

		foreach ( $deleted_posts_list as $p_id ) {
			delete_post_meta( $p_id, 'gh_spin_id' );
		}

		foreach ( $posts_list as $p_id ) {
			update_post_meta( $p_id, 'gh_spin_id', $spin_id );
		}
	}


	private function saveGlobalOptions() {
		if ( ! user_can( get_current_user_id(), 'administrator' ) ) {
			$this->alert( 'error', __( 'Permission denied!', 'wp-spinner' ) );

			return;
		}
		if ( ! wp_verify_nonce( $_POST['_gh'], 'spin_global' ) ) {
			$this->alert( 'error', __( 'Nonce verify error, please try again', 'wp-spinner' ) );

			return;

		}
		$opt = array(
			'show_spin_popup' => isset( $_POST['show_spin_popup'] ) ? (int) $_POST['show_spin_popup'] : 0,
			'popup_spin_id'   => isset( $_POST['popup_spin_id'] ) ? (int) $_POST['popup_spin_id'] : 0,
			'spin_exclude'    => isset( $_POST['spin_exclude_ids'] ) ? $_POST['spin_exclude_ids'] : array(),
			'mailchimp'       => isset( $_POST['mailchimp'] ) ? $_POST['mailchimp'] : array(),
			'zapier'          => isset( $_POST['zapier'] ) ? $_POST['zapier'] : array(),
			'remarkety'       => isset( $_POST['remarkety'] ) ? $_POST['remarkety'] : array(),
			'active_campaign' => isset( $_POST['active_campaign'] ) ? $_POST['active_campaign'] : array(),
		);

		update_option( 'spin_global_option_gh', $opt );
		$this->alert( 'success', __( 'Settings saved', 'wp-spinner' ) );
	}


	public function shortCodeColumns( $defaults ) {
		$new = [];
		foreach ( $defaults as $k => $value ) {
			if ( $value === 'Date' ) {
				$new['gh_spin_posts'] = __( 'Related Posts', 'wp-spinner' );
				$new['gh_short_code'] = __( 'ShortCode', 'wp-spinner' );
			}
			$new[ $k ] = $value;
		}

		return $new;

	}

	public function manageShortCodeColumns( $column_name, $post_id ) {
		if ( $column_name == 'gh_short_code' ) {
			echo '<input style="border:none;background:transparent" onfocus="this.select();" readonly="" value=\'[spin_gh id="' . $post_id . '"]\'/>';
		} elseif ( $column_name == 'gh_spin_posts' ) {
			$related_posts = get_post_meta( $post_id, 'gh_spin_related_posts', true );
			if ( $related_posts ) {
				foreach ( $related_posts as $p_id ) {
					echo '<a href="' . admin_url( "post.php?post={$p_id}&action=edit" ) . '" target="_blank">' . get_the_title( $p_id ) . ' #' . $p_id . '</a> | ';
				}
			}
		}

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

	public function getPostsList( $name = '', $sel_ids = array() ) {

		$post_types = get_post_types( array(
			'public' => true,
		), false );

		unset( $post_types['attachment'] );

		$multiple = strstr( $name, '[]' ) !== false ? ' multiple="multiple"' : '';


		$sel_ids = is_array( $sel_ids ) ? array_map( function ( $id ) {
			return (int) $id;
		}, $sel_ids ) : array();

		$options = '<select class="gh_posts_list select2_gh" name="' . esc_attr( $name ) . '" ' . $multiple . '>';
		foreach ( $post_types as $post_type ) {
			$args  = array(
				'post_type'      => $post_type->name,
				'post_status'    => 'publish',
				'posts_per_page' => - 1,
			);
			$posts = get_posts( $args );
			if ( $posts ) {
				$options .= '<optgroup label="' . esc_attr( $post_type->labels->name ) . '">';
				foreach ( $posts as $post ) {
					$id       = $post->ID;
					$selected = in_array( $id, $sel_ids ) ? ' selected=""' : '';
					$options  .= '<option value="' . $id . '" ' . $selected . '>'
					             . $post->post_title . " #{$id}"
					             . '</option>';
				}
				$options .= '</optgroup>';
			}

		}
		$options .= '</select>';

		return $options;

	}


	public function spinSoundsArea( $post ) {
		global $sp_gh;
		$option = $sp_gh->getSpinOptions( $post->ID );
		?>
        <div id="gh_audio_section">
            <label style="color:grey">
                <input type="checkbox" disabled>
				<?php _e( 'Enable Sounds', 'wp-spinner' ) ?>
				<?php _e( ' (PRO)', 'wp-spinner' ) ?>
            </label>
			<?php
			foreach ( array( 'background', 'spin', 'win', 'lose' ) as $type ) :
				$src = '' !== $option['sounds'][ $type ] ? wp_get_attachment_url( $option['sounds'][ $type ] ) : SPIN_URL . "assets/sounds/null.mp3";
				?>
                <div class="item" data-default="<?php echo SPIN_URL . "assets/sounds/null.mp3" ?>">
                    <input type="hidden" name="sounds[<?php echo $type ?>]"
                           value="<?php echo $option['sounds'][ $type ] ?>">
                    <div class="title">
                        <strong><?php echo ucfirst( $type ) . ' ' . __( 'music', 'wp-spinner' ) ?></strong>
                        <span class="name">( <?php echo esc_html( basename( $src ) ) ?> )</span>
                    </div>
                    <div class="audio_wrapper">
						<?php echo wp_audio_shortcode( array( 'src' => $src ? $src : SPIN_URL . "assets/sounds/null.mp3" ) ) ?>
                        <span class="buttons">
                        <button class="add_audio btn-green-gh">
                            <span class="dashicons dashicons-plus"></span>
                        </button>
                        <button class="default_audio btn-orange-gh">
                            <span class="dashicons dashicons-image-rotate"></span>
                        </button>
                        <button class="remove_audio btn-red-gh">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </span>
                    </div>
                </div>
			<?php endforeach; ?>
        </div>

		<?php
	}


	public function addShortCodeButton( $buttons, $editor_id ) {
		$arr = array(
			'win_email_gh',
			'lose_email_gh',
			'win_message_gh',
			'lose_message_gh',
			'max_try_message_gh',
			'claimed_error_gh'
		);
		if ( in_array( $editor_id, $arr ) ) {
			if ( $key = array_search( 'wp_more', $buttons ) ) {
				unset( $buttons[ $key ] );
			}
			$buttons[] = 'wp_spinner';
		}

		return $buttons;
	}

	public function registerShortCodeButtonScript( $plugin_array ) {

		$plugin_array['wp_spinner'] = SPIN_URL . 'assets/scripts/tinymce.min.js';

		return $plugin_array;
	}

	public function spinIconStyle() {
		?>
        <style type="text/css">
            @font-face {
                font-family: 'icomoon-gh';
                src: url('<?php echo SPIN_URL ?>assets/fonts/icomoon.eot?bo8icg');
                src: url('<?php echo SPIN_URL ?>assets/fonts/icomoon.eot?bo8icg#iefix') format('embedded-opentype'), url('<?php echo SPIN_URL ?>assets/fonts/icomoon.ttf?bo8icg') format('truetype'), url('<?php echo SPIN_URL ?>assets/fonts/icomoon.woff?bo8icg') format('woff'), url('<?php echo SPIN_URL ?>assets/fonts/icomoon.svg?bo8icg#icomoon') format('svg');
                font-weight: normal;
                font-style: normal;
            }

            .mce-i-wp_spinner {
                margin-top: -2px !important;
            }

            .mce-i-wp_spinner:before,
            .dashicons-spin_gh:before {
                font-family: 'icomoon-gh' !important;
                speak: none;
                font-style: normal;
                font-weight: normal;
                font-variant: normal;
                text-transform: none;
                line-height: 1;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
                content: "\e901";
            }
        </style>
		<?php
	}
}

new SpinAdminGh();