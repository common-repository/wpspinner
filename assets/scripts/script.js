jQuery(document).ready(function ($) {
    var wrapper = '[id^="spin_game_gh_"] ';


    $(document).on('mousedown', wrapper + '.spin-start', function () {
        if ($(this).hasClass('disabled')) {
            return;
        }
        spin($(this));
    });

    $(document).on('click', wrapper + '.restart', function () {
        var $wrapper = $(this).closest('.spin_game_gh');

        $wrapper.find('.spin_result').addClass('leave');
        setTimeout(function () {
            $wrapper.find('.spin_overlay').hide();
        }, 800);
        $wrapper.find('.spin-start').removeClass('disabled');
    });

    function spin($this) {
        var $wrapper = $this.closest('.spin_game_gh');
        var id = $wrapper.find('.roulette').attr('id').split('_')[2];
        var $spinner = $wrapper.find('.roulette .gh_spinner');
        $('#spin_close_global').fadeOut();

        $('.pres_spin_anim').addClass('anim');

        audioPlay($wrapper, 'spin');


        var data = {
            name: $wrapper.find('[name="name"]').val(),
            email: $wrapper.find('[name="email"]').val(),
            spin_id: id,
            action: 'spin_start_gh'
        };


        $.ajax({
            url: spinGh.ajaxUrl,
            method: 'post',
            data: data
        }).done(function (response) {
            $('.pres_spin_anim').removeClass('anim');
            $('#spin_close_global').fadeIn();
            data = JSON.parse(response);

            if (data.status == 'error') {
                $wrapper.find('.spin_result .image_wrapper').html('');
                $wrapper.find('.spin_result .message_area').html(data.message);

                $wrapper.find('.spin_result .claim').hide();
                $wrapper.find('.spin_result .restart').hide();

                $wrapper.find('.spin_overlay').show();
                setTimeout(function () {
                    $wrapper.find('.spin_result').removeClass('leave');
                }, 500);
                return;
            }

            var preffix = 'index-';
            $spinner.toggleClass('spin');
            $spinner.get(0).className = $spinner.get(0).className.replace(new RegExp('(^|\\s)' + preffix + '\\S+', 'g'), '');
            $spinner.addClass(preffix + data.winNumber);


            $wrapper.find('.spin_overlay').show();

            setTimeout(function () {
                $wrapper.find('.spin_result .image_wrapper .win_image').remove();
                $wrapper.find('.spin_result .image_wrapper').prepend(data.itemImage);
                $wrapper.find('.spin_result .message_area').html(data.message);

                $wrapper.find('.spin_result .claim').show();

                if (data.lastSpin) {
                    $wrapper.find('.spin_result .restart').hide();
                } else {
                    $wrapper.find('.spin_result .restart').show();
                }

                $wrapper.find('.spin_result').removeClass('leave');

                if (data.type == 'no_prize') {
                    audioPlay($wrapper, 'lose');
                } else {
                    audioPlay($wrapper, 'win');
                }
            }, parseInt($wrapper.data('duration')) + 800);

            // $spinner.addClass('in-progress');
            // setTimeout(function () {
            //     $spinner.removeClass('in-progress')
            // }, parseInt($wrapper.data('duration')));
        });
    }


    $(document).on('click', wrapper + '.playnow', function () {
        var $wrapper = $(this).closest('.spin_game_gh');
        var $form = $wrapper.find('.gh_spin_form');

        var hasError = false;
        if ($form.find('[name="name"]').val().trim() == '') {
            $form.find('[name="name"]').addClass('error');
            $form.find('[name="name"]').closest('label').find('.error_message').html(spinGh.l10n.nameRequired);
            hasError = true;
        } else {
            $form.find('[name="name"]').removeClass('error');
        }

        if ($form.find('[name="email"]').val().trim() == '') {
            $form.find('[name="email"]').addClass('error');
            $form.find('[name="email"]').closest('label').find('.error_message').html(spinGh.l10n.emailRequired);
            hasError = true;
        } else if (!isValidEmail($form.find('[name="email"]').val())) {
            $form.find('[name="email"]').addClass('error');
            $form.find('[name="email"]').closest('label').find('.error_message').html(spinGh.l10n.emailNotValid);
            hasError = true;
        } else {
            $form.find('[name="email"]').removeClass('error');
        }
        if (!hasError) {
            setCookie('wp-spinner-opened', '1', 0.5);
            $form.addClass('leave');
            $wrapper.find('.spin-start').removeClass('disabled');
            setTimeout(function () {
                $wrapper.find('.spin_overlay').hide();
                $form.hide();
            }, 800);
        }

    });

    $(document).on('focus', wrapper + '.gh_spin_form input', function () {
        $(this).removeClass('error');
        $(this).closest('label').find('.error_message').html('');
    });

    function isValidEmail(email) {
        return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(email);
    }

    $(document).on('click', wrapper + '.claim', function () {
        var $button = $(this);
        if ($button.hasClass('disabled')) {
            return;
        }
        $button.addClass('disabled');

        var $wrapper = $button.closest('.spin_game_gh');
        var data = $wrapper.find(".claimform").serialize();

        $.ajax({
            url: spinGh.ajaxUrl,
            method: 'post',
            data: data
        }).done(function (response) {
            $button.removeClass('disabled');
            try {
                var data = JSON.parse(response);
            } catch (er) {
                console.warn(er);
                return;
            }
            if (data.status == 'ok') {
                $wrapper.find('.buttons').slideUp();
                $wrapper.find('.spin_result .image_wrapper .success').removeClass('leave');

            } else {
                $wrapper.find(".buttons").html('<h3> ' + data.message + ' </h3>');
            }
        });
    });


    $(window).resize(function () {
        $.each($('.spin_game_gh .gh_spinner'), function (_, spin) {
            var $spin = $(spin);
            var $container = $spin.closest('.spinwheel');
            var $containerWidth = $spin.closest('.spin_game_gh').width() || $(document).width();
            if ($spin.width() > $containerWidth) {
                var zoom = Math.floor(parseInt($containerWidth) / parseInt($spin.width()) * 100);
                $container.css({'zoom': zoom + '%'})
            } else {
                $container.css({'zoom': '100%'})
            }
        })
    }).resize();


    $(document).on('click', '#spin_open_global  .open', function () {
        var $wrapper = $('#spin_global_popup .spin_game_gh');
        $('#spin_global_popup').fadeIn();
        if ($('#spin_global_popup .gh_spin_form').is(':visible')) {
            audioPlay($wrapper, 'background');
        }
        $(window).resize();
    });


    function fakeClick(fn) {
        var $a = $('<a href="#" id="fakeClick"></a>');
        $a.bind("click", function (e) {
            e.preventDefault();
            fn();
        });

        $("body").append($a);

        var evt,
            el = $("#fakeClick").get(0);

        if (document.createEvent) {
            evt = document.createEvent("MouseEvents");
            if (evt.initMouseEvent) {
                evt.initMouseEvent("click", true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
                el.dispatchEvent(evt);
            }
        }

        $(el).remove();
    }

    function audioStopAll(className) {
        var stop = 0;
        var allAudio = $('audio');
        if (className) {
            allAudio = allAudio.not('.sounds_wrapper .' + className);
        }
        for (var i = 0; i < allAudio.length; i++) {
            var audio = allAudio.get(i);
            var vol = audio.volume;
            if (vol > 0.2) {
                audio.volume = vol - 0.1;
            } else {
                stop++;
                audio.pause();
                audio.currentTime = 0.0;
            }
        }
        if (stop < allAudio.length) {
            setTimeout(function () {
                audioStopAll(className);
            }, 100);
        }
    }

    function audioPlay($wrapper, className) {
        audioStopAll(className);
        var $audio = $wrapper.find('.sounds_wrapper .' + className);
        if ($audio.length) {
            $audio.get(0).volume = 1;
            fakeClick(function () {
                $audio.get(0).play();
            });
        }
    }

    (function () {
        var spinOpenTimeout = parseInt($('#spin_open_global').attr('data-timeout'));
        if (spinOpenTimeout > -1 && getCookie('wp-spinner-opened') !== '1') {
            setTimeout(function () {
                $('#spin_open_global .open').click();
            }, spinOpenTimeout);
        }
    })();


    $(document).on('click', '#spin_close_global', function () {
        $('#spin_global_popup').fadeOut();
        audioStopAll();
    });


});

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires;
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}