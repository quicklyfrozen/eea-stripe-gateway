<?php

namespace EventEspresso\Stripe\domain;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct script access allowed');



/**
 * Class ConnectSettingsConverterTest
 * Description
 *
 * @package        Event Espresso
 * @author         Mike Nelson
 * @since          $VID:$
 */
class ConnectSettingsConverterTest extends \EE_UnitTestCase
{
    public function testCheckForOldStripeConnectSettingsForOldStripe()
    {
        $secret_key = '123qwe';
        $oldStripe = $this->new_model_obj_with_dependencies(
            'Payment_Method',
            array(
                'PMD_type' => 'Stripe_Onsite',
                'PMD_name' => 'Stripe Standalone'
            )
        );
        $this->addExtraMetas(
            array(
                'stripe_secret_key' => $secret_key,
                
            ),
            $oldStripe
        );
        $converter = new ConnectSettingsConverter();
        $converter->checkForOldStripeConnectSettings();
        //make sure the old setting is gone
        $this->assertNull(
            $oldStripe->get_extra_meta(
                'stripe_secret_key',
                true,
                null
            )
        );
        $this->assertEquals(
            $secret_key,
            $oldStripe->get_extra_meta(
                Domain::META_KEY_SECRET_KEY,
                true
            )
        );
    }
    
    public function testCheckForOldStripeConnectSettingsForOldStripeConnect()
    {
        $access_token= '123qwe';
        $oldStripe = $this->new_model_obj_with_dependencies(
            'Payment_Method',
            array(
                'PMD_type' => 'Stripe_Onsite',
                'PMD_name' => 'Old Stripe Connect'
            )
        );
        $this->addExtraMetas(
            array(
                'access_token' => $access_token
            ),
            $oldStripe
        );
        $converter = new ConnectSettingsConverter();
        $converter->checkForOldStripeConnectSettings();
        $this->assertNull(
            $oldStripe->get_extra_meta(
                'access_token',
                true,
                null
            )
        );
        $this->assertEquals(
            $access_token,
            $oldStripe->get_extra_meta(
                Domain::META_KEY_SECRET_KEY,
                true
            )
        );
    }
    protected function addExtraMetas($keys_and_values, \EE_Payment_Method $payment_method)
    {
        foreach( $keys_and_values as $key => $value) {
            $payment_method->update_extra_meta($key, $value);
        }
    }


}
// End of file ConnectSettingsConverterTest.php
// Location: blergh/ConnectSettingsConverterTest.php