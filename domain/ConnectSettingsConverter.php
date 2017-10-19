<?php

namespace EventEspresso\Stripe\Domain;
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
                    'PMD_type'            => 'Stripe_Onsite',
                    'Extra_Meta.EXM_type' => 'Payment_Method',
                    'Extra_Meta.EXM_key'  => 'access_token',
                )
            )
        );
        foreach ($stripe_pm_with_old_connect_data as $payment_method_obj) {
            /**
             * @var $payment_method_obj EE_Payment_Method
             */
            $this->convertOldStripeConnectSettings($payment_method_obj);
        }
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
        //keys are old keys, values are their new keys
        $setting_mapping = array(
            'access_token' => Domain::META_KEY_SECRET_KEY,
            'stripe_publishable_key' => Domain::META_KEY_PUBLISHABLE_KEY,
        );
        foreach ($setting_mapping as $old_setting => $new_setting) {
            $payment_method->update_extra_meta(
                $new_setting,
                $payment_method->get_extra_meta($old_setting, true)
            );
            $payment_method->delete_extra_meta($old_setting);
        }
    }
}
// End of file ConnectSettingsConverter.php
// Location: EventEspresso\Stripe\Domain/ConnectSettingsConverter.php
