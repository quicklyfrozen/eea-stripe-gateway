<?php

namespace EventEspresso\Stripe\domain;

use EE_Payment_Method;
use EEM_Payment_Method;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct script access allowed');



/**
 * Class ConnectSettingsConverter
 * Takes care of converting old stripe conenct settings (when it was an add-on)
 * to the new settings.
 * This could have been done in a DMS but it's so short we don't want to put
 * the site in maintenance mode just for this.
 *
 * @package        Event Espresso
 * @author         Mike Nelson
 * @since          $VID:$
 */
class ConnectSettingsConverter
{

    /**
     * Checks for Stripe payment methods in the database that use the old Stripe Connect settings.
     * If nothing needs to be converted, this just takes the time to run the query
     *
     * @throws \EE_Error
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \InvalidArgumentException
     */
    public function checkForOldStripeConnectSettings()
    {
        $stripe_pm_with_old_connect_data = EEM_Payment_Method::instance()->get_all(
            array(
                array(
                    'PMD_type'                       => 'Stripe_Onsite',
                    'Extra_Meta.EXM_type'            => 'Payment_Method',
                    'Extra_Meta.EXM_key'             => array(
                        'IN',
                        array(
                            'access_token',
                            'stripe_secret_key',
                        ),
                    ),
                    'Extra_Meta.EXM_value*not-blank' => array('!=', ''),
                    'Extra_Meta.EXM_value*not-null'  => array('IS_NOT_NULL'),
                ),
            )
        );
        foreach ($stripe_pm_with_old_connect_data as $payment_method_obj) {
            if ($payment_method_obj->get_extra_meta('access_token', true)) {
                $this->convertOldStripeConnectSettings($payment_method_obj);
            } elseif ($payment_method_obj->get_extra_meta('stripe_secret_key', true)) {
                $this->convertOldStripeStandaloneSettings($payment_method_obj);
            }
        }
    }



    /**
     * Converts the old key "stripe_secret_key" (what we used to use for the secret key)
     * to "secret_key". Just Mike changing his mind about what name to use!
     *
     * @param EE_Payment_Method $payment_method
     */
    public function convertOldStripeStandaloneSettings(EE_Payment_Method $payment_method)
    {
        $this->convertSettings(
            array(
                'stripe_secret_key' => Domain::META_KEY_SECRET_KEY,
            ),
            $payment_method
        );
    }



    /**
     * Moves the stripe configuration data from the options we used to use to the new ones
     *
     * @param EE_Payment_Method $payment_method
     * @return void
     * @throws \EE_Error
     */
    public function convertOldStripeConnectSettings(EE_Payment_Method $payment_method)
    {
        $this->convertSettings(
            array(
                'access_token'            => Domain::META_KEY_SECRET_KEY,
                'connect_publishable_key' => Domain::META_KEY_PUBLISHABLE_KEY,
                'stripe_secret_key'       => null,
            ),
            $payment_method
        );
        //client_id is a new one normally retrieved from the EE middleman server
        //before that, it was just the hardcoded eventmsart client ID
        if ($payment_method->debug_mode() && defined('EE_SAAS_STRIPE_CONNECT_TEST_CLIENT_ID')) {
            $eventsmart_client_id = EE_SAAS_STRIPE_CONNECT_TEST_CLIENT_ID;
        } elseif (! $payment_method->debug_mode() && defined('EE_SAAS_STRIPE_CONNECT_CLIENT_ID')) {
            $eventsmart_client_id = EE_SAAS_STRIPE_CONNECT_CLIENT_ID;
        } else {
            //so, you were using our unreleased plugin for Stripe Connect but don't have the connection
            //defined anywhere? That shouldn't happen
            \EE_Error::add_error(
                esc_html__(
                // @codingStandardsIgnoreStart
                    'We could not convert your old Stripe Connect data to its new format because you don\'t have the necessary constants defined.',
                    // @codingStandardsIgnoreEnd
                    'event_espresso'
                ),
                __FILE__,
                __FUNCTION__,
                __LINE__
            );
            $eventsmart_client_id = '';
        }
        $payment_method->update_extra_meta(
            Domain::META_KEY_CLIENT_ID,
            $eventsmart_client_id
        );
    }



    /**
     * Using the $settings_mapping, moves the old extra meta keys to their new keys for the payment method
     *
     * @param array $settings_mapping keys are the old extra meta key, and values are the new key.
     *                                If the value is null, it means the key should simply be removed.
     * @throws \EE_Error
     */
    protected function convertSettings($settings_mapping, EE_Payment_Method $payment_method)
    {
        foreach ($settings_mapping as $old_setting => $new_setting) {
            if ($new_setting) {
                $payment_method->update_extra_meta(
                    $new_setting,
                    $payment_method->get_extra_meta($old_setting, true)
                );
            }
            $payment_method->delete_extra_meta($old_setting);
        }
    }
}
// End of file ConnectSettingsConverter.php
// Location: EventEspresso\Stripe\Domain/ConnectSettingsConverter.php
