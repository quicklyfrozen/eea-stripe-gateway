<?php

use EventEspresso\Stripe\domain\Domain;

defined('EVENT_ESPRESSO_VERSION') || exit('NO direct script access allowed');



/**
 *    Class  EED_Stripe_Connect_OAuth
 *
 * @package        Event Espresso
 * @subpackage     espresso-stripe-connect
 * @author         Event Espresso
 *
 */
class EED_Stripe_Connect_OAuth_Middleman extends EED_Module
{



    /**
     * @return EED_Module|EED_Stripe_Connect_OAuth_Middleman
     */
    public static function instance()
    {
        return parent::get_instance(__CLASS__);
    }



    /**
     * run - initial module setup
     *
     * @param WP $WP
     * @return void
     */
    public function run($WP)
    {
    }



    /**
     * set_hooks - for hooking into EE Core, other modules, etc
     *
     * @return void
     */
    public static function set_hooks()
    {
        if (EE_Maintenance_Mode::instance()->models_can_query()) {
            // A hook to handle the process after the return from Stripe and get the auth creds.
            add_action('init', array('EED_Stripe_Connect_OAuth_Middleman', 'request_access'), 16);
        }
    }



    /**
     * set_hooks_admin - for hooking into EE Admin Core, other modules, etc
     *
     * @return void
     */
    public static function set_hooks_admin()
    {
        if (EE_Maintenance_Mode::instance()->models_can_query()) {
            add_action(
                'wp_ajax_eea_stripe_request_connection_page',
                array('EED_Stripe_Connect_OAuth_Middleman', 'get_connect_token')
            );
            // Request Stripe Connect initial data.
            add_action(
                'wp_ajax_eeg_request_stripe_connect_data',
                array('EED_Stripe_Connect_OAuth_Middleman', 'get_connection_data')
            );
            // Update the OAuth status.
            add_action(
                'wp_ajax_eeg_stripe_update_connection_status',
                array('EED_Stripe_Connect_OAuth_Middleman', 'update_connection_status')
            );
            // Stripe disconnect.
            add_action(
                'wp_ajax_eeg_request_stripe_disconnect',
                array('EED_Stripe_Connect_OAuth_Middleman', 'disconnect_account')
            );
            // Filter the PM settings form subsections.
            add_filter(
                'FHEE__EE_PMT_Stripe_Onsite__generate_new_settings_form__form_filtering',
                array('EED_Stripe_Connect_OAuth_Middleman', 'add_stripe_connect_button'),
                10,
                3
            );
        }
    }



    /**
     *    Fetch the user’s authorization credentials.
     *    This will handle the user return from the Stripe authentication page.
     * We expect them to return to a page like
     *
     * @codingStandardsIgnoreStart
    /?webhook_action=eeg_stripe_grab_access_token&access_token=123qwe&nonce=qwe123&refresh_token=123qwe&connect_publishable_key=123qwe&stripe_slug=stripe1&stripe_user_id12345&livemode=1
     * @codingStandardsIgnoreEnd
     * @return void
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EE_Error
     */
    public static function request_access()
    {
        // Check if this is the webhook for Stripe Connect
        if (
            ! isset(
                $_GET['webhook_action'],
                $_GET['nonce']
            )
            || $_GET['webhook_action'] !== 'eeg_stripe_grab_access_token'
        ) {
            //ignore it
            return;
        }
        // Check that we have all the required parameters and the nonce is ok.
        if (! isset(
                $_GET['access_token'],
                $_GET[Domain::META_KEY_REFRESH_TOKEN],
                $_GET[Domain::META_KEY_PUBLISHABLE_KEY],
                $_GET['stripe_slug'],
                $_GET[Domain::META_KEY_STRIPE_USER_ID],
                $_GET[Domain::META_KEY_LIVE_MODE],
                $_GET[Domain::META_KEY_CLIENT_ID]
            )
            || ! wp_verify_nonce($_GET['nonce'], 'eeg_stripe_grab_access_token')
        ) {
            //this is an error. Close the window outright
            EED_Stripe_Connect_OAuth_Middleman::close_oauth_window(esc_html__('Nonce fail!', 'event_espresso'));
        }
        // Get pm data.
        $stripe = EEM_Payment_Method::instance()->get_one_by_slug(sanitize_key($_GET['stripe_slug']));
        if (! $stripe instanceof EE_Payment_Method) {
            EED_Stripe_Connect_OAuth_Middleman::close_oauth_window(
                esc_html__(
                    'Could not specify the payment method!',
                    'event_espresso'
                )
            );
        }
        $stripe->update_extra_meta(Domain::META_KEY_SECRET_KEY, sanitize_text_field($_GET['access_token']));
        $stripe->update_extra_meta(Domain::META_KEY_REFRESH_TOKEN, sanitize_text_field($_GET[Domain::META_KEY_REFRESH_TOKEN]));
        $stripe->update_extra_meta(Domain::META_KEY_PUBLISHABLE_KEY, sanitize_text_field($_GET[Domain::META_KEY_PUBLISHABLE_KEY]));
        $stripe->update_extra_meta(Domain::META_KEY_CLIENT_ID, sanitize_text_field($_GET[Domain::META_KEY_CLIENT_ID]));
        $stripe->update_extra_meta(Domain::META_KEY_LIVE_MODE, sanitize_key($_GET[Domain::META_KEY_LIVE_MODE]));
        $stripe->update_extra_meta(Domain::META_KEY_STRIPE_USER_ID, sanitize_text_field($_GET[Domain::META_KEY_STRIPE_USER_ID]));
        $stripe->update_extra_meta(Domain::META_KEY_USING_STRIPE_CONNECT, true);
        // Write JS to pup-up window to close it and refresh the parent.
        EED_Stripe_Connect_OAuth_Middleman::close_oauth_window('');
    }



