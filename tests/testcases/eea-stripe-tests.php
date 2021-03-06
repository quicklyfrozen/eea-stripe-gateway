<?php

/**
 * Test class for eea-stripe-gateway file
 *
 * @since         1.0.0
 * @package       EventEspresso/Stripe
 * @subpackage    tests
 */
class eea_stripe_tests extends EE_UnitTestCase
{

    /**
     * Tests the loading of the main file
     */
    function test_loading_main_class()
    {
        $this->assertEquals(10,has_action('AHEE__EE_System__load_espresso_addons', 'load_espresso_stripe'));
        $this->assertTrue(class_exists('EE_Stripe_Gateway'));
    }
}
