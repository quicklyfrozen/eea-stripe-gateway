<?php
/**
 * Bootstrap for eea-strip tests
 */

use EETests\bootstrap\AddonLoader;

$core_tests_dir = dirname(dirname(dirname(__FILE__))) . '/event-espresso-core/tests/';
require $core_tests_dir . 'includes/CoreLoader.php';
require $core_tests_dir . 'includes/AddonLoader.php';

define('EEA_STRIPE_PLUGIN_DIR', dirname(dirname(__FILE__)) . '/');
define('EEA_STRIPE_TESTS_DIR', EEA_STRIPE_PLUGIN_DIR . 'tests');


$addon_loader = new AddonLoader(
    EEA_STRIPE_TESTS_DIR,
    EEA_STRIPE_PLUGIN_DIR,
    'eea-stripe-gateway.php'
);
$addon_loader->init();
