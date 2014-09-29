jQuery(document).ready(function($) {

    SPCO.main_container.on( 'click', '.stripe-button', function() {
        e.preventDefault();
        e.stopPropagation();
        // Disable the submit button to prevent repeated clicks
        SPCO.disable_submit_buttons();
        Stripe.card.createToken( SPCO.current_form_to_validate, stripeResponseHandler );
	});

});

function stripeResponseHandler( status, response ) {

	var $form = SPCO.current_form_to_validate;

	if ( response.error ) {
		// Show the errors on the form.
        SPCO.current_form_to_validate.find('.payment-errors').text( response.error.message );
        SPCO.current_form_to_validate.find('button').prop('disabled', false);
	} else {
		// set token value in hidden input so it gets submitted to the server
        $('#ee-stripe-token').val( response.id ) ;
		// and re-submit.
        SPCO.current_form_to_validate.submit();
	}
}