jQuery(document).ready(function($) {

	var EE_STRIPE;

	/**
	 * @namespace EE_STRIPE
	 * @type {{
		 *     handler: object,
		 *     submit_payment_button: object,
		 *     error_msg: string,
	 * }}
	 * @namespace StripeCheckout
	 * @type {{
		 *     configure: function,
		 *     handler: object
	 * }}
	 * @namespace token
	 * @type {{
		 *     error: object
		 *     id: string
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
	 *     card_error_message: string
	 *     no_SPCO_error: string
	 *     no_StripeCheckout_error: string
	 * }}
	 */
	EE_STRIPE = {

		handler : {},
		submit_payment_button : $('#custom-stripe-button'),
		error_msg : '',



		/**
		 * @function init
		 */
		init : function() {
			// ensure that the SPCO js class is loaded
			if ( typeof SPCO === 'undefined' ) {
				EE_STRIPE.error_msg = SPCO.generate_message_object( SPCO.tag_message_for_debugging( 'EE_STRIPE.init() error', transaction_args.no_SPCO_error ), '', '' );
				SPCO.scroll_to_top_and_display_messages( SPCO.main_container, EE_STRIPE.error_msg );
				return;
			}
			// ensure that the StripeCheckout js class is loaded
			if ( typeof StripeCheckout === 'undefined' ) {
				EE_STRIPE.error_msg = SPCO.generate_message_object( SPCO.tag_message_for_debugging( 'EE_STRIPE.init() error', transaction_args.no_StripeCheckout_error ), '', '' );
				SPCO.scroll_to_top_and_display_messages( SPCO.main_container, EE_STRIPE.error_msg );
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
				token: function(token) {
					// Use the token to create the charge with a server-side script.
					if ( token.error ) {
						SPCO.hide_notices();
						EE_STRIPE.error_msg = SPCO.generate_message_object( SPCO.tag_message_for_debugging( 'stripeResponseHandler error', token.error.message ), '', '' );
						SPCO.scroll_to_top_and_display_messages( SPCO.main_container, EE_STRIPE.error_msg );
						EE_STRIPE.submit_payment_button.text( transaction_args.card_error_message );
					} else {
						$('#ee-stripe-token').val( token.id );
						// Enable SPCO submit buttons.
						SPCO.enable_submit_buttons();
						EE_STRIPE.submit_payment_button.text( transaction_args.accepted_message ).css('background', '#1A89C8').prop('disabled', true);
					}
				}
			});
		},



		/**
		 * @function set_listener_for_payment_method_selector
		 */
		set_listener_for_payment_method_selector : function() {
			SPCO.main_container.on( 'click', '.spco-next-step-btn', function() {
				// Deactivate SPCO submit buttons to prevent submitting with no Stripe token.
				if ( EE_STRIPE.submit_payment_button.length > 0 && EE_STRIPE.submit_payment_button.val().length <= 0 ) {
					SPCO.disable_submit_buttons();
				}
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
			});
		}


	};

	EE_STRIPE.init();

});
