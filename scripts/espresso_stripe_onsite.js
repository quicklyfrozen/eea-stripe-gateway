jQuery(document).ready(function($) {
   /* SPCO.main_container.on( 'click', '#spco-go-to-step-finalize_registration-submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        // Disable the submit button to prevent repeated clicks
        SPCO.disable_submit_buttons();
        var $form = $('#ee-Stripe-billing-form');
        Stripe.setPublishableKey('YOUR_PUBLISHABLE_KEY');
        Stripe.card.createToken({
                number: $form.find('.card-number').val(),
                cvc: $form.find('.card-cvc').val(),
                exp_month: $form.find('.card-expiry-month').val(),
                exp_year: $form.find('.card-expiry-year').val()
            },
            stripeResponseHandler
        );
	});


    function stripeResponseHandler( status, response ) {
        alert('stripeResponseHandler');

        if ( response.error ) {
            // Show the errors on the form.
            SPCO.hide_notices();
            var msg = SPCO.generate_message_object( '', SPCO.tag_message_for_debugging( 'stripeResponseHandler error', response.error.message ), '' );
            SPCO.scroll_to_top_and_display_messages( SPCO.main_container, msg );
            SPCO.enable_submit_buttons();
        } else {
            alert('NO errors!');

            // set token value in hidden input so it gets submitted to the server
            $('#ee-stripe-token').val( response.id ) ;
            // and re-submit by triggering a click on the current step's submit button
            //SPCO.current_form_to_validate.find('.spco-next-step-btn').trigger('click');
        }
    }*/
    
    var defaultButtonColor = '';

    SPCO.main_container.on( 'click', '#ee-available-payment-method-inputs-stripe_onsite', function(e) {
    	// Deactivate SPCO submit buttons to prevent submitting with no Stripe token.
		SPCO.disable_submit_buttons();

		/*defaultButtonColor = $('.spco-next-step-btn').style.backgroundColor;
		$('.spco-next-step-btn').each( function() {
			$(this).style.background = '#A0A0A0';
		});*/
    });

	var handler = StripeCheckout.configure({
		key: transaction_args.data_key,
		image: transaction_args.data_image,
		token: function(token) {
			// Use the token to create the charge with a server-side script.
			$('#ee-stripe-token').val( token.id );
		}
	});

	SPCO.main_container.on( 'click', '#custom-stripe-button', function(e) {
		// Open Checkout with further options.
		handler.open({
			name: transaction_args.data_name,
			amount: $('#ee-stripe-transaction-total').val(),
			description: $('#ee-stripe-prod-description').val()
		});
		// Enable SPCO submit buttons.
		SPCO.enable_submit_buttons();
		/*$('.spco-next-step-btn').each( function() {
			$(this).style.background = backgroundColor;
		});
		$('#custom-stripe-button').disabled = true;
		$('#custom-stripe-button').val('Accepted. Click "Finalize Registration"');*/
		e.preventDefault();
	});

});
