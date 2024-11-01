<?php
/*
* Plugin Name: WpSpinner
* Plugin URI: https://www.wp-spinner.com
* Description: WP Spinner is a spin and win game that will help users to grow their businesses by a funny way of email collection.
* Version: 1.0.01
* Author: gHost Services
* Author URI: https://www.ghost-services.com
*
* Text Domain: wp-spinner
* Domain Path: /languages/
*/
defined( "ABSPATH" ) or exit;
! class_exists( 'SpinPostTypeGh' ) or wp_die( 'WpSpinner already activated' );

define( "SPIN_DIR", plugin_dir_path( __FILE__ ) );
define( "SPIN_URL", plugin_dir_url( __FILE__ ) );
define( "SPIN_DIR_NAME", dirname( plugin_basename( __FILE__ ) ) );
define( "SPIN_ROOT_FILE", __FILE__ );


include SPIN_DIR . 'includes/SpinPostTypeGh.php';

if ( isset( $_GET['gh_spin_style'] ) ) {
	include SPIN_DIR . 'includes/style.php';
	exit;
}

include SPIN_DIR . 'includes/SpinDbGh.php';
include SPIN_DIR . 'includes/SpinAdminGh.php';
include SPIN_DIR . 'includes/SpinWooCommerceGh.php';
include SPIN_DIR . 'includes/SpinFrontGh.php';
include SPIN_DIR . 'includes/SpinMembersGh.php';
include SPIN_DIR . 'includes/DuplicateSpinGh.php';
