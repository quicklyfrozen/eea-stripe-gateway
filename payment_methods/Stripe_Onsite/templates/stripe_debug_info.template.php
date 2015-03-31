<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' ) ) { exit('No direct script access allowed'); } ?>

    <div id="stripe-sandbox-panel" class="sandbox-panel">

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
                        <td><?php _e('Exp Date (MM/YY)', 'event_espresso'); ?></td>
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
            <li><strong>4000000000000341</strong> - <?php _e('charge fail; Attaching this card will succeed, but attempts to charge the customer will fail.', 'event_espresso'); ?></li>
			<li><strong>4000000000000002</strong> - <?php _e('card_declined; Use this special card number.', 'event_espresso'); ?></li>
			<li><strong>4242424242424241</strong> - <?php _e('incorrect_number; Use a number that fails the Luhn check, e.g.', 'event_espresso'); ?></li>
			<li><strong>13</strong> - <?php _e('invalid_expiry_month; Use an invalid month e.g.', 'event_espresso'); ?></li>
			<li><strong>1970</strong> - <?php _e('invalid_expiry_year; Use a year in the past e.g.', 'event_espresso'); ?></li>
			<li><strong>99</strong> - <?php _e('invalid_cvc; Use a two digit number e.g.', 'event_espresso'); ?></li>
		</ul>
    </div>
