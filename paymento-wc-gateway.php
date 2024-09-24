<?php
/*
   Plugin name: Paymento gateway for Woocommerce
   Plugin URI: #
   Description: Paymento electronic payment gateway for Woocommerce
   Version: 1.0.0
   Author: Paymento.io
   Author URI: #
   Text Domain: paymento
   Domain Path: /languages
*/

/**
 * Main PAYMENTO Gateway Class.
 *
 * @class PAYMENTO_WC_Main
 * @version	1.0.0
 */
class PAYMENTO_WC_Main {

	/**
	 * The single instance of the class.
	 *
	 * @var PAYMENTO_WC_Main
	 */
	protected static $_instance = null;

	/**
	 * Name of plugin in wordpress admin area
	 * @var
	 */
	private $name;

	/**
	 * Description of plugin in wordpress admin area
	 * @var
	 */
	private $description;

	/**
	 * Author of plugin in wordpress admin area
	 * @var
	 */
	private $author;



	/**
	 * PAYMENTO_WC_Main constructor.
	 */
	public function __construct() {
		$this->define_constants();
		//$this->includes();
		$this->init_hooks();

		$this->name         = __('PAYMENTO gateway for Woocommerce', 'paymento');
		$this->description  = __('Paymento electronic payment gateway for Woocommerce', 'paymento');
		$this->author       = __('Paymento Team', 'paymento');
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'localization' ) );
		add_action( 'plugins_loaded', array( $this, 'includes' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
	}

	/**
	 * Make plugin translatable
	 */
	public function localization() {
		$plugin_rel_path = plugin_basename(PAYMENTOGW_PATH).'/languages';
		load_plugin_textdomain('paymento', false, $plugin_rel_path);
	}

	/**
	 *
	 * Add plugin gateway class to woocommerce gateways
	 *
	 * @param $gateways
	 *
	 * @return array
	 */
	public function add_gateway( $gateways ) {
		$gateways[] = 'WC_PAYMENTO_Gateway';
		return $gateways;
	}

	/**
	 * Main Gateway Instance.
	 *
	 * Ensures only one instance of Paymento Gateway is loaded or can be loaded.
	 *
	 * @static
	 * @return PAYMENTO_WC_Main Gateway - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	/**
	 * Define Paymento Constants.
	 */
	private function define_constants() {
		$this->define( 'PAYMENTOGW_URL', plugin_dir_url(__FILE__) );
		$this->define( 'PAYMENTOGW_PATH', plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Gateway Class.
		 */
		include_once( PAYMENTOGW_PATH . 'includes/class-gateway.php' );

	}

}
function get_instance_paymento_gateway() {
	return PAYMENTO_WC_Main::instance();
}
get_instance_paymento_gateway();