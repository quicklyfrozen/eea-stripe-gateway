<?php
use EventEspresso\Stripe\domain\Domain;
use EventEspresso\Stripe\forms\BillingForm;
if (!defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}

/**
 *
 * EE_PMT_Onsite
 *
 *
 * @package            Event Espresso
 * @subpackage        espresso-stripe-gateway
 * @author            Event Espresso
 *
 */
class EE_PMT_Stripe_Onsite extends EE_PMT_Base
{

    /**
     * path to the templates folder for the Stripe PM
     * @var string
     */
    protected $_template_path = NULL;


    /**
     *
     * @param EE_Payment_Method $pm_instance
     * @throws \EE_Error
     * @return \EE_PMT_Stripe_Onsite
     */
    public function __construct($pm_instance = NULL)
    {
        $this->_pretty_name = __("Stripe", 'event_espresso');
        $this->_default_description = __('Click the "Pay Now" button to proceed with payment.', 'event_espresso');
        $this->_template_path = dirname(__FILE__) . DS . 'templates' . DS;
        $this->_requires_https = FALSE;
        $this->_cache_billing_form = FALSE;
        $this->_default_button_url = EE_STRIPE_URL . 'payment_methods' . DS . 'Stripe_Onsite' . DS . 'lib' . DS . 'stripe-cc-logo.png';

        // Include Stripe API dependencies.
        require_once(EE_STRIPE_PATH . 'includes' . DS . 'stripe_dependencies' . DS . 'lib' . DS . 'Stripe.php');

        require_once($this->file_folder() . 'EEG_Stripe_Onsite.gateway.php');
        $this->_gateway = new EEG_Stripe_Onsite();

        parent::__construct($pm_instance);
    }


    /**
     * Generate a new payment settings form.
     *
     * @return EE_Payment_Method_Form
     */
    public function generate_new_settings_form()
    {
        $pms_form = new EE_Payment_Method_Form(array(
            'extra_meta_inputs' => array(
                Domain::META_KEY_SECRET_KEY => new EE_Text_Input(array(
                    'html_label_text' => sprintf(__("Stripe Secret Key %s", "event_espresso"), $this->get_help_tab_link())
                )),
                Domain::META_KEY_PUBLISHABLE_KEY => new EE_Text_Input(array(
                    'html_label_text' => sprintf(__("Stripe Publishable Key %s", "event_espresso"), $this->get_help_tab_link())
                )),
                'validate_zip' => new EE_Yes_No_Input(
                    array(
                        'html_label_text' => sprintf(__("Validate the billing ZIP code? %s", 'event_espresso'), $this->get_help_tab_link()),
                        'default' => false,
                        'required' => true
                    )
                ),
                'billing_address' => new EE_Yes_No_Input(
                    array(
                        'html_label_text' => sprintf(__("Collect the user's billing address? %s", 'event_espresso'), $this->get_help_tab_link()),
                        'default' => false,
                        'required' => true
                    )
                ),
                'data_locale' => new EE_Select_Input(
                    array(
                        null => __('None', 'event_espresso'),
                        'auto' => __('Auto (Defaults to English)', 'event_espresso'),
                        'zh' => __('Simplified Chinese', 'event_espresso'),
                        'da' => __('Danish', 'event_espresso'),
                        'nl' => __('Dutch', 'event_espresso'),
                        'en' => __('English', 'event_espresso'),
                        'fi' => __('Finnish', 'event_espresso'),
                        'fr' => __('French', 'event_espresso'),
                        'de' => __('German', 'event_espresso'),
                        'it' => __('Italian', 'event_espresso'),
                        'ja' => __('Japanese', 'event_espresso'),
                        'no' => __('Norwegian', 'event_espresso'),
                        'es' => __('Spanish', 'event_espresso'),
                        'sv' => __('Swedish', 'event_espresso')
                    ),
                    array(
                        'html_label_text' => sprintf(__("Checkout locale %s", 'event_espresso'), $this->get_help_tab_link()),
                        'html_help_text' => __("This is the locale sent to Stripe to determine which language the checkout modal should use.", 'event_espresso')
                    )
                ),
                'stripe_logo_url' => new EE_Admin_File_Uploader_Input(array(
                        'html_label_text' => sprintf(__("Logo URL %s", "event_espresso"), $this->get_help_tab_link()),
                        'default' => EE_Registry::instance()->CFG->organization->get_pretty('logo_url'),
                        'html_help_text' => __("(Logo shown on Stripe checkout)", 'event_espresso'),
                    )
                )
            )
        ));

        // Filtering the form contents.
        $pms_form = apply_filters('FHEE__EE_PMT_Stripe_Onsite__generate_new_settings_form__form_filtering', $pms_form, $this, $this->_pm_instance);

        return $pms_form;
    }


