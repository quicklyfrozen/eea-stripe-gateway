<?php if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit('NO direct script access allowed');
}
use EEA_Stripe\Stripe;
use EEA_Stripe\Stripe_Charge;

/**
 *    Class  EED_Stripe_Connect_OAuth
 *
 * @package        Event Espresso
 * @subpackage     espresso-stripe-connect
 * @author         Event Espresso
 * @version        $VID:$
 */
class EED_Stripe_Connect_OAuth_Middleman extends EED_Module
{

    /**
     * @return EED_Stripe_Connect_OAuth
     */
    public static function instance()
    {
        return parent::get_instance(__CLASS__);
    }



    /**
     *    run - initial module setup
     *
     * @access public
     * @return void
     */
    public function run($WP)
    {
    }



    /**
     *    set_hooks - for hooking into EE Core, other modules, etc
     *
     * @access public
     * @return void
     */
    public static function set_hooks()
    {
        if (EE_Maintenance_Mode::instance()->models_can_query()) {
            // A hook to handle the process after the return from Stripe and get the auth creds.
            add_action('init', array('EED_Stripe_Connect_OAuth_Middleman', 'request_access'), 16);
            // A web-hook listener.
            add_action('init', array('EED_Stripe_Connect_OAuth_Middleman', 'webhook_listener'));
        }
    }



    /**
     *    set_hooks_admin - for hooking into EE Admin Core, other modules, etc
     *
     * @access public
     * @return void
     */
    public static function set_hooks_admin()
    {
        if (EE_Maintenance_Mode::instance()->models_can_query()) {
            add_action('wp_ajax_eea_stripe_request_connection_page',
                array('EED_Stripe_Connect_OAuth_Middleman', 'get_connect_token'));
            // Request Stripe Connect initial data.
            add_action('wp_ajax_eeg_request_stripe_connect_data',
                array('EED_Stripe_Connect_OAuth_Middleman', 'get_connection_data'));
            // Update the OAuth status.
            add_action('wp_ajax_eeg_stripe_update_connection_status',
                array('EED_Stripe_Connect_OAuth_Middleman', 'update_connection_status'));
            // Stripe disconnect.
            add_action('wp_ajax_eeg_request_stripe_disconnect', array('EED_Stripe_Connect_OAuth', 'disconnect_account'));
            // Filter the PM settings form subsections.
            add_filter('FHEE__EE_PMT_Stripe_Onsite__generate_new_settings_form__form_filtering',
                array('EED_Stripe_Connect_OAuth_Middleman', 'add_stripe_connect_button'), 10, 3);
        }
    }



