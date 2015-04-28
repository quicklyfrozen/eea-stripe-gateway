<h3>
	<?php _e('Stripe Gateway', 'event_espresso'); ?>
</h3>
<p>
	<?php printf(__('Adjust the settings for the Stripe payment gateway. More information can be found on %s Stripe.com %s .', 'event_espresso'),'<a href="http://www.stripe.com/">','</a>'); ?>
</p>
<h3><?php _e('Stripe Settings', 'event_espresso'); ?></h3>
<ul>
	<li>
		<strong><?php _e('Stripe Publishable Key', 'event_espresso'); ?></strong><br />
		<?php _e('Enter your Stripe publishable API key (test or live). You can get all your keys from <a href="http://dashboard.stripe.com/account/apikeys">your account page</a>, 
			or find out about <a href="http://stripe.com/docs/tutorials/dashboard#livemode-and-testing">livemode and testing</a>.', 'event_espresso'); ?>
	</li>
	<li>
		<strong><?php _e('Stripe Secret Key', 'event_espresso'); ?></strong><br />
		<?php _e('Enter your Stripe Secret API key (test or live). This will authenticate you to Stripe, and it\'s separate from your publishable key — keep it secret and keep it safe.', 'event_espresso'); ?>
	</li>
	<li>
		<strong><?php _e('Validate the billing ZIP code', 'event_espresso'); ?></strong><br />
		<?php _e('Specify if the billing ZIP code should be validated on the checkout.', 'event_espresso'); ?>
	</li>
</ul>