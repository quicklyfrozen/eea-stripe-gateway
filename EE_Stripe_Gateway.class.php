<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' )) { exit('NO direct script access allowed'); }
// Define the plugin directory path and URL.
define( 'EE_STRIPE_BASENAME', plugin_basename( EE_STRIPE_PLUGIN_FILE ) );
define( 'EE_STRIPE_PATH', plugin_dir_path( __FILE__ ) );
define( 'EE_STRIPE_URL', plugin_dir_url( __FILE__ ) );
/**
 *
 * Class  EE_Stripe_Gateway
 *
 * @package			Event Espresso
 * @subpackage		espresso-stripe-gateway
 * @author			Event Espresso
 * @ version		 	$VID:$
 */
class EE_Stripe_Gateway extends EE_Addon {

	/**
	 *    class constructor
	 * @return EE_Stripe_Gateway
	 */
	function __construct() {
	}



	public static function register_addon() {
		// Register addon via Plugin API.
		EE_Register_Addon::register(
			'Stripe_Gateway',
			array(
				'version' => EE_STRIPE_VERSION,
				'min_core_version' => '4.6.0.dev.000',
				'main_file_path' => EE_STRIPE_PLUGIN_FILE,
				'admin_callback' => 'additional_stripe_admin_hooks',
				// if plugin update engine is being used for auto-updates. not needed if PUE is not being used.
				'pue_options' => array(
					'pue_plugin_slug' => 'eea-stripe-gateway',
					'plugin_basename' => EE_STRIPE_BASENAME,
					'checkPeriod' => '24',
					'use_wp_update' => FALSE,
				),
				'payment_method_paths' => array(
					EE_STRIPE_PATH . 'payment_methods' . DS . 'Stripe_Onsite'
				),
		));
	}


	/**
	 * 	Setup default data for the addon.
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function initialize_default_data() {
		parent::initialize_default_data();

		// Update the currencies supported by this gateway (if changed).
		$stripe = EEM_Payment_method::instance()->get_one_of_type( 'Stripe_Onsite' );
		// Update If the payment method already exists.
		if ( $stripe ) {
			$currencies = $stripe->get_all_usable_currencies();
			$all_related = $stripe->get_many_related( 'Currency' );

			if ( ($currencies != $all_related) ) {
				$stripe->_remove_relations( 'Currency' );
				foreach ( $currencies as $currency_obj ) {
					$stripe->_add_relation_to( $currency_obj, 'Currency' );
				}
			}
		}
	}


	/**
	 * 	Additional admin hooks.
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function additional_stripe_admin_hooks() {
		// is admin and not in M-Mode ?
		if ( is_admin() && ! EE_Maintenance_Mode::instance()->level() ) {
			add_filter( 'plugin_action_links', array( 'EE_Stripe_Gateway', 'plugin_actions' ), 10, 2 );
		}
	}

	/**
	 * Add a settings link to the Plugins page.
	 *
	 * Add a settings link to the Plugins page, so people can go straight from the plugin page to the settings page.
	 * @param $links
	 * @param $file
	 * @return array
	 */
	public static function plugin_actions( $links, $file ) {
		if ( $file == EE_STRIPE_BASENAME ) {
			// Before other links
			array_unshift( $links, '<a href="admin.php?page=espresso_payment_settings">' . __('Settings') . '</a>' );
		}
		return $links;
	}
}
// End of file EE_Stripe_Gateway.class.php
// Location: wp-content/plugins/espresso-stripe-gateway/EE_Stripe_Gateway.class.php
