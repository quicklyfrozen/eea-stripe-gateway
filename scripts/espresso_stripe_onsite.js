jQuery(document).ready(function($) {

	var EE_STRIPE;

	/**
	 * @namespace EE_STRIPE
	 * @type {{
		 *     handler: object,
		 *     payment_method_selector: object,
		 *     payment_method_info_div: object,
		 *     stripe_button_div: object,
		 *     submit_button_id: string,
		 *     submit_payment_button: object,
		 *     stripe_token: object,
		 *     transaction_email: object,
		 *     transaction_total: object,
		 *     product_description: object,
		 *     stripe_response: object,
		 *     offset_from_top_modifier: number,
		 *     notification: string,
		 *     initialized: boolean,
	 	 *     txn_data: array
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
	 *     data_name: string,
	 *     data_image: string,
	 *     data_currency: string,
	 *     data_cc_number: number,
	 *     data_exp_month: number,
	 *     data_exp_year: number,
	 *     data_cvc: number,
	 *     data_panel_label: string,
	 *     accepted_message: string,
	 *     card_error_message: string,
	 *     no_SPCO_error: string,
	 *     no_StripeCheckout_error: string
	 * }}
	 */
	EE_STRIPE = {

		handler : {},
		submit_button_id : '#ee-stripe-button-btn',
		payment_method_selector : {},
		payment_method_info_div : {},
		stripe_button_div : {},
		submit_payment_button : {},
		token_string : {},
		transaction_email : {},
		transaction_total : {},
		product_description : {},
		stripe_response : {},
		offset_from_top_modifier : -400,
		notification : '',
		initialized : false,
		txn_data : {},



		/**
		 * @function initialize
		 */
		initialize : function() {

			EE_STRIPE.initialize_objects();
			EE_STRIPE.disable_SPCO_submit_buttons_if_Stripe_selected();

			// has the Stripe gateway has been selected ? or already initialized?
			if ( ! EE_STRIPE.submit_payment_button.length || EE_STRIPE.initialized ) {
				//SPCO.console_log( 'initialize', 'already initialized!', true );
				return;
			}
			// ensure that the SPCO js class is loaded
			if ( typeof SPCO === 'undefined' ) {
				//console.log( JSON.JSON.stringify( 'initialize: ' + 'no SPCO !!!', null, 4 ) );
				EE_STRIPE.hide_stripe();
				EE_STRIPE.display_error( transaction_args.no_SPCO_error );
				return;
			}
			// ensure that the StripeCheckout js class is loaded
			if ( typeof StripeCheckout === 'undefined' ) {
				//SPCO.console_log( 'initialize', 'no StripeCheckout!!', true );
				SPCO.offset_from_top_modifier = EE_STRIPE.offset_from_top_modifier;
				EE_STRIPE.notification = SPCO.generate_message_object( '', SPCO.tag_message_for_debugging( 'EE_STRIPE.init() error', transaction_args.no_StripeCheckout_error ), '' );
				SPCO.scroll_to_top_and_display_messages( EE_STRIPE.stripe_button_div, EE_STRIPE.notification, true );
				return;
			}
			EE_STRIPE.set_up_handler();
			EE_STRIPE.set_listener_for_payment_method_selector();
			EE_STRIPE.set_listener_for_submit_payment_button();
			EE_STRIPE.set_listener_for_leave_page();
			EE_STRIPE.get_transaction_data();
			//alert('EE_STRIPE.initialized');
			EE_STRIPE.initialized = true;
		},



		/**
		 * @function set_up_handler
		 */
		initialize_objects : function() {
			//SPCO.console_log( 'initialize', 'initialize_objects', true );
			EE_STRIPE.submit_payment_button = $( EE_STRIPE.submit_button_id );
			EE_STRIPE.payment_method_selector = $('#ee-available-payment-method-inputs-stripe_onsite-lbl');
			EE_STRIPE.payment_method_info_div = $('#spco-payment-method-info-stripe_onsite');
			EE_STRIPE.stripe_button_div = $('#ee-stripe-button-dv');
			EE_STRIPE.token_string = $('#ee-stripe-token');
			EE_STRIPE.transaction_email = $('#ee-stripe-transaction-email');
			EE_STRIPE.transaction_total = $('#ee-stripe-transaction-total');
			EE_STRIPE.product_description = $('#ee-stripe-prod-description');
			EE_STRIPE.stripe_response = $('#ee-stripe-response-pg');
		},


		/**
		 * @function get_transaction_data
		 */
		get_transaction_data : function() {
			var req_data = {};
			req_data.step = 'payment_options';
			req_data.action = 'get_transaction_details_for_gateways';
			req_data.selected_method_of_payment = 'stripe_onsite';
			req_data.generate_reg_form = false;
			req_data.process_form_submission = false;
			req_data.noheader = true;
			req_data.ee_front_ajax = true;
			req_data.EESID = eei18n.EESID;
			req_data.revisit = eei18n.revisit;
			req_data.e_reg_url_link = eei18n.e_reg_url_link;

			$.ajax( {
				type : "POST",
				url : eei18n.ajax_url,
				data : req_data,
				dataType : "json",

				beforeSend : function() {
					SPCO.do_before_sending_ajax();
					EE_STRIPE.submit_payment_button.prop('disabled', true ).addClass('spco-disabled-submit-btn');
				},
				success : function( response ) {
					EE_STRIPE.txn_data = response;
					EE_STRIPE.submit_payment_button.prop('disabled', false ).removeClass('spco-disabled-submit-btn');
					SPCO.end_ajax();
				},
				error : function() {
					SPCO.end_ajax();
					return SPCO.submit_reg_form_server_error();
				}
			});
		},



		/**
		 * @function set_up_handler
		 */
		set_up_handler : function() {
			//SPCO.console_log( 'initialize', 'set_up_handler', true );
			EE_STRIPE.handler = StripeCheckout.configure({
				key: transaction_args.data_key,
				token: function( stripe_token ) {
					//SPCO.console_log_object( 'stripe_token', stripe_token, 0 );
					// Use the token to create the charge with a server-side script.
					if ( typeof stripe_token.error !== 'undefined' && stripe_token.error ) {
						EE_STRIPE.checkout_error( stripe_token );
					} else {
						EE_STRIPE.checkout_success( stripe_token );
					}
					if ( typeof stripe_token.card !== 'undefined' && stripe_token.card.name  !== 'undefined' ) {
						EE_STRIPE.save_card_details( stripe_token.card );
					}
				}
			});
		},



		/**
		 * @function checkout_success
		 * @param  {object} stripe_token
		 */
		checkout_success : function( stripe_token ) {
			//SPCO.console_log( 'initialize', 'checkout_success', true );
			// Enable SPCO submit buttons.
			SPCO.enable_submit_buttons();
			if ( typeof stripe_token.used !== 'undefined' && ! stripe_token.used ) {
				//SPCO.console_log( 'checkout_success > EE_STRIPE.token_string.attr(name)', EE_STRIPE.token_string.attr('name'), true );
				EE_STRIPE.submit_payment_button.prop('disabled', true ).addClass('spco-disabled-submit-btn');
				EE_STRIPE.token_string.val( stripe_token.id );
				//SPCO.console_log( 'checkout_success > stripe_token.id', stripe_token.id, true );
				SPCO.offset_from_top_modifier = EE_STRIPE.offset_from_top_modifier;
				EE_STRIPE.notification =SPCO.generate_message_object( transaction_args.accepted_message, '', '' );
				SPCO.scroll_to_top_and_display_messages( EE_STRIPE.stripe_button_div, EE_STRIPE.notification, true );
			}
		},



		/**
		 * @function checkout_error
		 * @param  {object} stripe_token
		 */
		checkout_error : function( stripe_token ) {
			SPCO.hide_notices();
			if ( typeof stripe_token.error !== 'undefined' ) {
				SPCO.offset_from_top_modifier = EE_STRIPE.offset_from_top_modifier;
				EE_STRIPE.notification = SPCO.generate_message_object( '', SPCO.tag_message_for_debugging( 'stripeResponseHandler error', stripe_token.error.message ), '' );
				SPCO.scroll_to_top_and_display_messages( EE_STRIPE.stripe_button_div, EE_STRIPE.notification, true );
				EE_STRIPE.stripe_response.text( transaction_args.card_error_message ).addClass( 'important-notice error' ).show();
			}
		},



		/**
		 * @function save_email_address
		 * @param  {object} card_info
		 */
		save_card_details : function( card_info ) {

			//SPCO.console_log_object( 'card_info', card_info, 0 );

			var data={};
			data.action = 'save_payer_details';
			data.step = 'payment_options';
			data.e_reg_url_link = eei18n.e_reg_url_link;
			data.revisit = eei18n.revisit;
			data.EESID = eei18n.EESID;
			data.generate_reg_form = true;
			data.process_form_submission = false;
			data.noheader = true;
			data.ee_front_ajax = true;
			data.email = card_info.name;
			// attempt to capture address info
			if ( typeof card_info.address_line1 !== 'undefined' && card_info.address_line1 !== '' ) {
				data.ATT_address = card_info.address_line1;
			}
			if ( typeof card_info.address_line2 !== 'undefined' && card_info.address_line2 !== '' ) {
				data.ATT_address2 = card_info.address_line2;
			}
			if ( typeof card_info.address_city !== 'undefined' && card_info.address_city !== '' ) {
				data.ATT_city = card_info.address_city;
			}
			if ( typeof card_info.address_state !== 'undefined' && card_info.address_state !== '' ) {
				data.ATT_state = card_info.address_state;
			}
			if ( typeof card_info.address_zip !== 'undefined' && card_info.address_zip !== '' ) {
				data.ATT_zip = card_info.address_zip;
			}
			if ( typeof card_info.address_country !== 'undefined' && card_info.address_country !== '' ) {
				data.ATT_country = card_info.address_country;
			}

			$.ajax({
				type: 'POST',
				url: eei18n.ajax_url,
				data: data,
				dataType: "json",
				success: function( response ) {
					if ( typeof response.errors !== 'undefined' && response.errors !== '' ) {
						SPCO.offset_from_top_modifier = EE_STRIPE.offset_from_top_modifier;
						EE_STRIPE.notification = SPCO.generate_message_object( '', SPCO.tag_message_for_debugging( 'Stripe save_card_details error', response.errors ), '' );
						SPCO.scroll_to_top_and_display_messages( EE_STRIPE.stripe_button_div, EE_STRIPE.notification, true );
					}
				}
			});

		},



		/**
		 * @function set_listener_for_payment_method_selector
		 */
		set_listener_for_payment_method_selector : function() {
			//SPCO.main_container.on( 'click', '.spco-payment-method', function() {
			SPCO.main_container.on( 'click', '.spco-next-step-btn', function() {
				EE_STRIPE.disable_SPCO_submit_buttons_if_Stripe_selected();
			});
		},



		/**
		 * @function disable_SPCO_submit_buttons_if_Stripe_selected
		 * Deactivate SPCO submit buttons to prevent submitting with no Stripe token.
		 */
		disable_SPCO_submit_buttons_if_Stripe_selected : function() {
			if ( EE_STRIPE.submit_payment_button.length > 0 && EE_STRIPE.submit_payment_button.val().length <= 0 ) {
				SPCO.allow_enable_submit_buttons = false;
				SPCO.disable_submit_buttons();
			}
		},



		/**
		 * @function set_listener_for_submit_payment_button
		 */
		set_listener_for_submit_payment_button : function() {
			//SPCO.console_log( 'initialize', 'set_listener_for_submit_payment_button', true );
			SPCO.main_container.on( 'click', EE_STRIPE.submit_button_id, function(e) {
				e.preventDefault();
				//e.stopPropagation();
				SPCO.hide_notices();

				var amount = EE_STRIPE.txn_data['payment_amount'];
				amount = amount.toString().replace('.' , '').replace(',' , '');
				// Open Checkout with further options that were set in EE_PMT_Stripe_Onsite::enqueue_stripe_payment_scripts()
				EE_STRIPE.handler.open({
					name: transaction_args.data_name,
					image: transaction_args.data_image,
					description: EE_STRIPE.product_description.val(),
					amount: amount,	//EE_STRIPE.transaction_total.val(),
					email: EE_STRIPE.transaction_email.val(),
					currency: transaction_args.data_currency,
					panelLabel: transaction_args.data_panel_label
				});
			});
		},



		/**
		 * @function hide_stripe
		 */
		hide_stripe : function() {
			EE_STRIPE.payment_method_selector.hide();
			EE_STRIPE.payment_method_info_div.hide();
		},



		/**
		 * @function set_listener_for_leave_page
		 * Close Checkout on page navigation
		 */
		set_listener_for_leave_page : function() {
			$(window).on( 'popstate', function() {
				EE_STRIPE.handler.close();
			});
		},



		/**
		 * @function display_error
		 * @param  {string} msg
		 */
		display_error : function( msg ) {
			// center notices on screen
			$('#espresso-ajax-notices').eeCenter( 'fixed' );
			// target parent container
			var espresso_ajax_msg = $('#espresso-ajax-notices-error');
			//  actual message container
			espresso_ajax_msg.children('.espresso-notices-msg').html( msg );
			// bye bye spinner
			$('#espresso-ajax-loading').fadeOut('fast');
			// display message
			espresso_ajax_msg.removeClass('hidden').show().delay( 10000 ).fadeOut();
		}



	};
	// end of EE_STRIPE object



	// initialize Stripe Checkout if the SPCO reg step changes to "payment_options"
	SPCO.main_container.on( 'spco_display_step', function( event, step_to_show ) {
		if ( typeof step_to_show !== 'undefined' && step_to_show === 'payment_options' ) {
			EE_STRIPE.initialize();
		}
	});



	// also initialize Stripe Checkout if the selected method of payment changes
	SPCO.main_container.on( 'spco_switch_payment_methods', function( event, payment_method ) {
		//SPCO.console_log( 'payment_method', payment_method, false );
		if ( typeof payment_method !== 'undefined' && payment_method === 'stripe_onsite' ) {
			EE_STRIPE.initialize();
		}
	});



	// also initialize Stripe Checkout if the page just happens to load on the "payment_options" step with Stripe already selected!
	EE_STRIPE.initialize();


});
