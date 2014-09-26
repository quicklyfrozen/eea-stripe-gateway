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

	public function __construct( $pm_instance = NULL ) {
		// Scripts for generating Stripe token.
		add_action( 'wp_enqueue_scripts', array($this, 'enqueue_stripe_payment_scripts') );

		require_once( $this->file_folder() . 'EEG_Stripe_Onsite.gateway.php' );
		$this->_gateway = new EEG_Stripe_Onsite();
		$this->_pretty_name = __("Stripe Onsite", 'event_espresso');
		$this->_default_description = __( 'Please provide the following billing information:', 'event_espresso' );
		parent::__construct($pm_instance);
		$this->_default_button_url = $this->file_url() . 'lib' . DS . 'stripe-default-logo.png';
	}

	/**
	 * Generate a new payment settings form.
	 *
	 * @return EE_Payment_Method_Form
	 */
	public function generate_new_settings_form() {
		EE_Registry::instance()->load_helper('Template');
		$form = new EE_Payment_Method_Form( array(
			'extra_meta_inputs' => array(
				'stripe_embedded_form' => new EE_Checkbox_Multi_Input( array(
					'stripe_embedded_checkout' => sprintf( __( 'Use Stripe Embedded Form %s', 'event_espresso' ), $this->get_help_tab_link() )
				)),
				'secter_key' => new EE_Text_Input( array(
					'html_label_text' => sprintf( __("Stripe Secret Key %s", "event_espresso"), $this->get_help_tab_link() )
				)),
				'publishable_key' => new EE_Text_Input( array(
					'html_label_text' => sprintf( __("Stripe Publishable Key %s", "event_espresso"), $this->get_help_tab_link() )
				))
			)
		));
		return $form;
	}

	/**
	 * Creates a billing form for this payment method type.
	 *
	 */
	public function generate_new_billing_form() {
		$form_name = 'Stripe_Onsite_Form';
		$billing_form = new EE_Billing_Info_Form( $this->_pm_instance, array(
			'name' => $form_name,
			'html_id'=> 'ee-Stripe-billing-form',
			'html_class'=> 'ee-billing-form',
			'subsections' => array(
				'credit_card' => new EE_Credit_Card_Input( array('required' => true, 'html_id' => 'ee-stripe-card')),
				'cvv' => new EE_CVV_Input( array('required' => true, 'html_id' => 'ee-stripe-cvv')),
				'exp_month' => new EE_Month_Input( true, array('required' => true, 'html_id' => 'ee-stripe-expmonth')),
				'exp_year' => new EE_Year_Input( true, 1, 15, array('required' => true, 'html_id' => 'ee-stripe-expyear')),
			)
		));

		// Shorten the form.
		$billing_form->exclude( array(
				'address',
				'address2',
				'city',
				'state',
				'country',
				'zip',
				'phone'
			));

		// Tweak the form (in the template we check for debug mode and whether ot add any content or not).
		add_filter( 'FHEE__EE_Form_Section_Layout_Base__layout_form__start__for_' . $form_name, array('EE_PMT_Stripe_Onsite', 'generate_billing_form_debug_content'), 10, 2 );
		if ( $this->_pm_instance->debug_mode() ) {
			$billing_form->get_input('credit_card')->set_default( '4242424242424242' );
			$billing_form->get_input('exp_year')->set_default( date('Y') + 4 );
			$billing_form->get_input('cvv')->set_default( '248' );
		}

		// Just leave this for an example:
		add_filter( 'FHEE__EE_Form_Section_Layout_Base__layout_form__end__for_' . $form_name, array('EE_PMT_Stripe_Onsite', 'generate_stripe_embedded_form'), 10, 2 );

		return $billing_form;
	}

	/**
	 *  Possibly adds debug content to Stripe billing form.
	 *
	 * @param string $form_begin_content
	 * @param EE_Billing_Info_Form $form_section
	 * @return string
	 */
	public static function generate_billing_form_debug_content( $form_begin_content, $form_section ) {
		EE_Registry::instance()->load_helper('Template');
		return EEH_Template::display_template( dirname(__FILE__) . DS . 'templates' . DS . 'stripe_debug_info.template.php', array('form_section' => $form_section), true ) . $form_begin_content;
	}

	/**
	 *  Use Stripe's Embedded form.
	 *
	 * @param string $form_end_content
	 * @param EE_Billing_Info_Form $form_section
	 * @return string
	 */
	public static function generate_stripe_embedded_form( $form_end_content, $form_section ) {
		EE_Registry::instance()->load_helper('Template');
		return EEH_Template::display_template( dirname(__FILE__) . DS . 'templates' . DS . 'stripe_embedded_form.template.php', array('form_section' => $form_section), true ) . $form_end_content;
	}

	/**
	 *  Load all the scripts needed for the Stripe checkout.
	 *
	 * @return void
	 */
	public function enqueue_stripe_payment_scripts() {
		wp_enqueue_script( 'stripe_payment_js', 'https://checkout.stripe.com/v2/checkout.js', '', '2.0' );
		wp_enqueue_script( 'espresso_stripe_payment_js', EE_STRIPE_URL . 'scripts' . DS . 'espresso_stripe_onsite.js', '', EE_STRIPE_VERSION );
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