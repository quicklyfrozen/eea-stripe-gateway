<?php if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}



/**
 * EE_Stripe_OAuth_Form
 * OAuth integration form contents.
 *
 * @package            Event Espresso
 * @subpackage         espresso-stripe-connect
 * @author             Event Espresso
 */
class EE_Stripe_OAuth_Form extends EE_Form_Section_Proper
{

    /**
     *  Payment method.
     *
     * @var EE_PMT_Base
     */
    protected $_pmt;

    /**
     *  Payment method instance.
     *
     * @var EE_PMT_Base
     */
    protected $_the_pm_instance;

    /**
     *  Payment method slug.
     *
     * @var EE_PMT_Base
     */
    protected $_pm_slug;

    /**
     *  Stripe Connect button text.
     *
     * @var string
     */
    protected $_connect_btn_text;

    /**
     *  Stripe Connect button in sandbox mode text.
     *
     * @var string
     */
    protected $_connect_btn_sandbox_text;

    /**
     *  Stripe connected section sandbox mode text.
     *
     * @var string
     */
    protected $_connected_sandbox_text;



    /**
     * Class constructor.
     *
     * @param  EE_PMT_Base       $pmt
     * @param  EE_Payment_Method $payment_method
     * @throws EE_Error
     */
    public function __construct(EE_PMT_Base $pmt, EE_Payment_Method $payment_method)
    {
        $this->_pmt                      = $pmt;
        $this->_the_pm_instance          = $payment_method;
        $this->_pm_slug                  = $this->_the_pm_instance->slug();
        $this->_connect_btn_text         = esc_html__('Connect with Stripe', 'event_espresso');
        $this->_connect_btn_sandbox_text = esc_html__('Connect with Stripe (sandbox)', 'event_espresso');
        $this->_connected_sandbox_text   = esc_html__('Test mode (using sandbox credentials)', 'event_espresso');
        $options                         = array(
            'html_id'               => $this->_pm_slug . '_oauth_connect_form',
            'layout_strategy'       => new EE_Admin_Two_Column_Layout(),
            'validation_strategies' => array(new EE_Simple_HTML_Validation_Strategy()),
            'subsections'           => $this->_oauth_form_contents(),
        );
        parent::__construct($options);
    }



    /**
     * Generate the Connect and Disconnect buttons.
     *
     * @access public
     * @return array
     * @throws EE_Error
     */
    protected function _oauth_form_contents()
    {
        $field_heading = EEH_HTML::th(
            sprintf(
                esc_html__('Stripe Connect: %1$s *', 'event_espresso'),
                $this->_pmt->get_help_tab_link()
            )
        );
        // Is this a test connection?
        $livemode_txt = ! $this->_the_pm_instance->get_extra_meta('livemode', true)
            ? ' ' . EEH_HTML::strong(
                $this->_connected_sandbox_text,
                'eeg_stripe_test_connected_txt',
                'eeg-stripe-test-connected-txt'
            )
            : '';
        $is_oauthed   = $this->_is_oauthed();
        // subsections array
        return array(
            // Section to be displayed if not connected.
            'stripe_connect_btn' => new EE_Form_Section_HTML(
                EEH_HTML::tr(
                    $field_heading .
                    EEH_HTML::td(
                        EEH_HTML::link(
                            '#',
                            EEH_HTML::span($this->_connect_btn_text),
                            '',
                            'eeg_stripe_connect_btn',
                            'eeg-stripe-connect-btn'
                        )
                    ),
                    'eeg_stripe_connect_' . $this->_pm_slug,
                    'eeg-stripe-connect-section',
                    $is_oauthed ? 'display:none;' : ''    // Are we OAuth'ed ?
                )
            ),
            // Section to be displayed when connected.
            'stripe_disconnect_btn' => new EE_Form_Section_HTML(
                EEH_HTML::tr(
                    $field_heading .
                    EEH_HTML::td(
                        EEH_HTML::img(
                            EE_STRIPE_URL . 'assets' . DS . 'lib' . DS . 'stripe-connected.png',
                            '',
                            'eeg_stripe_connected_ico',
                            'eeg-stripe-connected-ico'
                        ) .
                        EEH_HTML::strong(
                            esc_html__('Connected.', 'event_espresso'),
                            'eeg_stripe_connected_txt',
                            'eeg-stripe-connected-txt'
                        ) .
                        $livemode_txt .
                        EEH_HTML::link(
                            '#',
                            EEH_HTML::span(esc_html__('Disconnect', 'event_espresso')),
                            '',
                            'eeg_stripe_disconnect_btn',
                            'eeg-stripe-connect-btn light-blue'
                        )
                    ),
                    'eeg_stripe_disconnect_' . $this->_pm_slug,
                    'eeg-stripe-disconnect-section',
                    $is_oauthed ? '' : 'display:none;'    // Are we OAuth'ed ?
                )
            )
        );
    }



