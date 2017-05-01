<?php
/*
  Plugin Name: Event Espresso - Stripe Gateway (EE 4.6.0+)
  Plugin URI: https://eventespresso.com
  Description: Stripe is an on-site payment method for Event Espresso for accepting credit and debit cards and is available to event organizers in many countries. An account with Stripe is required to accept payments.

  Version: 1.0.15.p

  Author: Event Espresso
  Author URI: https://eventespresso.com
  Copyright 2014 Event Espresso (email : support@eventespresso.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA
 */
define( 'EE_STRIPE_VERSION', '1.0.15.p' );
define( 'EE_STRIPE_PLUGIN_FILE',  __FILE__ );

function load_espresso_stripe() {
  if ( class_exists( 'EE_Addon' ) && extension_loaded('mbstring') && function_exists('json_decode') && function_exists('curl_init') ) {
    require_once ( plugin_dir_path( __FILE__ ) . 'EE_Stripe_Gateway.class.php' );
    EE_Stripe_Gateway::register_addon();
  }
}
add_action( 'AHEE__EE_System__load_espresso_addons', 'load_espresso_stripe' );



// Check for extensions needed by Stripe.
function espresso_stripe_check_for_components() {
  if ( ! extension_loaded('mbstring') || ! function_exists('json_decode') || ! function_exists('curl_init') ) {
    deactivate_plugins( plugin_basename( EE_STRIPE_PLUGIN_FILE ) );
    add_action( 'admin_notices', 'espresso_stripe_gw_disable_notice' );

    if ( isset( $_GET['activate'] ) ) {
      unset( $_GET['activate'] );
    }
  }
}
add_action( 'admin_init', 'espresso_stripe_check_for_components' );

function espresso_stripe_gw_disable_notice() {
    echo '<div class="error"><p>' . sprintf(__( 'The %s Stripe Gateway %s plugin was deactivated! This plugin requires the %s Multibyte String, JSON and CURL PHP %s extensions to be active on your server.' , 'event_espresso' ), '<strong>', '</strong>', '<strong>', '</strong>') . '</p></div>';
}


// End of file espresso_new_payment_method.php
// Location: wp-content/plugins/espresso-stripe-gateway/espresso-stripe.php
