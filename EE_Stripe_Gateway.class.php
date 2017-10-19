<?php

use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;

defined('EVENT_ESPRESSO_VERSION') || exit();
// Define the plugin directory path and URL.
define('EE_STRIPE_BASENAME', plugin_basename(EE_STRIPE_PLUGIN_FILE));
define('EE_STRIPE_PATH', plugin_dir_path(__FILE__));
define('EE_STRIPE_URL', plugin_dir_url(__FILE__));



/**
 * Class  EE_Stripe_Gateway
 *
 * @package            Event Espresso
 * @subpackage         espresso-stripe-gateway
 * @author             Event Espresso
 * @version            $VID:$
 */
class EE_Stripe_Gateway extends EE_Addon
{



    /**
     * @throws EE_Error
     */
    public static function register_addon()
    {
        // Register addon via Plugin API.
        EE_Register_Addon::register(
            'Stripe_Gateway',
            array(
                'version'              => EE_STRIPE_VERSION,
                'min_core_version'     => '4.9.26.rc.000',
                'main_file_path'       => EE_STRIPE_PLUGIN_FILE,
                'admin_callback'       => 'additional_stripe_admin_hooks',
                // register autoloaders
                'autoloader_paths'     => array(
                    'EE_PMT_Base'          => EE_LIBRARIES . 'payment_methods' . DS . 'EE_PMT_Base.lib.php',
                    'EE_PMT_Stripe_Onsite' => EE_STRIPE_PATH . 'payment_methods' . DS . 'Stripe_Onsite'
                                              . DS . 'EE_PMT_Stripe_Onsite.pm.php',
                    'EE_Stripe_OAuth_Form' => EE_STRIPE_PATH . 'forms' . DS . 'EE_Stripe_OAuth_Form.form.php',
                ),
                'namespace'        => array(
                    'FQNS' => 'EventEspresso\Stripe',
                    'DIR'  => __DIR__,
                ),
                // if plugin update engine is being used for auto-updates. not needed if PUE is not being used.
                'pue_options'          => array(
                    'pue_plugin_slug' => 'eea-stripe-gateway',
                    'plugin_basename' => EE_STRIPE_BASENAME,
                    'checkPeriod'     => '24',
                    'use_wp_update'   => false,
                ),
                'payment_method_paths' => array(
                    EE_STRIPE_PATH . 'payment_methods' . DS . 'Stripe_Onsite',
                ),
                'module_paths'         => array(
                    EE_STRIPE_PATH . 'EED_Stripe_Connect_OAuth_Middleman.module.php',
                ),
            )
        );
    }



    /**
     * a safe space for addons to add additional logic like setting hooks
     * that will run immediately after addon registration
     * making this a great place for code that needs to be "omnipresent"
     */
    public function after_registration()
    {
        // Log Stripe JS errors.
        add_action('wp_ajax_eea_stripe_log_error', array('EE_PMT_Stripe_Onsite', 'log_stripe_error'));
        add_action('wp_ajax_nopriv_eea_stripe_log_error', array('EE_PMT_Stripe_Onsite', 'log_stripe_error'));
    }



    /**
     * Setup default data for the addon.
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     * @throws EE_Error
     */
    public function initialize_default_data()
    {
        parent::initialize_default_data();
        $converter = new EventEspresso\Stripe\Domain\ConnectSettingsConverter();
        $converter->checkForOldStripeConnectSettings();
    }



    /**
     *    Additional admin hooks.
     *
     * @access    public
     * @return    void
     */
    public static function additional_stripe_admin_hooks()
    {
        // is admin and not in M-Mode ?
        if (is_admin() && ! EE_Maintenance_Mode::instance()->level()) {
            add_filter('plugin_action_links', array('EE_Stripe_Gateway', 'plugin_actions'), 10, 2);
        }
    }


    /**
     * Add a settings link to the Plugins page.
     * Add a settings link to the Plugins page, so people can go straight from the plugin page to the settings page.
     *
     * @param $links
     * @param $file
     * @return array
     */
    public static function plugin_actions($links, $file)
    {
        if ($file === EE_STRIPE_BASENAME) {
            // Before other links
            array_unshift(
                $links,
                '<a href="admin.php?page=espresso_payment_settings">' . __('Settings') . '</a>'
            );
        }
        return $links;
    }
}
// End of file EE_Stripe_Gateway.class.php
// Location: wp-content/plugins/espresso-stripe-gateway/EE_Stripe_Gateway.class.php