    /**
     * Add JS needed for this form. This is called automatically when displaying the form.
     *
     * @return string
     * @throws EE_Error
     */
    public function enqueue_js()
    {
        $stripe_connect_args = array(
            'payment_method_slug'       => $this->_pm_slug,
            'request_connection_errmsg' => esc_html__('Error while requesting the redirect URL.', 'event_espresso'),
            'blocked_popups_notice'     => esc_html__(
                'The authentication process could not be executed. Please allow window pop-ups in your browser for this website in order to process a successful authentication.',
                'event_espresso'
            ),
            'pm_debug_is_on_notice'     => esc_html__(
            // @codingStandardsIgnoreStart
                'The authentication with Stripe Connect is in sandbox mode! If you wish to process real payments with this payment method, please reset the connection and use live credentials to authenticate with Stripe Connect.',
                // @codingStandardsIgnoreEnd
                'event_espresso'
            ),
            'pm_debug_is_off_notice'    => esc_html__(
            // @codingStandardsIgnoreStart
                'The authentication with Stripe Connect is in Live mode! If you wish to test this payment method, please reset the connection and use sandbox credentials to authenticate with Stripe Connect.',
                // @codingStandardsIgnoreEnd
                'event_espresso'
            ),
            'error_response'            => esc_html__('Error response received', 'event_espresso'),
            'oauth_request_error'       => esc_html__('oAuth request Error.', 'event_espresso'),
            'unknown_container'         => esc_html__('Could not specify the parent form.', 'event_espresso'),
            'espresso_default_styles'   => EE_ADMIN_URL . 'assets/ee-admin-page.css',
            'wp_stylesheet'             => includes_url('css/dashicons.min.css'),
            'connect_btn_text'          => $this->_connect_btn_text,
            'connect_btn_sandbox_text'  => $this->_connect_btn_sandbox_text,
            'connected_sandbox_text'    => $this->_connected_sandbox_text,
        );
        // Styles
        wp_enqueue_style(
            'eea_stripe_connect_form_styles',
            EE_STRIPE_URL . 'assets' . DS . 'css' . DS . 'espresso_stripe_connect.css',
            array(),
            EE_STRIPE_VERSION
        );
        // Scripts
        wp_enqueue_script(
            'eea_stripe_connect_form_scripts',
            EE_STRIPE_URL . 'assets' . DS . 'scripts' . DS . 'espresso_stripe_connect.js',
            array(),
            EE_STRIPE_VERSION
        );
        // Localize the script with some extra data.
        wp_localize_script('eea_stripe_connect_form_scripts', 'EEG_STRIPE_CONNECT_ARGS', $stripe_connect_args);
        return parent::enqueue_js();
    }



    /**
     *  Check if already Connected.
     *
     * @return bool
     * @throws EE_Error
     */
    private function _is_oauthed()
    {
        if (! $this->_the_pm_instance) {
            return false;
        }
        $access_token = $this->_the_pm_instance->get_extra_meta('stripe_secret_key', true);
        if (! empty($access_token)) {
            return true;
        }
        return false;
    }
}
