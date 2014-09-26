<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' ) ) { exit('No direct script access allowed'); }
/**
 * ------------------------------------------------------------------------
 *
 * stripe_settings_before_form
 *
 * @package			Event Espresso
 * @subpackage		espresso-stripe-gateway
 *
 * ------------------------------------------------------------------------
 */

/**
 * @var $form_section EE_Billing_Info_Form
 */
if ( $form_section->payment_method()->debug_mode() ) { ?>
    <div class="sandbox-panel">
        <h2 class="section-title"><?php _e('Stripe Sandbox Mode', 'event_espreso'); ?></h2>
        <h3 style="color:#ff0000;"><?php _e('Debug Mode Is Turned On. Payments will not be processed', 'event_espresso'); ?></h3>

        <p class="test-credit-cards-info-pg">
            <strong><?php _e('Credit Card Numbers Used for Testing', 'event_espreso'); ?></strong><br/>
            <span class="small-text"><?php _e('Use the following credit card information for testing:', 'event_espreso'); ?></span>
        </p>

        <div class="tbl-wrap">
            <table id="stripe-test-credit-cards" class="test-credit-card-data-tbl">
                <thead>
                    <tr>
                        <td><?php _e('Card Number', 'event_espreso'); ?></td>
                        <td><?php _e('CVV/CVV2', 'event_espreso'); ?></td>
                        <td><?php _e('Exp Date (DD/MM)', 'event_espreso'); ?></td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>4242424242424242</td>
                        <td>248</td>
                        <td><?php _e('Any greater than today', 'event_espreso'); ?></td>
                    </tr>

                </tbody>
            </table>
        </div>
		<br/>
		<h4><?php _e('How do I test specific error codes?', 'event_espreso'); ?></h4>
		<ul>
			<li><?php _e('card_declined: Use this special card number', 'event_espreso'); ?> - 4000000000000002</li>
			<li><?php _e('incorrect_number: Use a number that fails the Luhn check, e.g.', 'event_espreso'); ?> 4242424242424241</li>
			<li><?php _e('invalid_expiry_month: Use an invalid month e.g.', 'event_espreso'); ?> 13</li>
			<li><?php _e('invalid_expiry_year: Use a year in the past e.g.', 'event_espreso'); ?> 1970</li>
			<li><?php _e('invalid_cvc: Use a two digit number e.g.', 'event_espreso'); ?> 99</li>
		</ul>
    </div>
<?php }
