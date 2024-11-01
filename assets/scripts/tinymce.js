(function () {
    if (!SpinData) {
        SpinData.l10n = {
            'WinText': 'Win Text',
            'AvailableSpinsCount': 'Available Spins Count',
            'UserSpinsCount': 'User Spins Count',
            'UserName': 'User Name',
            'UserEmail': 'User Email',
            'TryAgainExpireTime': 'Try Again Expire Time',
            'InsertShortCode': 'Insert ShortCode'
        }
    }
    /* Register the buttons */
    tinymce.create('tinymce.plugins.SpButtons', {
        init: function (ed, url) {
            /**
             * Inserts shortcode content
             */
            var values = [
                {
                    text: 'ShortCode',
                    style: "display:none",

                },
                {
                    text: SpinData.l10n.WinText,
                    onclick: function () {
                        ed.selection.setContent('[win]');
                    }
                },
                {
                    text: SpinData.l10n.AvailableSpinsCount,
                    onclick: function () {
                        ed.selection.setContent('[available_spins_count]');
                    }
                },
                {
                    text: SpinData.l10n.UserSpinsCount,
                    onclick: function () {
                        ed.selection.setContent('[user_spins_count]');
                    }
                },
                {
                    text: SpinData.l10n.UserName,
                    onclick: function () {
                        ed.selection.setContent('[user_name]');
                    }
                },
                {
                    text: SpinData.l10n.UserEmail,
                    onclick: function () {
                        ed.selection.setContent('[user_email]');
                    }
                },
                {
                    text: SpinData.l10n.TryAgainExpireTime,
                    onclick: function () {
                        ed.selection.setContent('[try_again_time]');
                    }
                }
            ];
            ed.addButton('wp_spinner', {
                title: SpinData.l10n.InsertShortCode,
                ico: true,
                type: 'listbox',
                values: values,
            });
        },
        createControl: function (n, cm) {
            return null;
        },
    });
    /* Start the buttons */
    tinymce.PluginManager.add('wp_spinner', tinymce.plugins.SpButtons);
})();