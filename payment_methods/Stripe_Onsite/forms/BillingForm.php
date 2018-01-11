<?php
namespace EventEspresso\Stripe\payment_methods\Stripe_Onsite\forms;
use EE_Attendee;
use EE_Billing_Info_Form;
use EE_Email_Validation_Strategy;
use EE_Error;
use EE_Float_Validation_Strategy;
use EE_Form_Section_HTML;
use EE_Form_Section_Proper;
use EE_Hidden_Input;
use EE_Payment_Method;
use EE_Registration;
use EE_Registry;
use EE_Template_Layout;
use EE_Transaction;
use EEH_Money;
use EventEspresso\Stripe\domain\Domain;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct script access allowed');

/**
 * Class BillingForm
 *
 * Form for displaying Stripe billing information
 *
 * @package     Event Espresso
 * @author         Mike Nelson
 * @since         $VID:$
 *
 */
class BillingForm extends EE_Billing_Info_Form
{
    /**
     * Filepath to template files
     * @var @template_path
     */
    protected $template_path;
    public function __construct(EE_Payment_Method $payment_method, array $options_array = array())
    {
        if (! isset($options_array['transaction']) || ! $options_array['transaction'] instanceof EE_Transaction) {
            throw new EE_Error(
                sprintf(
                    esc_html__('%1$s instantiated without the needed transaction. Please provide it in $2$s', 'event_espresso'),
                    __CLASS__,
                    '$options_array[\'transaction\']'
                )
            );
        }
        if (! isset($options_array['template_path'])) {
            throw new EE_Error(
                sprintf(
                    esc_html__('%1$s instantiated without the needed template_path. Please provide it in $2$s', 'event_espresso'),
                    __CLASS__,
                    '$options_array[\'template_path\']'
                )
            );
        }
        $transaction = $options_array['transaction'];
        $this->template_path = $options_array['template_path'];
        EE_Registry::instance()->load_helper('Money');
        $event_name = '';
        $email = '';
        if ($transaction->primary_registration() instanceof EE_Registration) {
            $event_name = $transaction->primary_registration()->event_name();
            if ($transaction->primary_registration()->attendee() instanceof EE_Attendee) {
                $email = $transaction->primary_registration()->attendee()->email();
            }
        }
        if (isset($options_array['amount_owing'])) {
            $amount = $options_array['amount_owing'] * 100;
        } else {
            // If this is a partial payment..
            $total = EEH_Money::convert_to_float_from_localized_money($transaction->total()) * 100;
            $paid = EEH_Money::convert_to_float_from_localized_money($transaction->paid()) * 100;
            $owning = $total - $paid;
            $amount = ($owning > 0) ? $owning : $total;
        }
        $options_array = array_merge(
            $options_array,
            array(
                'name' => 'Stripe_Onsite_Billing_Form',
                'html_id' => 'ee-Stripe-billing-form',
                'html_class' => 'ee-billing-form',
                'subsections' => array(
                    $this->generateBillingFormDebugContent($payment_method),
                    $this->stripeEmbeddedForm(),
                    'ee_stripe_token' => new EE_Hidden_Input(
                        array(
                            'html_id' => 'ee-stripe-token',
                            'html_name' => 'EEA_stripeToken',
                            'default' => ''
                        )
                    ),
                    'ee_stripe_transaction_email' => new EE_Hidden_Input(
                        array(
                            'html_id' => 'ee-stripe-transaction-email',
                            'html_name' => 'eeTransactionEmail',
                            'default' => $email,
                            'validation_strategies' => array(new EE_Email_Validation_Strategy())
                        )
                    ),
                    'ee_stripe_transaction_total' => new EE_Hidden_Input(
                        array(
                            'html_id' => 'ee-stripe-transaction-total',
                            'html_name' => 'eeTransactionTotal',
                            'default' => $amount,
                            'validation_strategies' => array(new EE_Float_Validation_Strategy())
                        )
                    ),
                    'ee_stripe_prod_description' => new EE_Hidden_Input(
                        array(
                            'html_id' => 'ee-stripe-prod-description',
                            'html_name' => 'stripeProdDescription',
                            'default' => apply_filters('FHEE__EE_PMT_Stripe_Onsite__generate_new_billing_form__description', $event_name, $transaction)
                        )
                    )
                )
            )
        );
        parent::__construct($payment_method, $options_array);
    }

