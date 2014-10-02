jQuery(document).ready(function($) {

    SPCO.main_container.on( 'click', '#ee-available-payment-method-inputs-stripe_onsite', function(e) {
    	// Deactivate SPCO submit buttons to prevent submitting with no Stripe token.
		SPCO.disable_submit_buttons();
    });

	var handler = StripeCheckout.configure({
		key: transaction_args.data_key,
		image: transaction_args.data_image,
		token: function(token) {
			// Use the token to create the charge with a server-side script.
			if ( token.error ) {
				SPCO.hide_notices();
            	var msg = SPCO.generate_message_object( '', SPCO.tag_message_for_debugging( 'stripeResponseHandler error', token.error.message ), '' );
            	SPCO.scroll_to_top_and_display_messages( SPCO.main_container, msg );
				$('#custom-stripe-button').text('Card Error ! Try again.');
			} else {
				$('#ee-stripe-token').val( token.id );
				// Enable SPCO submit buttons.
				SPCO.enable_submit_buttons();
				$('#custom-stripe-button').text('Card Accepted !');
				$('#custom-stripe-button').css('background', '#1A89C8');
				$('#custom-stripe-button').attr('disabled', 'disabled');
			}
		}
	});

	SPCO.main_container.on( 'click', '#custom-stripe-button', function(e) {
		// Open Checkout with further options.
		handler.open({
			name: transaction_args.data_name,
			amount: $('#ee-stripe-transaction-total').val(),
			description: $('#ee-stripe-prod-description').val()
		});
		e.preventDefault();
	});

});
