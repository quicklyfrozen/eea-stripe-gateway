<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' ) ) { exit('No direct script access allowed'); } ?>

    <div class="sandbox-panel">

		<h6 class="important-notice"><?php _e('Debug Mode is turned ON. Payments will NOT be processed', 'event_espresso'); ?></h6>

        <p class="test-credit-cards-info-pg">
            <strong><?php _e('Credit Card Numbers Used for Testing', 'event_espresso'); ?></strong><br/>
            <span class="small-text"><?php _e('Use the following credit card information for testing:', 'event_espresso'); ?></span>
        </p>

        <div class="tbl-wrap">
            <table id="stripe-test-credit-cards" class="test-credit-card-data-tbl">
                <thead>
                    <tr>
                        <td><?php _e('Card Number', 'event_espresso'); ?></td>
                        <td><?php _e('CVV/CVV2', 'event_espresso'); ?></td>
                        <td><?php _e('Exp Date (DD/MM)', 'event_espresso'); ?></td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>4242424242424242</td>
                        <td>248</td>
                        <td><?php _e('Any date greater than today', 'event_espresso'); ?></td>
                    </tr>

                </tbody>
            </table>
        </div>

		<h4><?php _e('How do I test specific error codes?', 'event_espresso'); ?></h4>
		<ul>
			<li><?php _e('card_declined: Use this special card number', 'event_espresso'); ?> - 4000000000000002</li>
			<li><?php _e('incorrect_number: Use a number that fails the Luhn check, e.g.', 'event_espresso'); ?> 4242424242424241</li>
			<li><?php _e('invalid_expiry_month: Use an invalid month e.g.', 'event_espresso'); ?> 13</li>
			<li><?php _e('invalid_expiry_year: Use a year in the past e.g.', 'event_espresso'); ?> 1970</li>
			<li><?php _e('invalid_cvc: Use a two digit number e.g.', 'event_espresso'); ?> 99</li>
		</ul>
    </div>
