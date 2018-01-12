jQuery(document).ready(function($) {

    /**
     * @namespace EegStripeConnect
     * @type {{
     * slug: string,
     *     oauth_window: boolean,
     *     initialized: boolean,
     *     connect_btn: object,
     *     disconnect_btn: object,
     *     form: object
     *     connect_btn_id: string,
     *     disconnect_btn_id: string,
     *     form_id: string,
     *     redirect_url: string,
     *     submitted_pm: string,
     *     debug_mode: string,
     *     connected_sandbox_text: string,
     *     translations: array {
     *      payment_method_slug: string,
     *		request_connection_errmsg: string,
     *		blocked_popups_notice: string,
     *		error_response: string,
     *		oauth_request_error: string,
     *		unknown_container: string,
     *		espresso_default_styles: string,
     *		wp_stylesheet: string,
     *	    connect_btn_text: string,
     *	    connect_btn_sandbox_text: string,
     *	    pm_debug_is_on_notice: string,
     *	    pm_debug_is_off_notice: string,
     *	    connected_sandbox_text: string,
     *     }
     *
     * }}
     *
     * @namespace EEG_STRIPE_CONNECT_ARGS
     * @type {{
     *		payment_method_slug: string,
     *		request_connection_errmsg: string,
     *		blocked_popups_notice: string,
     *		error_response: string,
     *		oauth_request_error: string,
     *		unknown_container: string,
     *		espresso_default_styles: string,
     *		wp_stylesheet: string,
     *	    connect_btn_text: string,
     *	    connect_btn_sandbox_text: string,
     *	    pm_debug_is_on_notice: string,
     *	    pm_debug_is_off_notice: string,
     *	    connected_sandbox_text: string,
     * }}
     */
    function EegStripeConnect (stripe_instance_vars, translations) {
        this.slug              = stripe_instance_vars.payment_method_slug;
        this.oauth_window      = null;
        this.initialized       = false;
        this.connect_btn       = {};
        this.disconnect_btn    = {};
        this.form              = {};
        this.connect_btn_id    = '#eeg_stripe_connect_btn_' + this.slug;
        this.disconnect_btn_id = '#eeg_stripe_disconnect_btn_' + this.slug;
        this.form_id           = '#' + stripe_instance_vars.form_id;
        this.redirect_url      = 'https://connect.stripe.com/oauth/authorize?response_type=code&scope=read_write';
        this.submitted_pm      = '';
        this.debug_mode        = '0';
        this.translations      = {};

        /**
         * @function
         */
        this.initialize = function() {

            this.initialize_objects();
            // Stripe selected / initialized ?
            if (!this.connect_btn.length ||
                !this.disconnect_btn.length ||
                this.initialized
            ) {
                return;
            }

            this.connect_btn_listeners();

            this.initialized = true;
        };

        /**
         * Initializes jQuery objects which point to various page elements
         * @function
         */
        this.initialize_objects = function() {
            this.connect_btn    = $(this.connect_btn_id);
            this.disconnect_btn = $(this.disconnect_btn_id);
            this.form = $(this.form_id);
        };

        /**
         * Sets up listeners to listen for when the connect button is pressed
         * @function
         */
        this.connect_btn_listeners = function() {
            var $sandbox_mode_select = this.form.find('select[name*=stripe][name*=PMD_debug_mode]');
            // Update connect button text depending on the PM sandbox mode.
            var this_passed_in = this;
            $sandbox_mode_select.each(function() {
                this_passed_in.update_connect_button_text($(this), false);
            });

            // Listen for the sandbox mode change.
            $sandbox_mode_select.on('change', function() {
                this_passed_in.update_connect_button_text($(this), true);
            });

            // Connect with Stripe.
            $(this.connect_btn_id).on('click', function(event) {
                event.preventDefault();
                var button_container = $(this).closest('tr');
                var submitting_form  = $(this).parents('form').eq(0)[0];
                if (button_container && submitting_form) {
                    // Check if window already open.
                    if (this_passed_in.oauth_window &&
                         !this_passed_in.oauth_window.closed
                    ) {
                        this_passed_in.oauth_window.focus();
                        return;
                    }
                    // Need to open the new window now to prevent browser pop-up blocking.
                    var wind_height                 = screen.height / 2;
                    wind_height                     = wind_height > 750 ? 750 : wind_height;
                    wind_height                     = wind_height < 280 ? 280 : wind_height;
                    var wind_width                  = screen.width / 2;
                    wind_width                      = wind_width > 1200 ? 1200 : wind_width;
                    wind_width                      = wind_width < 380 ? 380 : wind_width;
                    var parameters                  = [
                        'location=0',
                        'height=' + wind_height,
                        'width=' + wind_width,
                        'top=' + (screen.height - wind_height) / 2,
                        'left=' + (screen.width - wind_width) / 2,
                        'centered=true',
                    ];
                    this_passed_in.oauth_window = window.open('', 'StripeConnectPopupWindow', parameters.join());
                    setTimeout(function() {
                        $(this_passed_in.oauth_window.document.body).html(
                            '<html><head>' +
                            '<title>Stripe Connect</title>' +
                            '<link rel="stylesheet" type="text/css" href="' +
                            this_passed_in.translations.espresso_default_styles + '">' +
                            '<link rel="stylesheet" type="text/css" href="' + this_passed_in.translations.wp_stylesheet +
                            '">' +
                            '</head><body>' +
                            '<div id="espresso-ajax-loading" class="ajax-loading-grey">' +
                            '<span class="ee-spinner ee-spin">' +
                            '</div></body></html>'
                        );
                        var win_loader           = this_passed_in.oauth_window.document.getElementById(
                            'espresso-ajax-loading');
                        win_loader.style.display = 'inline-block';
                        win_loader.style.top     = '40%';
                    }, 100);
                    // Check in case the pop-up window was blocked.
                    if (!this_passed_in.oauth_window
                        || typeof this_passed_in.oauth_window === 'undefined'
                        || typeof this_passed_in.oauth_window.closed === 'undefined'
                        || this_passed_in.oauth_window.closed
                    ) {
                        this_passed_in.oauth_window = null;
                        alert(this_passed_in.translations.blocked_popups_notice);
                        console.log(this_passed_in.translations.blocked_popups_notice);
                        return;
                    }

                    // Maybe update connected area text.
                    this_passed_in.update_disconnect_section(submitting_form);

                    // Continue to the OAuth page.
                    this_passed_in.submitted_pm = button_container.attr('id').
                        replace(/eeg_stripe_connect_|eeg_stripe_disconnect_/, '');
                    var debug_mode_selector         = submitting_form.querySelector('select[name*=PMD_debug_mode]');
                    this_passed_in.debug_mode   = debug_mode_selector.value;
                    this_passed_in.oauth_send_request('eeg_request_stripe_connect_data');
                } else {
                    console.log(this_passed_in.translations.unknown_container);
                }``
            });

            // Stripe Disconnect.
            $(this.disconnect_btn_id).on('click', function(event) {
                event.preventDefault();
                var button_container = $(this).closest('tr');
                var submitting_form  = $(this).parents('form').eq(0)[0];
                if (button_container && submitting_form) {
                    this_passed_in.submitted_pm = button_container.attr('id').
                        replace(/eeg_stripe_connect_|eeg_stripe_disconnect_/, '');
                    var debug_mode_selector         = submitting_form.querySelector('select[name*=PMD_debug_mode]');
                    this_passed_in.debug_mode   = debug_mode_selector.value;
                    this_passed_in.oauth_send_request('eeg_request_stripe_disconnect');
                } else {
                    console.log(this_passed_in.translations.unknown_container);
                }
            });
        };

        /**
         * Updates the "Connect to Stripe" button
         * @function
         */
        this.update_connect_button_text = function(caller, allow_alert) {
            var submitting_form = caller.parents('form').eq(0)[0];
            if (submitting_form) {
                var stripe_connect_btn = $(submitting_form).find(
                    'a[id=' + this.connect_btn_id.substr(1) + ']'
                )[0];
                if (stripe_connect_btn) {
                    var btn_text_span         = $(stripe_connect_btn).find('span')[0];
                    var pm_debug_mode_enabled = caller[0].value;
                    var disconnect_section    = $(submitting_form).find(
                        'tr[class="eeg-stripe-disconnect-section"]'
                    )[0];

                    // Change btn text.
                    if (btn_text_span) {
                        if (pm_debug_mode_enabled === '1') {
                            $(btn_text_span).text(this.translations.connect_btn_sandbox_text);
                        } else {
                            $(btn_text_span).text(this.translations.connect_btn_text);
                        }
                    }

                    // Maybe show an alert.
                    if (allow_alert && disconnect_section && $(disconnect_section).is(':visible')) {
                        var test_connected = $(disconnect_section).find(
                            'strong[class="eeg-stripe-test-connected-txt"]'
                        )[0];
                        if (test_connected && pm_debug_mode_enabled === '0') {
                            alert(this.translations.pm_debug_is_on_notice);
                        } else if (!test_connected && pm_debug_mode_enabled === '1') {
                            alert(this.translations.pm_debug_is_off_notice);
                        }
                    }
                }
            }
        };

        /**
         * Updates the "Disconnect from Stripe" button
         * @function
         */
        this.update_disconnect_section = function(submitting_form) {
            var pm_debug_mode      = $(submitting_form).find('select[name*=PMD_debug_mode]')[0];
            var disconnect_section = $(submitting_form).find(
                'tr[class="eeg-stripe-disconnect-section"]'
            )[0];
            if (disconnect_section && pm_debug_mode) {
                var test_connected_text = $(disconnect_section).find(
                    'strong[class="eeg-stripe-test-connected-txt"]'
                )[0];
                if (test_connected_text
                    && pm_debug_mode.value === '0'
                    && $(test_connected_text).html().length > 0
                ) {
                    // Remove the sandbox connection note text.
                    $(test_connected_text).html('');
                } else if (test_connected_text
                    && pm_debug_mode.value === '1'
                    && $(test_connected_text).html().length === 0
                ) {
                    // Add the sandbox connection note.
                    $(test_connected_text).html(this.translations.connected_sandbox_text);
                }
            }
        };

        /**
         * Sends the OAuth request and then sets up callbacks depending on how it goes
         * @function
         */
        this.oauth_send_request = function(request_action) {
            var request_data          = {};
            request_data.action       = request_action;
            request_data.submitted_pm = this.submitted_pm;
            request_data.debug_mode   = this.debug_mode;
            var this_passed_in = this;
            $.ajax({
                type:     'POST',
                url:      eei18n.ajax_url,
                data:     request_data,
                dataType: 'json',

                beforeSend: function() {
                    window.do_before_admin_page_ajax();
                },
                success: function(request) {
                    this_passed_in.oauth_send_request_success(request, request_action);
                },
                error:  this.oauth_send_request_error,
            });
        };

        /**
         * How to handle a successful OAuth HTTP Request that was sent
         * @param response
         * @param request_action string
         * @returns {boolean}
         */
        this.oauth_send_request_success = function(response, request_action) {
            var $ajax_spinner = $('#espresso-ajax-loading');
            if (response === null
                || response['stripe_error']
                || typeof response['stripe_success'] === 'undefined'
                || response['stripe_success'] === null
            ) {
                var stripe_error = this.translations.oauth_request_error;
                $ajax_spinner.fadeOut('fast');
                if (response['stripe_error']) {
                    stripe_error = response['stripe_error'];
                }
                console.log(stripe_error);

                // Display the error in the pop-up.
                if (this.oauth_window) {
                    this.oauth_window.document.getElementById(
                        'espresso-ajax-loading').style.display = 'none';
                    $(this.oauth_window.document.body).html(stripe_error);
                    this.oauth_window = null;
                }
                return false;
            }
            $ajax_spinner.fadeOut('fast');

            switch (request_action) {
                // If all is fine open a new window for OAuth process.
                case 'eeg_request_stripe_connect_data':
                    this.open_oauth_window(response['request_url']);
                    break;
                // Disconnect.
                case 'eeg_request_stripe_disconnect':
                    this.update_connection_status();
                    break;
            }
        };

        this.oauth_send_request_error = function(response, error, description) {
            var stripe_error = this.translations.error_response;
            if (description)
                stripe_error = stripe_error + ': ' + description;
            // Display the error in the pop-up.
            if (this.oauth_window) {
                this.oauth_window.document.getElementById(
                    'espresso-ajax-loading').style.display = 'none';
                $(this.oauth_window.document.body).html(stripe_error);
                this.oauth_window = null;
            }
            $('#espresso-ajax-loading').fadeOut('fast');
            console.log(stripe_error);
        }

        /**
         * Opens the OAuth window, or focuses on it if it's already open
         * @function
         */
        this.open_oauth_window = function(request_url) {
            if (this.oauth_window
                && this.oauth_window.location.href.indexOf('about:blank') > -1
            ) {
                this.oauth_window.location = request_url;
                // Update the connection status if window was closed action.
                var this_passed_in = this;
                this.oauth_window_timer    = setInterval(
                    function() {
                        this_passed_in.check_oauth_window();
                    },
                    500
                );
            } else if (this.oauth_window) {
                this.oauth_window.focus();
            }
        };

        /**
         * Checks if the OAuth window has closed
         * @function
         */
        this.check_oauth_window = function() {
            if (this.oauth_window && this.oauth_window.closed) {
                clearInterval(this.oauth_window_timer);
                this.update_connection_status();
                this.oauth_window = false;
            }
        };

        /**
         * Updates the UI to show if we've managed to get connected with Stripe Connect
         * @function
         */
        this.update_connection_status = function() {
            var req_data          = {};
            req_data.action       = 'eeg_stripe_update_connection_status';
            req_data.submitted_pm = this.submitted_pm;
            req_data.debug_mode   = this.debug_mode;
            var this_passed_in = this;
            $.ajax({
                type:     'POST',
                url:      eei18n.ajax_url,
                data:     req_data,
                dataType: 'json',

                beforeSend: function() {
                    window.do_before_admin_page_ajax();
                },
                success:    function(response) {
                    $('#espresso-ajax-loading').fadeOut('fast');
                    if (response['connected'] === true) {
                        $('#eeg_stripe_connect_' + this_passed_in.submitted_pm).hide();
                        $('#eeg_stripe_disconnect_' + this_passed_in.submitted_pm).show();
                    } else {
                        $('#eeg_stripe_connect_' + this_passed_in.submitted_pm).show();
                        $('#eeg_stripe_disconnect_' + this_passed_in.submitted_pm).hide();
                    }
                },
            });
        };
    }
    //ok let's get the ball rolling
    var stripe_connect_objs = {};
    for (var slug in ee_form_section_vars.stripe_connect) {
        stripe_connect_objs[slug] = new EegStripeConnect(ee_form_section_vars.stripe_connect[slug], EEG_STRIPE_CONNECT_ARGS);
        stripe_connect_objs[slug].initialize();
    }

});
