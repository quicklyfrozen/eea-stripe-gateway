<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EEG_Stripe_Onsite
 *
 * Just approves payments where billing_info[ 'credit_card' ] == 1.
 * If $billing_info[ 'credit_card' ] == '2' then its pending.
 * All others get refused
 *
 * @package			Event Espresso
 * @subpackage		espresso-stripe-gateway
 * @author			Event Espresso
 *
 */
class EEG_Stripe_Onsite extends EE_Onsite_Gateway {

	protected $_publishable_key = NULL;

	protected $_stripe_secret_key = NULL;

	/**
	 * All the currencies supported by this gateway. Add any others you like,
	 * as contained in the esp_currency table
	 * @var array
	 */
	protected $_currencies_supported = array(
		'USD',
		'GBP',
		'CAD',
		'AUD'
	);

	/**
	 *
	 * @param EEI_Payment $payment
	 * @param array $billing_info
	 */
	public function do_direct_payment($payment, $billing_info = null) {
		$transaction = $payment->transaction();

		// Set your secret key.
		Stripe::setApiKey( $this->_stripe_secret_key );

		// Get the credit card details submitted by the form.
		$token = $billing_info['ee_stripe_token'];
		$description = $billing_info['ee_stripe_prod_description'];
		$amount = str_replace( array(',', '.'), '', number_format( $payment->amount(), 2));

		// Create the charge on Stripe's servers - this will charge the user's card.
		try {
			$charge = Stripe_Charge::create( array(
				'amount' => $amount,
				'currency' => $payment->currency_code(),
				'card' => $token,
				'description' => $description
			));
		} catch ( Stripe_CardError $e ) {
			$payment->set_status($this->_pay_model->failed_status());
			$payment->set_gateway_response($e->getMessage());
			return $payment;
		}

		//$this->log( $billing_info, $payment );
		$payment->set_status( $this->_pay_model->approved_status() );
		return $payment;
	}
}

// End of file EEG_Stripe_Onsite.gateway.php