jQuery(document).ready(function($) {

    var EEG_STRIPE_CONNECT;

    /**
     * @namespace EEG_STRIPE_CONNECT
     * @type {{
     *     oauth_window: boolean,
     *     initialized: boolean,
     *     connect_btn: object,
     *     disconnect_btn: object,
     *     connect_btn_id: string,
     *     disconnect_btn_id: string,
     *     redirect_url: string,
     *     submitted_pm: string,
     *     debug_mode: string,
     *     connected_sandbox_text: string
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
     *	    connected_sandbox_text: string
     * }}
     */
    EEG_STRIPE_CONNECT = {

        oauth_window:      null,
        initialized:       false,
        connect_btn:       {},
        disconnect_btn:    {},
        connect_btn_id:    '#eeg_stripe_connect_btn',
        disconnect_btn_id: '#eeg_stripe_disconnect_btn',
        redirect_url:      'https://connect.stripe.com/oauth/authorize?response_type=code&scope=read_write',
        submitted_pm:      '',
        debug_mode:        '0',

        /**
         * @function
         */
        initialize: function() {
            EEG_STRIPE_CONNECT.initialize_objects();
            // Stripe selected / initialized ?
            if (!EEG_STRIPE_CONNECT.connect_btn.length
                || !EEG_STRIPE_CONNECT.disconnect_btn.length
                || EEG_STRIPE_CONNECT.initialized
            ) {
                return;
            }

            EEG_STRIPE_CONNECT.setup_listeners();

            EEG_STRIPE_CONNECT.initialized = true;
        },

        /**
         * @function
         */
        initialize_objects: function() {
            EEG_STRIPE_CONNECT.connect_btn    = $(EEG_STRIPE_CONNECT.connect_btn_id);
            EEG_STRIPE_CONNECT.disconnect_btn = $(EEG_STRIPE_CONNECT.disconnect_btn_id);
        },

        /**
         * @function
         */
        setup_listeners: function() {
            EEG_STRIPE_CONNECT.connect_btn_listeners();
        },

        /**
         * @function
         */
        connect_btn_listeners: function() {
            var $sandbox_mode_select = $('select[name*=stripe][name*=PMD_debug_mode]');
            // Update connect button text depending on the PM sandbox mode.
            $sandbox_mode_select.each(function() {
                EEG_STRIPE_CONNECT.update_connect_button_text($(this), false);
            });

            // Listen for the sandbox mode change.
            $sandbox_mode_select.on('change', function() {
                EEG_STRIPE_CONNECT.update_connect_button_text($(this), true);
            });

            // Connect with Stripe.
            $(EEG_STRIPE_CONNECT.connect_btn_id).on('click', function(event) {
                event.preventDefault();
                var button_container = $(this).closest('tr');
                var submitting_form  = $(this).parents('form').eq(0)[0];
                if (button_container && submitting_form) {
                    // Check if window already open.
                    if (EEG_STRIPE_CONNECT.oauth_window
                        && !EEG_STRIPE_CONNECT.oauth_window.closed
                    ) {
                        EEG_STRIPE_CONNECT.oauth_window.focus();
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
                    EEG_STRIPE_CONNECT.oauth_window = window.open('', 'StripeConnectPopupWindow', parameters.join());
                    setTimeout(function() {
                        $(EEG_STRIPE_CONNECT.oauth_window.document.body).html(
                            '<html><head>' +
                            '<title>Stripe Connect</title>' +
                            '<link rel="stylesheet" type="text/css" href="' +
                            EEG_STRIPE_CONNECT_ARGS.espresso_default_styles + '">' +
                            '<link rel="stylesheet" type="text/css" href="' + EEG_STRIPE_CONNECT_ARGS.wp_stylesheet +
                            '">' +
                            '</head><body>' +
                            '<div id="espresso-ajax-loading" class="ajax-loading-grey">' +
                            '<span class="ee-spinner ee-spin">' +
                            '</div></body></html>'
                        );
                        var win_loader           = EEG_STRIPE_CONNECT.oauth_window.document.getElementById(
                            'espresso-ajax-loading');
                        win_loader.style.display = 'inline-block';
                        win_loader.style.top     = '40%';
                    }, 100);
                    // Check in case the pop-up window was blocked.
                    if (!EEG_STRIPE_CONNECT.oauth_window
                        || typeof EEG_STRIPE_CONNECT.oauth_window === 'undefined'
                        || typeof EEG_STRIPE_CONNECT.oauth_window.closed === 'undefined'
                        || EEG_STRIPE_CONNECT.oauth_window.closed
                    ) {
                        EEG_STRIPE_CONNECT.oauth_window = null;
                        alert(EEG_STRIPE_CONNECT_ARGS.blocked_popups_notice);
                        console.log(EEG_STRIPE_CONNECT_ARGS.blocked_popups_notice);
                        return;
                    }

                    // Maybe update connected area text.
                    EEG_STRIPE_CONNECT.update_disconnect_section(submitting_form);

                    // Continue to the OAuth page.
                    EEG_STRIPE_CONNECT.submitted_pm = button_container.attr('id').
                        replace(/eeg_stripe_connect_|eeg_stripe_disconnect_/, '');
                    var debug_mode_selector         = submitting_form.querySelector('select[name*=PMD_debug_mode]');
                    EEG_STRIPE_CONNECT.debug_mode   = debug_mode_selector.value;
                    EEG_STRIPE_CONNECT.oauth_send_request('eeg_request_stripe_connect_data');
                } else {
                    console.log(EEG_STRIPE_CONNECT_ARGS.unknown_container);
                }
            });

            // Stripe Disconnect.
            $(EEG_STRIPE_CONNECT.disconnect_btn_id).on('click', function(event) {
                event.preventDefault();
                var button_container = $(this).closest('tr');
                var submitting_form  = $(this).parents('form').eq(0)[0];
                if (button_container && submitting_form) {
                    EEG_STRIPE_CONNECT.submitted_pm = button_container.attr('id').
                        replace(/eeg_stripe_connect_|eeg_stripe_disconnect_/, '');
                    var debug_mode_selector         = submitting_form.querySelector('select[name*=PMD_debug_mode]');
                    EEG_STRIPE_CONNECT.debug_mode   = debug_mode_selector.value;
                    EEG_STRIPE_CONNECT.oauth_send_request('eeg_request_stripe_disconnect');
                } else {
                    console.log(EEG_STRIPE_CONNECT_ARGS.unknown_container);
                }
            });
        },

        /**
         * @function
         */
        update_connect_button_text: function(caller, allow_alert) {
            var submitting_form = caller.parents('form').eq(0)[0];
            if (submitting_form) {
                var stripe_connect_btn = $(submitting_form).find(
                    'a[id=' + EEG_STRIPE_CONNECT.connect_btn_id.substr(1) + ']'
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
                            $(btn_text_span).text(EEG_STRIPE_CONNECT_ARGS.connect_btn_sandbox_text);
                        } else {
                            $(btn_text_span).text(EEG_STRIPE_CONNECT_ARGS.connect_btn_text);
                        }
                    }

                    // Maybe show an alert.
                    if (allow_alert && disconnect_section && $(disconnect_section).is(':visible')) {
                        var test_connected = $(disconnect_section).find(
                            'strong[class="eeg-stripe-test-connected-txt"]'
                        )[0];
                        if (test_connected && pm_debug_mode_enabled === '0') {
                            alert(EEG_STRIPE_CONNECT_ARGS.pm_debug_is_on_notice);
                        } else if (!test_connected && pm_debug_mode_enabled === '1') {
                            alert(EEG_STRIPE_CONNECT_ARGS.pm_debug_is_off_notice);
                        }
                    }
                }
            }
        },

        /**
         * @function
         */
        update_disconnect_section: function(submitting_form) {
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
                    $(test_connected_text).html(EEG_STRIPE_CONNECT_ARGS.connected_sandbox_text);
                }
            }
        },

        /**
         * @function
         */
        oauth_send_request: function(request_action) {
            var request_data          = {};
            request_data.action       = request_action;
            request_data.submitted_pm = EEG_STRIPE_CONNECT.submitted_pm;
            request_data.debug_mode   = EEG_STRIPE_CONNECT.debug_mode;
            $.ajax({
                type:     'POST',
                url:      eei18n.ajax_url,
                data:     request_data,
                dataType: 'json',

                beforeSend: function() {
                    window.do_before_admin_page_ajax();
                },
                success:    function(response) {
                    var $ajax_spinner = $('#espresso-ajax-loading');
                    if (response === null
                        || response['stripe_error']
                        || typeof response['stripe_success'] === 'undefined'
                        || response['stripe_success'] === null
                    ) {
                        var stripe_error = EEG_STRIPE_CONNECT_ARGS.oauth_request_error;
                        $ajax_spinner.fadeOut('fast');
                        if (response['stripe_error']) {
                            stripe_error = response['stripe_error'];
                        }
                        console.log(stripe_error);

                        // Display the error in the pop-up.
                        if (EEG_STRIPE_CONNECT.oauth_window) {
                            EEG_STRIPE_CONNECT.oauth_window.document.getElementById(
                                'espresso-ajax-loading').style.display = 'none';
                            $(EEG_STRIPE_CONNECT.oauth_window.document.body).html(stripe_error);
                            EEG_STRIPE_CONNECT.oauth_window = null;
                        }
                        return false;
                    }
                    $ajax_spinner.fadeOut('fast');

                    switch (request_action) {
                        // If all is fine open a new window for OAuth process.
                        case 'eeg_request_stripe_connect_data':
                            EEG_STRIPE_CONNECT.open_oauth_window(response['request_url']);
                            break;
                        // Disconnect.
                        case 'eeg_request_stripe_disconnect':
                            EEG_STRIPE_CONNECT.update_connection_status();
                            break;
                    }
                },
                error:      function(response, error, description) {
                    var stripe_error = EEG_STRIPE_CONNECT_ARGS.error_response;
                    if (description)
                        stripe_error = stripe_error + ': ' + description;
                    // Display the error in the pop-up.
                    if (EEG_STRIPE_CONNECT.oauth_window) {
                        EEG_STRIPE_CONNECT.oauth_window.document.getElementById(
                            'espresso-ajax-loading').style.display = 'none';
                        $(EEG_STRIPE_CONNECT.oauth_window.document.body).html(stripe_error);
                        EEG_STRIPE_CONNECT.oauth_window = null;
                    }
                    $('#espresso-ajax-loading').fadeOut('fast');
                    console.log(stripe_error);
                },
            });
        },

        /**
         * @function
         */
        open_oauth_window: function(request_url) {
            if (EEG_STRIPE_CONNECT.oauth_window
                && EEG_STRIPE_CONNECT.oauth_window.location.href.indexOf('about:blank') > -1
            ) {
                EEG_STRIPE_CONNECT.oauth_window.location = request_url;
                // Update the connection status if window was closed action.
                EEG_STRIPE_CONNECT.oauth_window_timer    = setInterval(EEG_STRIPE_CONNECT.check_oauth_window, 500);
            } else if (EEG_STRIPE_CONNECT.oauth_window) {
                EEG_STRIPE_CONNECT.oauth_window.focus();
            }
        },

        /**
         * @function
         */
        check_oauth_window: function() {
            if (EEG_STRIPE_CONNECT.oauth_window && EEG_STRIPE_CONNECT.oauth_window.closed) {
                clearInterval(EEG_STRIPE_CONNECT.oauth_window_timer);
                EEG_STRIPE_CONNECT.update_connection_status();
                EEG_STRIPE_CONNECT.oauth_window = false;
            }
        },

        /**
         * @function
         */
        update_connection_status: function() {
            var req_data          = {};
            req_data.action       = 'eeg_stripe_update_connection_status';
            req_data.submitted_pm = EEG_STRIPE_CONNECT.submitted_pm;
            req_data.debug_mode   = EEG_STRIPE_CONNECT.debug_mode;
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
                        $('#eeg_stripe_connect_' + EEG_STRIPE_CONNECT.submitted_pm).hide();
                        $('#eeg_stripe_disconnect_' + EEG_STRIPE_CONNECT.submitted_pm).show();
                    } else {
                        $('#eeg_stripe_connect_' + EEG_STRIPE_CONNECT.submitted_pm).show();
                        $('#eeg_stripe_disconnect_' + EEG_STRIPE_CONNECT.submitted_pm).hide();
                    }
                },
            });
        },
    };

    EEG_STRIPE_CONNECT.initialize();
});