    /**
     * Creates a billing form for this payment method type.
     * @param \EE_Transaction $transaction
     * @return \EE_Billing_Info_Form
     */
    public function generate_new_billing_form(EE_Transaction $transaction = NULL, $extra_args = array())
    {
        EE_Registry::instance()->load_helper('Money');
        $event_name = '';
        $email = '';
        if ($transaction->primary_registration() instanceof EE_Registration) {
            $event_name = $transaction->primary_registration()->event_name();
            if ($transaction->primary_registration()->attendee() instanceof EE_Attendee) {
                $email = $transaction->primary_registration()->attendee()->email();
            }
        }
        if (isset($extra_args['amount_owing'])) {
            $amount = $extra_args['amount_owing'];
        } else {
            // If this is a partial payment..
            $total = EEH_Money::convert_to_float_from_localized_money($transaction->total());
            $paid = EEH_Money::convert_to_float_from_localized_money($transaction->paid());
            $owning = $total - $paid;
            $amount = ($owning > 0) ? $owning : $total;
        }
        $amount = $this->_gateway->prepare_amount_for_stripe($amount);

        //provide amount_owing and transaction
        return new BillingForm(
            $this->_pm_instance,
            array_merge(
                array(
                    'transaction' => $transaction,
                    'template_path' => $this->_template_path
                ),
                $extra_args
            )
        );
    }


    /**
     *  Possibly adds debug content to Stripe billing form.
     *
     * @return string
     * @deprecated in 1.1.1.p. Instead EventEspresso\Stripe\payment_methods\Stripe_Onsite\forms\BillingForm takes care of this
     */
    public function generate_billing_form_debug_content()
    {
        if ($this->_pm_instance->debug_mode()) {
            return new EE_Form_Section_Proper(
                array(
                    'layout_strategy' => new EE_Template_Layout(
                        array(
                            'layout_template_file' => $this->_template_path . 'stripe_debug_info.template.php',
                            'template_args' => array()
                        )
                    )
                )
            );
        } else {
            return new EE_Form_Section_HTML();
        }
    }


    /**
     *  Use Stripe's Embedded form.
     *
     * @return EE_Form_Section_Proper
     * @deprecated in 1.1.1.p. Instead EventEspresso\Stripe\payment_methods\Stripe_Onsite\forms\BillingForm takes care of this
     */
    public function stripe_embedded_form()
    {
        $template_args = array();
        return new EE_Form_Section_Proper(
            array(
                'layout_strategy' => new EE_Template_Layout(
                    array(
                        'layout_template_file' => $this->_template_path . 'stripe_embedded_form.template.php',
                        'template_args' => $template_args
                    )
                )
            )
        );
    }


    /**
     * Adds the help tab
     *
     * @see EE_PMT_Base::help_tabs_config()
     * @return array
     */
    public function help_tabs_config()
    {
        return array(
            $this->get_help_tab_name() => array(
                'title' => __('Stripe Settings', 'event_espresso'),
                'filename' => 'payment_methods_overview_stripe'
            ),
        );
    }


    /**
     * Log Stripe TXN Error.
     *
     * @return void
     */
    public static function log_stripe_error()
    {
        if (isset($_POST['txn_id']) && !empty($_POST['txn_id'])) {
            $stripe_pm = EEM_Payment_method::instance()->get_one_of_type('Stripe_Onsite');
            $transaction = EEM_Transaction::instance()->get_one_by_ID($_POST['txn_id']);
            $stripe_pm->type_obj()->get_gateway()->log(array('Stripe JS Error (Transaction: ' . $transaction->ID() . ')' => $_POST['message']), $transaction);
        }
    }
}

// End of file EE_PMT_Stripe_Onsite.pm.php
