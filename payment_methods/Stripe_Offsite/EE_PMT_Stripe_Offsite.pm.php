<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EE_PMT_Stripe_Offsite
 *
 *
 * @package			Event Espresso
 * @subpackage		espresso-stripe-gateway
 * @author			Event Espresso
 *
 */
class EE_PMT_Stripe_Offsite extends EE_PMT_Base {
	
	/**
     * Class constructor.
     */
	public function __construct( $pm_instance = NULL ) {
		require_once( $this->file_folder() . 'EEG_Stripe_Offsite.gateway.php' );
		$this->_gateway = new EEG_Stripe_Offsite();
		$this->_pretty_name = __("Stripe Offsite", 'event_espresso');
		$this->_default_description = __( 'After clicking \'Finalize Registration\', you will be able to enter your billing information and complete your payment, ', 'event_espresso' );
		parent::__construct( $pm_instance );
		$this->_default_button_url = $this->file_url() . 'lib' . DS . 'stripe-offsite-logo.png';
	}

	/**
	 * Generate a new payment settings form.
	 *
	 * @return EE_Payment_Method_Form
	 */
	public function generate_new_settings_form() {
		EE_Registry::instance()->load_helper('Template');
		$form = new EE_Payment_Method_Form(array(
			'extra_meta_inputs'=>array(
				'stripe_api_key'=>new EE_Text_Input(array(
					'html_label_text'=>  sprintf(__("Stripe API Key %s", "event_espresso"),  $this->get_help_tab_link())
				)))));
		return $form;
	}

	/**
	 * Creates the billing form for this payment method type.
	 *
	 * @return EE_Billing_Info_Form
	 */
	public function generate_new_billing_form() {
		return NULL;
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

// End of file EE_PMT_Stripe_Offsite.php