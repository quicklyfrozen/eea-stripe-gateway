<?php
namespace EventEspresso\Stripe\domain;

use EventEspresso\core\domain\DomainBase;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class Domain
 * domain data regarding the Stripe Add-on
 *
 * @package       Event Espresso
 * @author        Mike Nelson
 * @since         1.2.0
 */
class Domain
{

    /**
     * Name of Extra Meta that stores the Stripe secret key
     * used in API calls.
     * With Stripe Connect, the access token is put in here.
     */
    const META_KEY_SECRET_KEY = 'secret_key';

    /**
     * Name of the Extra Meta that stores the Stripe publishable
     * key is passed to the Javascript in order to setup the iFrame.
     */
    const META_KEY_PUBLISHABLE_KEY = 'publishable_key';

    /**
     * Name of the Extra Meta that stores the Refresh Token retrieved
     * when using Stripe Connect. Currently, we don't make use of this
     * in order to renew the access token because the access token
     * doesn't automatically expire.
     */
    const META_KEY_REFRESH_TOKEN = 'refresh_token';

    /**
     * Name of the Extra Meta that stores the Event Espresso Stripe Account's
     * client id. We could hardocde this into this plugin, but it's possible
     * we may want to change it someday. So instead, we hardcode it into
     * connect.eventespresso.com, and pass it to this plugin who stores it.
     * It's currently only used while deauthorizing EE from using a Stripe account
     */
    const META_KEY_CLIENT_ID = 'client_id';

    /**
     * Name of the Extra Meta that stores whether the credentials were
     * for the Stripe sandbox or live mode. Just used with Stripe Connect
     */
    const META_KEY_LIVE_MODE = 'livemode';

    /*
     * Name of the Extra Meta that stores the ID of the Stripe Account that
     * authorized the EE Stripe Connect client to access Stripe on its behalf.
     * Only used with Stripe Connect
     */
    const META_KEY_STRIPE_USER_ID = 'stripe_user_id';

    /**
     * Name of the Extra Meta that stores whether or not the above credentials
     * were provided by Stripe Connect or directly entered into this plugin.
     * If it doesn't exist, they were manually entered.
     */
    const META_KEY_USING_STRIPE_CONNECT = 'using_stripe_connect';


}
// End of file Domain.php
// Location: domain/Domain.php