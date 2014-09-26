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

<div id="ee-stripe-billing-form-input-dv" class="ee-billing-qstn-input-dv" style="text-align: center;">
    <script
        src="https://checkout.stripe.com/checkout.js" class="stripe-button"
        data-key="<?php echo $data_key; ?>"
        data-amount="<?php echo $grand_total; ?>"
        data-name="<?php echo $data_name; ?>"
        data-description="<?php echo $data_description; ?>"
        data-image="<?php echo $data_image; ?>"
        data-number="<?php echo $cc_number; // doesn't work  :( ?>"
        data-exp_month="<?php echo $exp_month; // doesn't work  :(  ?>"
        data-exp_year="<?php echo $exp_year; // doesn't work  :(  ?>"
        data-cvc="<?php echo $cvc; // doesn't work  :(  ?>"
	></script>
</div>