    /**
     *  Return information needed to request the Stripe Connect page.
     *
     * @return void
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EE_Error
     */
    public static function get_connection_data()
    {
        if (! isset($_POST['submitted_pm'])) {
            echo wp_json_encode(
                array(
                    'stripe_error' => esc_html__(
                        'Missing some required parameters: payment method slug.',
                        'event_espresso'
                    ),
                )
            );
            exit();
        }
        $stripe_slug = sanitize_key($_POST['submitted_pm']);
        $stripe      = EEM_Payment_Method::instance()->get_one_by_slug($stripe_slug);
        //if they changed the debug mode value without saving beforehand, just save that.
        //It simplifies the rest of this process having debug mode correct, and
        //it's likely they won't save after the oauth process is complete either
        if (array_key_exists('debug_mode', $_POST)
            && in_array(
                $_POST['debug_mode'],
                array('0','1'),
                true
            )
            && $stripe->debug_mode() !== (int)$_POST['debug_mode']
        ) {
            $stripe->save(
                array(
                    'PMD_debug_mode' => $_POST['debug_mode']
                )
            );
        }
        if (! $stripe instanceof EE_Payment_Method) {
            $err_msg = __('Could not specify the payment method.', 'event_espresso');
            echo wp_json_encode(array('stripe_error' => $err_msg));
            exit();
        }
        // oAuth return handler.
        $redirect_uri = add_query_arg(
            array(
                'webhook_action' => 'eeg_stripe_grab_access_token',
                'stripe_slug'    => $stripe_slug,
                Domain::META_KEY_LIVE_MODE       => $stripe->debug_mode() ? '0' : '1',
                'nonce'          => wp_create_nonce('eeg_stripe_grab_access_token'),
            ),
            site_url()
        );
        // Request URL should look something like
        // @codingStandardsIgnoreStart
        //https://connect.eventespresso.dev/stripeconnect/forward?return_url=http%253A%252F%252Fsrc.wordpress-develop.dev%252Fwp-admin%252Fadmin.php%253Fpage%253Dwc-settings%2526amp%253Btab%253Dintegration%2526amp%253Bsection%253Dstripeconnectconnect%2526amp%253Bwc_stripeconnect_token_nonce%253D6585f05708&scope=read_write
        // @codingStandardsIgnoreEnd
        $request_url = add_query_arg(
            array(
                'scope'      => 'read_write',
                'return_url' => rawurlencode($redirect_uri),
                'modal' => true
            ),
            EED_Stripe_Connect_OAuth_Middleman::stripe_connect_middleman_base_url($stripe) . 'forward'
        );
        echo wp_json_encode(
            array(
                'stripe_success' => true,
                'request_url'    => $request_url,
            )
        );
        exit();
    }


