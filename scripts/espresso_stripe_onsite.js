jQuery(document).ready(function($) {

	var EE_STRIPE;

	/**
	 * @namespace EE_STRIPE
	 * @type {{
		 *     StripeCheckout: object,
		 *     handler: object
		 *     token: object
	 * }}
	 * @namespace transaction_args
	 * @type {{
	 *     data_key: string,
	 *     data_name: string
	 *     data_image: string
	 *     data_cc_number: number
	 *     data_exp_month: number
	 *     data_exp_year: number
	 *     data_cvc: number
	 *     accepted_message: string
	 *     no_SPCO_error: string
	 *     no_StripeCheckout_error: string
	 * }}
	 */
	EE_STRIPE = {

		handler : {},
		submit_payment_button : $('#custom-stripe-button'),



		/**
		 * @function init		 *
		 */
		init : function() {
			// ensure that the SPCO js class is loaded
			if ( typeof SPCO === 'undefined' ) {
				SPCO.scroll_to_top_and_display_messages( SPCO.main_container, SPCO.generate_message_object( transaction_args.no_SPCO_error, '', '' ));
				return;
			}
			// ensure that the StripeCheckout js class is loaded
			if ( typeof StripeCheckout === 'undefined' ) {
				SPCO.scroll_to_top_and_display_messages( SPCO.main_container, SPCO.generate_message_object( transaction_args.no_StripeCheckout_error, '', '' ));
				return;
			}
			EE_STRIPE.set_up_handler();
			EE_STRIPE.set_listener_for_payment_method_selector();
			EE_STRIPE.set_listener_for_submit_payment_button();
		},



		/**
		 * @function set_up_handler
		 */
		set_up_handler : function() {
			EE_STRIPE.handler = StripeCheckout.configure({
				key: transaction_args.data_key,
				image: transaction_args.data_image,
				token: function( token ) {
					// Use the token to create the charge with a server-side script.
					$('#ee-stripe-token').val( token.id );
				}
			});
		},



		/**
		 * @function set_listener_for_payment_method_selector
		 */
		set_listener_for_payment_method_selector : function() {
			SPCO.main_container.on( 'click', '#ee-available-payment-method-inputs-stripe_onsite', function(e) {
				// Deactivate SPCO submit buttons to prevent submitting with no Stripe token.
				SPCO.disable_submit_buttons();
			});
		},



		/**
		 * @function set_listener_for_submit_payment_button
		 */
		set_listener_for_submit_payment_button : function() {
			SPCO.main_container.on( 'click', '#custom-stripe-button', function(e) {
				e.preventDefault();
				e.stopPropagation();
				// Open Checkout with further options.
				EE_STRIPE.handler.open({
					name: transaction_args.data_name,
					amount: $('#ee-stripe-transaction-total').val(),
					description: $('#ee-stripe-prod-description').val()
				});
				// Enable SPCO submit buttons.
				SPCO.enable_submit_buttons();
				EE_STRIPE.submit_payment_button.disabled = true;
				EE_STRIPE.submit_payment_button.val( transaction_args.accepted_message );
			});
		}


	};

	EE_STRIPE.init();

});
