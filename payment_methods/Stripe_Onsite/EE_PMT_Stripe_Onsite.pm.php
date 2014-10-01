<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EE_PMT_Onsite
 *
 *
 * @package			Event Espresso
 * @subpackage		espresso-stripe-gateway
 * @author			Event Espresso
 *
 */

class EE_PMT_Stripe_Onsite extends EE_PMT_Base {

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
	public function __construct( $pm_instance = NULL ) {
		$this->_pretty_name = __("Stripe", 'event_espresso');
		$this->_default_description = __( 'Click the "PAY WITH CARD" button to proceed with payment.', 'event_espresso' );
		require_once( $this->file_folder() . 'EEG_Stripe_Onsite.gateway.php' );
		$this->_gateway = new EEG_Stripe_Onsite();
		$this->_default_button_url = $this->file_url() . 'lib' . DS . 'stripe-default-logo.png';
		$this->_template_path = dirname(__FILE__) . DS . 'templates' . DS;

		parent::__construct( $pm_instance );

		// Scripts for generating Stripe token.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_stripe_payment_scripts' ));
	}


	/**
	 * Generate a new payment settings form.
	 *
	 * @return EE_Payment_Method_Form
	 */
	public function generate_new_settings_form() {

		return new EE_Payment_Method_Form( array(
			'extra_meta_inputs' => array(
				'publishable_key' => new EE_Text_Input( array(
					'html_label_text' => sprintf( __("Stripe Publishable Key %s", "event_espresso"), $this->get_help_tab_link() )
				)),
				'stripe_secret_key' => new EE_Text_Input( array(
					'html_label_text' => sprintf( __("Stripe Secret Key %s", "event_espresso"), $this->get_help_tab_link() )
				))
			)
		));
	}


	/**
	 * Creates a billing form for this payment method type.
	 * @param \EE_Transaction $transaction
	 * @return \EE_Billing_Info_Form
	 */
	public function generate_new_billing_form( EE_Transaction $transaction = NULL ) {

		return new EE_Billing_Info_Form(
			$this->_pm_instance,
			array(
				'name' => 'stripe_onsite_billing_form',
				'html_id'=> 'ee-Stripe-billing-form',
				'html_class'=> 'ee-billing-form',
				'subsections' => array(
					$this->generate_billing_form_debug_content(),
					$this->stripe_embedded_form( $transaction ),
					'ee_stripe_token' => new EE_Hidden_Input(
						array(
							'html_id' => 'ee-stripe-token',
							'html_name' => 'stripeToken',
							'default' => ''
						)
					),
					'ee_stripe_transaction_total' => new EE_Hidden_Input(
						array(
							'html_id' => 'ee-stripe-transaction-total',
							'html_name' => 'eeTransactionTotal',
							'default' => str_replace( array(',', '.'), '', number_format($transaction->total(), 2))
						)
					),
					'ee_stripe_prod_description' => new EE_Hidden_Input(
						array(
							'html_id' => 'ee-stripe-prod-description',
							'html_name' => 'stripeProdDescription',
							'default' => $transaction->primary_registration()->event_name()
						)
					)
				)
			)
		);
	}


	/**
	 *  Possibly adds debug content to Stripe billing form.
	 *
	 * @return string
	 */
	public function generate_billing_form_debug_content() {
		if ( $this->_pm_instance->debug_mode() ) {
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
	 * @param \EE_Transaction $transaction
	 * @return EE_Form_Section_Proper
	 */
	public function stripe_embedded_form( EE_Transaction $transaction = NULL ) {
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
	 *  Load all the scripts needed for the Stripe checkout.
	 *
	 * @return void
	 */
	public function enqueue_stripe_payment_scripts() {
		wp_enqueue_script( 'stripe_payment_js', 'https://checkout.stripe.com/v2/checkout.js', array(), FALSE, TRUE );
		wp_enqueue_script( 'espresso_stripe_payment_js', EE_STRIPE_URL . 'scripts' . DS . 'espresso_stripe_onsite.js', array( 'stripe_payment_js', 'single_page_checkout' ), EE_STRIPE_VERSION, TRUE );

		// Data needed in the JS.
		$trans_args = array(
			'data_key' => $this->_pm_instance->get_extra_meta( 'publishable_key', TRUE ),
			'data_name' => EE_Registry::instance()->CFG->organization->name,
			'data_image' => EE_Registry::instance()->CFG->organization->logo_url,
			'data_cc_number' => '4242424242424242',
			'data_exp_month' => date('m'),
			'data_exp_year' => date('Y') + 4,
			'data_cvc' => '248'
		);

		// Localize the script with our transaction data.
		wp_localize_script( 'espresso_stripe_payment_js', 'transaction_args', $trans_args);
	}


	/**
	 * Adds the help tab
	 *
	 * @see EE_PMT_Base::help_tabs_config()
	 * @return array
	 */
	public function help_tabs_config(){
		return array(
			$this->get_help_tab_name() => array(
				'title' => __('Stripe Settings', 'event_espresso'),
				'filename' => 'payment_methods_overview_stripe'
			),
		);
	}
}

// End of file EE_PMT_Stripe_Onsite.pm.php