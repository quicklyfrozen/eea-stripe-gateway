jQuery(document).ready(function($) {

	$('#ee-spco-payment_options-reg-step-form').submit( function() {
		var $form = $(this);

		// Disable the submit button to prevent repeated clicks
		$form.find('button').prop('disabled', true);
		Stripe.card.createToken($form, stripeResponseHandler);

		// Prevent the form from submitting with the default action
		return false;
	});

});

function stripeResponseHandler( status, response ) {
	var $form = jQuery("#ee-spco-payment_options-reg-step-form");

	if ( response.error ) {
		// Show the errors on the form.
		$form.find('.payment-errors').text(response.error.message);
		$form.find('button').prop('disabled', false);
	} else {
		// Token contains id, last4, and card type.
		var token = response.id;
		// Insert the token into the form so it gets submitted to the server
		$form.append($('<input type="hidden" name="ee-stripe-token" />').val(token));
		$form.append('<input type="hidden" name="stripe-token" value="' + token + '" />');
		// and re-submit.
		$form.submit();
	}
}