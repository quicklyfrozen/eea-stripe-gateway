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
                        <td><?php _e('Card Status', 'event_espreso'); ?></td>
                        <td><?php _e('Error message', 'event_espreso'); ?></td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>4000000000000002</td>
                        <td>123</td>
                        <td>05/18</td>
                        <td>Good card</td>
                        <td>OK</td>
                    </tr>
                    
                </tbody>
            </table>
        </div><br/>
    </div>
<?php }
