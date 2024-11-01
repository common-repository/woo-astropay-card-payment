<?php
/*
Plugin Name: Payment Gateway AstroPay Card for Woocommerce 
Plugin URI:  https://simpleintelligentsystems.com
Description: Easily adds AstroPay Card payment options to the WooCommerce plugin so you can allow customers to checkout via AstroPay card.
Version:     1.0.1
Author:      Simple Intelligent Systems
Author URI:  https://simpleintelligentsystems.com
License:     GPL2 or later
Requires PHP: 5.6 
WC requires at least: 3.0
WC tested up to: 5.8.0
Text Domain: woo-astropay-card-payment
Domain Path: /languages

*/
if ( ! defined( 'ABSPATH' ) ) {
    //Exit if accessed directly
    exit;
}

if ( ! class_exists( 'WC_Astropay_Gateway_Addon' ) ) {

    class WC_Astropay_Gateway_Addon {

	var $version		 = '1.0';
	var $plugin_url;
	var $plugin_path;

	function __construct() {
	    $this->define_constants();
	    $this->include_files();
	    $this->loader_operations();
	    
	    add_filter( 'plugin_action_links', array( &$this, 'add_link_to_settings' ), 10, 2 );
	}

	function define_constants() {
	    define( 'WC_ASTROPAY_ADDON_URL', $this->plugin_url() );
	}

	function include_files() {
	    include_once('woo-astropay-utility-class.php');
	}

	function loader_operations() {
	    add_action( 'plugins_loaded', array( &$this, 'plugins_loaded_handler' ) ); //plugins loaded hook
	}

	function plugins_loaded_handler() {
	    //Runs when plugins_loaded action gets fired
		load_plugin_textdomain( 'woo-astropay-card-payment', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );

	    include_once('woo-astropay-gateway-class.php');
	    add_filter( 'woocommerce_payment_gateways', array( &$this, 'init_astropay_gateway' ) );
	}

	function plugin_url() {
	    if ( $this->plugin_url )
		return $this->plugin_url;
	    return $this->plugin_url = plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) );
	}

	function plugin_path() {
	    if ( $this->plugin_path )
			return $this->plugin_path;
	    return $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
	}


	function add_link_to_settings( $links, $file ) {
	    if ( $file == plugin_basename( __FILE__ ) ) {
			$settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=astropay">Settings</a>';
			array_unshift( $links, $settings_link );
	    }
	    return $links;
	}

	function init_astropay_gateway( $methods ) {
	    array_push( $methods, 'WC_Astropay_Gateway' );
	    return $methods;
	}

    }//End of class
	
}//End of class not exists check

$GLOBALS[ 'WC_Astropay_Gateway_Addon' ] = new WC_Astropay_Gateway_Addon();