    /**
     *    Fetch the user’s authorization credentials.
     *    This will handle the user return from the Stripe authentication page.
     * We expect them to return to a page like
     * @codingStandardsIgnoreStart
        /?webhook_action=eeg_stripe_grab_access_token&access_token=123qwe&nonce=qwe123&refresh_token=123qwe&connect_publishable_key=123qwe&stripe_slug=stripe1&stripe_user_id12345&livemode=1
     * @codingStandardsIgnoreEnd
     *
     * @return void
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EE_Error
     */
    public static function request_access()
    {
        // Check if this is the webhook we expect and if all the needed parameters are present.
        if (
            ! isset(
                $_GET['webhook_action'],
                $_GET['access_token'],
                $_GET['refresh_token'],
                $_GET['stripe_publishable_key'],
                $_GET['nonce'],
                $_GET['stripe_slug'],
                $_GET['stripe_user_id'],
                $_GET['livemode'],
                $_GET['client_id']
            )
            || $_GET['webhook_action'] !== 'eeg_stripe_grab_access_token'
        ) {
            return;
        }
        // Check the nonce.
        if ( ! wp_verify_nonce($_GET['nonce'], 'eeg_stripe_grab_access_token')) {
            EED_Stripe_Connect_OAuth::close_oauth_window(esc_html__('Nonce fail!', 'event_espresso'));
        }
        // Get pm data.
        $stripe = EEM_Payment_Method::instance()->get_one_by_slug(sanitize_key($_GET['stripe_slug']));
        if (! $stripe instanceof EE_Payment_Method) {
            EED_Stripe_Connect_OAuth::close_oauth_window(esc_html__('Could not specify the payment method!',
                'event_espresso'));
        }
        $stripe->update_extra_meta('stripe_secret_key', sanitize_text_field($_GET['access_token']));
        $stripe->update_extra_meta('refresh_token', sanitize_text_field($_GET['refresh_token']));
        $stripe->update_extra_meta('publishable_key',sanitize_text_field($_GET['stripe_publishable_key']));
        $stripe->update_extra_meta('client_id', sanitize_text_field($_GET['client_id']));
        $stripe->update_extra_meta('livemode',sanitize_key($_GET['livemode']));
        $stripe->update_extra_meta('stripe_user_id', sanitize_text_field($_GET['stripe_user_id']));
        $stripe->update_extra_meta('using_stripe_connect', true);
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
                    )
                )
            );
            exit();
        }
        $stripe_slug = sanitize_key($_POST['submitted_pm']);
        $stripe = EEM_Payment_Method::instance()->get_one_by_slug($stripe_slug);
        if (! $stripe instanceof EE_Payment_Method) {
            $err_msg = __('Could not specify the payment method.', 'event_espresso');
            echo wp_json_encode(array('stripe_error' => $err_msg));
            exit();
        }
        // oAuth return handler.
        $redirect_uri = add_query_arg(array(
            'webhook_action' => 'eeg_stripe_grab_access_token',
            'stripe_slug' => $stripe_slug,
            'livemode' => ! ($stripe->debug_mode() || (isset($_POST['debug_mode']) && $_POST['debug_mode'])) ? 1 : 0,
            'nonce' => wp_create_nonce('eeg_stripe_grab_access_token')

        ), site_url());
        $middleman_server_tld = defined('LOCAL_MIDDLEMAN_SERVER') ? 'dev' : 'com';
        // Request URL should look something like
        // @codingStandardsIgnoreStart
        //https://connect.eventespresso.dev/stripeconnect/forward?return_url=http%253A%252F%252Fsrc.wordpress-develop.dev%252Fwp-admin%252Fadmin.php%253Fpage%253Dwc-settings%2526amp%253Btab%253Dintegration%2526amp%253Bsection%253Dstripeconnectconnect%2526amp%253Bwc_stripeconnect_token_nonce%253D6585f05708&scope=read_write
            // @codingStandardsIgnoreEnd
        $request_url = add_query_arg(array(
            'scope'          => 'read_write',
            'return_url'   => rawurlencode($redirect_uri),
        ), 'https://connect.eventespresso.' . $middleman_server_tld . '/stripeconnect/forward');
        echo wp_json_encode(array(
            'stripe_success' => true,
            'request_url'    => $request_url,
        ));
        exit();
    }


    /**
     *  Disconnect the current client's account from the EE account.
     * @todo oauth middleman needs deauthorize endpoint
     * @return void
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EE_Error
     */
    public static function disconnect_account()
    {
        $err_msg = '';
        // Check if all the needed parameters are present.
        if (isset($_POST['submitted_pm'])) {
            $submitted_pm = sanitize_text_field($_POST['submitted_pm']);
            $stripe = EEM_Payment_method::instance()->get_one_by_slug($submitted_pm);
            if (! $stripe instanceof EE_Payment_Method) {
                $err_msg = __('Could not specify the payment method.', 'event_espresso');
                echo wp_json_encode(array('stripe_error' => $err_msg));
                exit();
            }
            $stripe_user_id = $stripe->get_extra_meta('stripe_user_id', true);
            if (! $stripe_user_id) {
                $err_msg = __('Could not specify the connected user.', 'event_espresso');
                echo wp_json_encode(array('stripe_error' => $err_msg));
                exit();
            }

            // Those are the EE Stripe Client credentials.
            $client_data = EED_Stripe_Connect_OAuth::client_credentials($stripe, $_POST);
            $disconnect_args = array(
                'client_id'      => $client_data['id'],
                'stripe_user_id' => $stripe_user_id,
            );
            $post_args = array(
                'method'      => 'POST',
                'timeout'     => 60,
                'redirection' => 5,
                'blocking'    => true,
                'headers'     => array(
                    'Authorization' => ' Bearer ' . $client_data['secret'],
                ),
                'body'        => $disconnect_args,
            );
            $post_url = 'https://connect.stripe.com/oauth/deauthorize';
            // Request the token.
            $response = wp_remote_post($post_url, $post_args);
            $err_msg = '';
            $response_body = (isset($response['body']) && $response['body']) ? json_decode($response['body']) : false;
            if (! is_wp_error($response) && $response_body
                && (! isset($response_body->error)
                    || strpos($response_body->error_description, 'This application is not connected') !== false)
            ) {
                $stripe->delete_extra_meta('stripe_secret_key');
                $stripe->delete_extra_meta('refresh_token');
                $stripe->delete_extra_meta('publishable_key');
                $stripe->delete_extra_meta('stripe_user_id');
                $stripe->delete_extra_meta('livemode');
                $stripe->update_extra_meta('using_stripe_connect', false);
                // Switch to Main site and delete the blog connection meta entry.
                if (is_multisite()) {
                    $blog_list = array();
                    // Main site.
                    switch_to_blog(get_main_network_id());
                    $blog_list = EEM_Extra_Meta::instance()
                                                ->get_all(array(
                                                    array(
                                                        'EXM_key'   => EED_Stripe_Connect_OAuth::STRIPE_USER_ID_META_KEY,
                                                        'EXM_value' => $stripe_user_id,
                                                        'EXM_type'  => 'Blog'
                                                    ),
                                                    'limit' => 1
                                                ));
                    $the_blog = reset($blog_list);
                    if ($the_blog instanceof EE_Extra_Meta) {
                        $the_blog->delete();
                    }
                    // Main site return.
                    restore_current_blog();
                }
                echo wp_json_encode(array(
                    'stripe_success' => true
                ));
                exit();
            } elseif (isset($response_body->error_description)) {
                $err_msg = $response_body->error_description;
            } else {
                $err_msg = esc_html__('Unknown response received!', 'event_espresso');
            }
        } else {
            $err_msg = __('Missing some required parameters: payment method slug.', 'event_espresso');
        }
        // If we got here then something went wrong.
        echo wp_json_encode(array('stripe_error' => $err_msg));
        exit();
    }



    /**
     *  Stripe Webhook listener.
     *  Right now this is set to catch the connected account "deauthorization" events.
     *  Webhook endpoint should look like:
     *  "https://[your.site]?webhook_action=eeg_stripe_webhook_event".
     * @todo oauth middleman needs to accept these requests too
     * @return void
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EE_Error
     */
    public static function webhook_listener()
    {
        // Is this a webhook we expect?
        if (! isset($_REQUEST['webhook_action']) || $_REQUEST['webhook_action'] !== 'eeg_stripe_webhook_event') {
            // This is not our call so we just return.
            return;
        }
        // Retrieve the request body contents.
        $post_body = file_get_contents('php://input');
        if ($post_body) {
            $data = json_decode($post_body);
            if ($data instanceof stdClass && $data->id && $data->type === 'account.application.deauthorized') {
                if (is_multisite()) {
                    // Main site.
                    switch_to_blog(get_main_network_id());
                    // Get list of blogs with this connected user.
                    $blog_list = EEM_Extra_Meta::instance()
                                                ->get_all(array(
                                                    array(
                                                        'EXM_key'   => EED_Stripe_Connect_OAuth::STRIPE_USER_ID_META_KEY,
                                                        'EXM_value' => $data->user_id,
                                                        'EXM_type'  => 'Blog'
                                                    ),
                                                    'limit' => 1
                                                ));
                    // Main site return.
                    restore_current_blog();
                    foreach ($blog_list as $blog_extra_meta) {
                        if ($blog_extra_meta instanceof EE_Extra_Meta) {
                            $disconnected = false;
                            // Connected account site.
                            switch_to_blog((int)$blog_extra_meta->get('OBJ_ID'));
                            // Try to get Stripe PM. We don't get any extra info from this Stripe web-hook so we use the standard/known slug.
                            $stripe = EEM_Payment_Method::instance()->get_one(array(array(
                                'PMD_type'             => 'Stripe',
                                'Extra_Meta.EXM_type'  => 'Payment_Method',
                                'Extra_Meta.EXM_key'   => EED_Stripe_Connect_OAuth::STRIPE_USER_ID_META_KEY,
                                'Extra_Meta.EXM_value' => $data->user_id
                            )));
                            // This is the right connection so we may reset it.
                            if ($stripe instanceof EE_Payment_Method) {
                                $stripe->delete_extra_meta('access_token');
                                $stripe->delete_extra_meta('refresh_token');
                                $stripe->delete_extra_meta('connect_publishable_key');
                                $stripe->delete_extra_meta('stripe_user_id');
                                $stripe->delete_extra_meta('livemode');
                                $stripe->update_extra_meta('using_stripe_connect', false);
                                $disconnected = true;
                            }
                            // Connected account site return.
                            restore_current_blog();
                            if ($disconnected) {
                                // Remove the connected user blog meta.
                                $blog_extra_meta->delete();
                            }
                        }
                    }
                }
            }
        }
        // Return OK status to Stripe.
        status_header(200);
        header('Content-Type: application/json');
        echo wp_json_encode(array('code' => 200));
        exit();
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
        $stripe = EEM_Payment_Method::instance()->get_one_by_slug($submitted_pm);
        $access_token = $stripe->get_extra_meta('access_token', true);
        $connected = true;
        if (empty($access_token)) {
            $connected = false;
        }
        echo wp_json_encode(array(
            'connected' => $connected,
        ));
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
        $js_out = '<script type="text/javascript">';
        if (! empty($msg)) {
            $js_out .= 'if ( window.opener ) {
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
        $using_stripe_connect = $pm_instance->get_extra_meta('using_stripe_connect', true, false);
        $connection_live_mode = $pm_instance->get_extra_meta('livemode', true);
        $pm_debug_mode = $pm_instance->debug_mode();
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
        if (apply_filters('FHEE__EE_PMT_Stripe_Onsite__generate_new_settings_form__hide_key_fields',
            ! $pm_instance->get_extra_meta('stripe_secret_key', true, false)
            // exclude fields if connected to remove any confusion
            || $using_stripe_connect
        )
        ) {
            $stripe_form->exclude(array(
                'stripe_secret_key',
                'publishable_key',
            ));
        }

        return $stripe_form;
    }
}