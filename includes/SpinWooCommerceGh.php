<?php
defined( "ABSPATH" ) or exit;

class SpinWooCommerceGh {

	public function __construct() {
		add_action( 'wp_ajax_get_product_image', array( $this, 'ajaxGetProductImage' ) );
	}

	public function wcActive() {
		if ( class_exists( 'woocommerce' ) ) {
			return true;
		}

		return false;
	}

	public function getProductList( $sel_id = 0 ) {
		return $this->getItemsList( $sel_id, 'product' );
	}


	private static function getItemsList( $sel_id = 0, $type = 'product' ) {
		static $products;
		if ( ! $products || ! isset( $products[ $type ] ) ) {
			$args              = array(
				'post_type'      => $type,//shop_coupon
				'post_status'    => 'publish',
				'posts_per_page' => - 1,
			);
			$products[ $type ] = get_posts( $args );
		}

		$options = '<select class="wc_' . esc_attr( $type ) . '_list ' . esc_attr( $type ) . '">';
		$options .= '<option value=""></option>';
		if ( $products ) {
			foreach ( $products[ $type ] as $product ) {
				$id       = $product->ID;
				$selected = $sel_id == $id ? ' selected=""' : '';
				$options  .= '<option value="' . $id . '" ' . $selected . '>'
				             . $product->post_title . " #{$id}"
				             . '</option>';
			}
		}
		$options .= '</select>';

		return $options;
	}


	public function generateOrder( $product_id, $email, $name ) {
		if ( ! $this->wcActive() ) {
			return;
		}
		if ( strpos( $name, ' ' ) ) {
			$name   = explode( ' ', $name );
			$f_name = $name[0];
			$l_name = $name[1];
		} else {
			$f_name = $name;
			$l_name = '';
		}



		$args = array();

		if ( $user = get_user_by( 'email', $email ) ) {
			$args['customer_id'] = $user->get( 'ID' );
		}

		// Now we create the order
		$order = wc_create_order( $args );


		$address = array(
			'first_name' => $f_name,
			'last_name'  => $l_name,
			'email'      => $email,
			'company'    => null,
			'phone'      => null,
			'address_1'  => null,
			'address_2'  => null,
			'city'       => null,
			'state'      => null,
			'postcode'   => null,
			'country'    => null
		);

		// Set addresses
		$order->set_address( $address, 'billing' );
		$order->set_address( $address, 'shipping' );


		$pf = new WC_Product_Factory();

		$product = $pf->get_product( $product_id );

		// The add_product() function below is located in /plugins/woocommerce/includes/abstracts/abstract_wc_order.php
		$order->add_product( $product ); // Use the product IDs to add

		// Set payment gateway
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		$order->set_payment_method( $payment_gateways['bacs'] );

		// Calculate totals
		$order->calculate_totals();

		$order->update_status( 'processing', 'Order created by Spin Game - ', true );

	}


	public function ajaxGetProductImage() {
		if ( $_REQUEST['template'] == 4 ) {
			$size = 'spin_gh';
		} else {
			$size = 'thumbnail';
		}
		$product_id = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : exit( 'error' );
		exit( get_the_post_thumbnail_url( $product_id, $size ) );
	}

}

$wc_gh = new SpinWooCommerceGh();