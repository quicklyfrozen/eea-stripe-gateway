jQuery(document).ready(function($) {

    //var handler = StripeCheckout.configure({
    //    key: 'pk_test_6pRNASCoBOKtIshFeQd4XMUh',
    //    image: '/square-image.png',
    //    token: function(token) {
    //        // Use the token to create the charge with a server-side script.
    //        // You can access the token ID with `token.id`
    //    }
    //});
    //
    //document.getElementById('customButton').addEventListener('click', function(e) {
    //    // Open Checkout with further options
    //    handler.open({
    //        name: 'Demo Site',
    //        description: '2 widgets ($20.00)',
    //        amount: 2000
    //    });
    //    e.preventDefault();
    //});

    SPCO.main_container.on( 'click', '.stripe-button', function() {
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
            //$('#ee-stripe-token').val( response.id ) ;
            // and re-submit by triggering a click on the current step's submit button
            //SPCO.current_form_to_validate.find('.spco-next-step-btn').trigger('click');
        }
    }

});