    /**
     *  Possibly adds debug content to Stripe billing form.
     *
     * @param EE_Payment_Method $payment_Method
     * @return string
     */
    public function generateBillingFormDebugContent(EE_Payment_Method $payment_Method)
    {
        if ($payment_Method->debug_mode()) {
            return new EE_Form_Section_Proper(
                array(
                    'layout_strategy' => new EE_Template_Layout(
                        array(
                            'layout_template_file' => $this->template_path . 'stripe_debug_info.template.php',
                            'template_args' => array()
                        )
                    )
                )
            );
        }
        return new EE_Form_Section_HTML();

    }


    /**
     *  Use Stripe's Embedded form.
     *
     * @return EE_Form_Section_Proper
     * @throws \EE_Error
     */
    public function stripeEmbeddedForm()
    {
        $template_args = array();
        return new EE_Form_Section_Proper(
            array(
                'layout_strategy' => new EE_Template_Layout(
                    array(
                        'layout_template_file' => $this->template_path . 'stripe_embedded_form.template.php',
                        'template_args' => $template_args
                    )
                )
            )
        );
    }

    /**
     * EE core takes care of only enqueueing this billing form's JS (by calling this method) when we want
     * to display this billing form. This prevents issues when multiple Stripe Payment methods exist because Payment
     * Methods Pro is active.
     */
    public function enqueue_js()
    {
        wp_enqueue_style('espresso_stripe_payment_css', EE_STRIPE_URL . 'css' . DS . 'espresso_stripe.css');
        wp_enqueue_script('stripe_payment_js', 'https://checkout.stripe.com/v2/checkout.js', array(), FALSE, TRUE);
        wp_enqueue_script('espresso_stripe_payment_js', EE_STRIPE_URL . 'scripts' . DS . 'espresso_stripe_onsite.js', array('stripe_payment_js', 'single_page_checkout'), EE_STRIPE_VERSION, TRUE);
        // Data needed in the JS.
        $trans_args = array(
            'data_key' => $this->payment_method()->get_extra_meta(Domain::META_KEY_PUBLISHABLE_KEY, TRUE),
            'data_name' => EE_Registry::instance()->CFG->organization->get_pretty('name'),
            'data_image' => $this->payment_method()->get_extra_meta('stripe_logo_url', TRUE, EE_Registry::instance()->CFG->organization->get_pretty('logo_url')),
            //note its expected that we're using string values for 'true' and 'false' here. That's what the Stripe API is working with
            'validate_zip' => $this->payment_method()->get_extra_meta('validate_zip', true) ? 'true' : 'false',
            'billing_address' => $this->payment_method()->get_extra_meta('billing_address', true) ? 'true' : 'false',
            'data_locale' => $this->payment_method()->get_extra_meta('data_locale', true),
            'data_currency' => EE_Registry::instance()->CFG->currency->code,
            'data_panel_label' => sprintf(__('Pay %1$s Now', 'event_espresso'), '{{amount}}'),
            'card_error_message' => __('Payment Error! Please refresh the page and try again or contact support.', 'event_espresso'),
            'no_SPCO_error' => __('It appears the Single Page Checkout javascript was not loaded properly! Please refresh the page and try again or contact support.', 'event_espresso'),
            'no_StripeCheckout_error' => __('It appears the Stripe Checkout javascript was not loaded properly! Please refresh the page and try again or contact support.', 'event_espresso'),
            'payment_method_slug' => $this->payment_method()->slug(),
        );
        if ($this->payment_method()->debug_mode()) {
            $trans_args['data_cc_number'] = '4242424242424242';
            $trans_args['data_exp_month'] = date('m');
            $trans_args['data_exp_year'] = date('Y') + 4;
            $trans_args['data_cvc'] = '248';
        }

        // Filter JS data.
        $trans_args = apply_filters('FHEE__EE_PMT_Stripe_Onsite__enqueue_stripe_payment_scripts__js_data', $trans_args, $this->payment_method());

        // Localize the script with our transaction data.
        wp_localize_script('espresso_stripe_payment_js', 'stripe_transaction_args', $trans_args);
        parent::enqueue_js();
    }
}
// End of file BillingForm.php
// Location: ${NAMESPACE}/StripeBillingForm.php