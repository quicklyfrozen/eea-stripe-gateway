<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EEG_Stripe_Offsite
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
class EEG_Stripe_Offsite extends EE_Offsite_Gateway {

	/**
	 *
	 * @param arrat $update_info {
	 *	@type string $gateway_txn_id
	 *	@type string status an EEMI_Payment status
	 * }
	 * @param type $transaction
	 * @return EEI_Payment
	 */
	public function handle_payment_update($update_info, $transaction) {
		if ( ! isset( $update_info[ 'gateway_txn_id' ] ) ) {
			return NULL;
		}
		//$payment = $this->_pay_model->get_payment_by_txn_id_chq_nmbr($update_info[ 'gateway_txn_id' ] );
		$payment = $transaction->last_payment();
		if ( $payment instanceof EEI_Payment &&  isset( $update_info[ 'status' ] ) ) {
			if ( $update_info[ 'status' ] == $this->_pay_model->approved_status() ) {
				$payment->set_status( $this->_pay_model->approved_status() );
			} elseif ( $update_info[ 'status' ] == $this->_pay_model->pending_status() ) {
				$payment->set_status( $this->_pay_model->pending_status() );
			} else {
				$payment->set_status( $this->_pay_model->failed_status() );
			}
		}
		return $payment;
	}

	/**
	 *
	 * @param EEI_Payment $payment
	 * @param type $billing_info
	 * @param type $return_url
	 * @param type $cancel_url
	 */
	public function set_redirection_info($payment, $billing_info = array(), $return_url = NULL, $cancel_url = NULL) {
		global $auto_made_thing_seed;

		$redirect_args = array(
			'data-key' => "pk_test_6pRNASCoBOKtIshFeQd4XMUh",
		    'data-amount' => "2000",
		    'data-name' => "Demo Site",
		    'data-description' => "2 widgets ($20.00)",
		    'data-image' => "/128x128.png"
		);
		$payment->set_redirect_args($redirect_args);

		$payment->set_redirect_url('https://checkout.stripe.com/checkout.js');
		if ( $this->_debug_mode ) {
			$payment->set_redirect_url('https://checkout.stripe.com/checkout.js');
		}
		$payment->set_txn_id_chq_nmbr( $auto_made_thing_seed++ );
		return $payment;
	}
}

// End of file EEG_Stripe_Offsite.gateway.php