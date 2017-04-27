<?php if ( ! defined('EVENT_ESPRESSO_VERSION') ) { exit('No direct script access allowed'); }

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

use EEA_Stripe\Stripe;
use EEA_Stripe\Stripe_Charge;

class EEG_Stripe_Onsite extends EE_Onsite_Gateway {

	protected $_publishable_key = NULL;

	protected $_stripe_secret_key = NULL;

	/**
	 * All the currencies supported by this gateway. Add any others you like,
	 * as contained in the esp_currency table
	 * @var array
	 */
	protected $_currencies_supported = EE_Gateway::all_currencies_supported;

	/**
	 *
	 * @param EEI_Payment $payment
	 * @param array       $billing_info
	 * @return \EE_Payment|\EEI_Payment
	 */
	public function do_direct_payment($payment, $billing_info = null) {
        if (! $payment instanceof EEI_Payment) {
            $payment->set_gateway_response(__('Error. No associated payment was found.', 'event_espresso'));
            $payment->set_status($this->_pay_model->failed_status());
            return $payment;
        }
        $transaction = $payment->transaction();
        if (! $transaction instanceof EE_Transaction) {
            $payment->set_gateway_response(__('Could not process this payment because it has no associated transaction.', 'event_espresso'));
            $payment->set_status($this->_pay_model->failed_status());
            return $payment;
        }
        // If this merchant is using Stripe Connect we need a to use the connected account token.
        $key = apply_filters('FHEE__EEG_Stripe_Onsite__do_direct_payment__use_connected_account_token',
                             $this->_stripe_secret_key, $transaction->payment_method());
        // Set your secret key.
        Stripe::setApiKey( $key );
        $stripe_data = array(
            'amount' => str_replace( array(',', '.'), '', number_format( $payment->amount(), 2) ),
            'currency' => $payment->currency_code(),
            'card' => $billing_info['ee_stripe_token'],
            'description' => $billing_info['ee_stripe_prod_description']
        );
        $stripe_data = apply_filters('FHEE__EEG_Stripe_Onsite__do_direct_payment__stripe_data_array', $stripe_data, $payment, $transaction, $billing_info);
        
        // Create the charge on Stripe's servers - this will charge the user's card.
        try {
            $this->log( array( 'Stripe Request data:' => $stripe_data ), $payment );
            $charge = Stripe_Charge::create( $stripe_data );
        } catch ( Stripe_CardError $error ) {
            $payment->set_status( $this->_pay_model->declined_status() );
            $payment->set_gateway_response( $error->getMessage() );
            $this->log( array('Stripe Error occurred:' => $error), $payment );
            return $payment;
        } catch ( Exception $exception ) {
            $payment->set_status( $this->_pay_model->failed_status() );
            $payment->set_gateway_response( $exception->getMessage() );
            $this->log( array('Stripe Error occurred:' => $exception), $payment );
            return $payment;
        }

        $charge_array = $charge->__toArray(true);
        $this->log( array( 'Stripe charge:' => $charge_array ), $payment );
        $payment->set_gateway_response( $charge_array['status'] );
        $payment->set_txn_id_chq_nmbr( $charge_array['id'] );
        $payment->set_details( $charge_array );
        $payment->set_amount( floatval( $charge_array['amount'] / 100 ) );
        $payment->set_status( $this->_pay_model->approved_status() );
		return $payment;
	}
}

// End of file EEG_Stripe_Onsite.gateway.php