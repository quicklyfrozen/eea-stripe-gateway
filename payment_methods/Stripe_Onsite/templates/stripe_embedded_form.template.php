<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' ) ) { exit('No direct script access allowed'); }
/**
 * ------------------------------------------------------------------------
 *
 * stripe_embedded_form
 *
 * @package			Event Espresso
 * @subpackage		espresso-stripe-gateway
 *
 * ------------------------------------------------------------------------
 */
?>
<!-- Stripe JS Button -->
<script src="https://checkout.stripe.com/checkout.js"></script>
<button id="custom-stripe-button"><?php _e( 'PAY WITH CARD', 'event_espresso' );?></button>