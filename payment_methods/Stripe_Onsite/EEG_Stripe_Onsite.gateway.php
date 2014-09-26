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
		// Set your secret key: remember to change this to your live secret key in production
		//Stripe::setApiKey("sk_test_BQokikJOvBiI2HlWgH4olfQ2");

		// Get the credit card details submitted by the form.
		//$token = $billing_info['stripeToken'];

		// Create the charge on Stripe's servers - this will charge the user's card.
		/*try {
			$charge = Stripe_Charge::create( array(
				"amount" => 1000, // amount in cents, again
				"currency" => "usd",
				"card" => $token,
				"description" => "payinguser@example.com")
			);
		} catch ( Stripe_CardError $e ) {
			$payment->set_status($this->_pay_model->failed_status());
			$payment->set_gateway_response($e->getMessage());
			return $payment;
		}*/

		//$this->log( $billing_info, $payment );
		$payment->set_status( $this->_pay_model->approved_status() );
		return $payment;
	}
}

// End of file EEG_Stripe_Onsite.gateway.php