    /**
     *  Disconnect the current client's account from the EE account.
     *
     * @return void
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EE_Error
     */
    public static function disconnect_account()
    {
        // Check if all the needed parameters are present.
        if (! isset($_POST['submitted_pm'])) {
            echo wp_json_encode(
                array(
                    'stripe_error' => esc_html__(
                        'Missing some required parameters: payment method slug.',
                        'event_espresso'
                    ),
                )
            );
            exit();
        }
        $submitted_pm = sanitize_text_field($_POST['submitted_pm']);
        $stripe       = EEM_Payment_Method::instance()->get_one_by_slug($submitted_pm);
        if (! $stripe instanceof EE_Payment_Method) {
            echo wp_json_encode(
                array(
                    'stripe_error' => esc_html__('Could not specify the payment method.', 'event_espresso'),
                )
            );
            exit();
        }
        $stripe_user_id = $stripe->get_extra_meta(Domain::META_KEY_STRIPE_USER_ID, true);
        if (! $stripe_user_id) {
            echo wp_json_encode(
                array(
                    'stripe_error' => esc_html__('Could not specify the connected user.', 'event_espresso'),
                )
            );
            exit();
        }
        $client_id = $stripe->get_extra_meta(Domain::META_KEY_CLIENT_ID, true);
        if (! $client_id) {
            echo wp_json_encode(
                array(
                    'stripe_error' => esc_html__('Could not specify the connected client ID.', 'event_espresso'),
                )
            );
            exit();
        }
        $post_args = array(
            'method'      => 'POST',
            'timeout'     => 60,
            'redirection' => 5,
            'blocking'    => true,
            'body'        => array(
                Domain::META_KEY_STRIPE_USER_ID => $stripe_user_id,
                Domain::META_KEY_CLIENT_ID      => $client_id,
            ),
        );
        if (defined('LOCAL_MIDDLEMAN_SERVER')) {
            $post_args['sslverify'] = false;
        }
        $post_url = EED_Stripe_Connect_OAuth_Middleman::stripe_connect_middleman_base_url($stripe) . 'deauthorize';
        //        POST https://connect.eventespresso.dev/stripeconnect/deauthorize
        // * with body Domain::META_KEY_STRIPE_USER_ID=123qwe
        // Request the token.
        $response = wp_remote_post($post_url, $post_args);
        if (is_wp_error($response)) {
            echo wp_json_encode(
                array(
                    'stripe_error' => $response->get_error_message(),
                )
            );
            exit();
        }
        $response_body = (isset($response['body']) && $response['body']) ? json_decode($response['body']) : false;
        //for any error (besides already being disconnected), give an error response
        if ($response_body === false
            || (
                isset($response_body->error)
                && strpos($response_body->error_description, 'This application is not connected') === false
            )
        ) {
            if (isset($response_body->error_description)) {
                $err_msg = $response_body->error_description;
            } else {
                $err_msg = esc_html__('Unknown response received!', 'event_espresso');
            }
            echo wp_json_encode(
                array(
                    'stripe_error' => $err_msg,
                )
            );
            exit();
        }
        $stripe->delete_extra_meta(Domain::META_KEY_SECRET_KEY);
        $stripe->delete_extra_meta(Domain::META_KEY_REFRESH_TOKEN);
        $stripe->delete_extra_meta(Domain::META_KEY_PUBLISHABLE_KEY);
        $stripe->delete_extra_meta(Domain::META_KEY_STRIPE_USER_ID);
        $stripe->delete_extra_meta(Domain::META_KEY_LIVE_MODE);
        $stripe->update_extra_meta(Domain::META_KEY_USING_STRIPE_CONNECT, false);
        echo wp_json_encode(
            array(
                'stripe_success' => true,
            )
        );
        exit();
    }



    /**
     * Gets the base URL to all the Stripe Connect middleman services for Event Espresso.
     * If LOCAL_MIDDLEMAN_SERVER is defined, tries to send requests to connect.eventespresso.dev
     * which can be a local instance of EE connect.
     * @param EE_Payment_Method $stripe
     * @return string
     */
    public static function stripe_connect_middleman_base_url(EE_Payment_Method $payment_method)
    {
        $middleman_server_tld = defined('LOCAL_MIDDLEMAN_SERVER') ? 'dev' : 'com';
        $stripe_account_indicator = defined('EE_STRIPE_CONNECT_ACCOUNT_INDICATOR') ? EE_STRIPE_CONNECT_ACCOUNT_INDICATOR : 'ee';
        $testing_postfix = $payment_method->debug_mode() ? '_test' : '';
        $path = 'stripe_' . $stripe_account_indicator . $testing_postfix;
        return 'https://connect.eventespresso.' . $middleman_server_tld . '/' . $path . '/';
    }



    /**
     *  Check the Stripe Connect status and update the interface.
     *
     * @return void
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EE_Error
     */
    public static function update_connection_status()
    {
        $submitted_pm = sanitize_key($_POST['submitted_pm']);
        $stripe       = EEM_Payment_Method::instance()->get_one_by_slug($submitted_pm);
        $access_token = $stripe->get_extra_meta(Domain::META_KEY_SECRET_KEY, true);
        $using_connect = $stripe->get_extra_meta(Domain::META_KEY_USING_STRIPE_CONNECT, true);
        $connected    = true;
        if (empty($access_token) || ! $using_connect) {
            $connected = false;
        }
        echo wp_json_encode(
            array(
                'connected' => $connected,
            )
        );
        exit();
    }



    /**
     *    Log an error and close the oAuth window with JS.
     *
     * @param string $msg
     * @return void
     */
    public static function close_oauth_window($msg = null)
    {
        $js_out = '
			<script type="text/javascript">';
        if (! empty($msg)) {
            $js_out .= '
                if ( window.opener ) {
					try {
						window.opener.console.log("' . $msg . '");
					} catch (e) {
						console.log("' . $msg . '");
					}
				}
			';
        }
        $js_out .= 'window.opener = self;
				window.close();
			</script>';
        echo $js_out;
        die();
    }



    /**
     *  Add the Stripe Connect button to the PM settings page.
     *
     * @param EE_Payment_Method_Form $stripe_form
     * @param EE_PMT_Stripe_Onsite   $payment_method
     * @param EE_Payment_Method      $pm_instance
     * @return EE_Payment_Method_Form
     * @throws \EE_Error
     */
    public static function add_stripe_connect_button($stripe_form, $payment_method, $pm_instance)
    {
        // If there is an established connection we should check the debug mode on the PM and the connection.
        $using_stripe_connect = $pm_instance->get_extra_meta(Domain::META_KEY_USING_STRIPE_CONNECT, true, false);
        $connection_live_mode = $pm_instance->get_extra_meta(Domain::META_KEY_LIVE_MODE, true);
        $pm_debug_mode        = $pm_instance->debug_mode();
        if ($using_stripe_connect) {
            if ($connection_live_mode && $pm_debug_mode) {
                $stripe_form->add_validation_error(
                    sprintf(
                        esc_html__(
                        // @codingStandardsIgnoreStart
                            '%1$sStripe Payment Method%2$s is in debug mode but the authentication with %1$sStripe Connect%2$s is in Live mode. Payments will not be processed correctly! If you wish to test this payment method, please reset the connection and use sandbox credentials to authenticate with Stripe Connect.',
                            // @codingStandardsIgnoreEnd
                            'event_espresso'
                        ),
                        '<strong>',
                        '</strong>'
                    ),
                    'ee4_stripe_live_connection_but_pm_debug_mode'
                );
            } elseif (! $connection_live_mode && ! $pm_debug_mode) {
                $stripe_form->add_validation_error(
                    sprintf(
                        esc_html__(
                        // @codingStandardsIgnoreStart
                            '%1$sStripe Payment Method%2$s is in live mode but the authentication with %1$sStripe Connect%2$s is in sandbox mode. Payments will not be processed correctly! If you wish to process real payments with this payment method, please reset the connection and use live credentials to authenticate with Stripe Connect.',
                            // @codingStandardsIgnoreEnd
                            'event_espresso'
                        ),
                        '<strong>',
                        '</strong>'
                    ),
                    'ee4_stripe_sandbox_connection_but_pm_not_in_debug_mode'
                );
            }
        }
        $oauth_template = new EE_Stripe_OAuth_Form($payment_method, $pm_instance);
        // Add Stripe Connect button before the Secret key field.
        $stripe_form->add_subsections(
            array(
                'stripe_oauth_connect' => new EE_Form_Section_HTML($oauth_template->get_html_and_js()),
            ),
            'validate_zip'
        );
        // We disable Stripe Key fields for new users.
        // Use a filter so that we may enforce showing the fields.
        if (apply_filters(
            'FHEE__EE_PMT_Stripe_Onsite__generate_new_settings_form__hide_key_fields',
            ! $pm_instance->get_extra_meta(Domain::META_KEY_SECRET_KEY, true, false)
            // exclude fields if connected to remove any confusion
            || $using_stripe_connect
        )
        ) {
            $stripe_form->exclude(
                array(
                    Domain::META_KEY_SECRET_KEY,
                    Domain::META_KEY_PUBLISHABLE_KEY,
                )
            );
        }
        return $stripe_form;
    }
}
