jQuery(document).ready(function ($) {
    var preview = '#spin_game_preview .gh_spinner',
        $preview = $(preview);

    var mainOption = '#spin_game_options .option_wrapper',
        $mainOption = $(mainOption);

    var itemsWrapper = '#spin_items_wrapper',
        $itemsWrapper = $(itemsWrapper);

    var itemsFirstElHtml = $itemsWrapper.find('.item_row').length ? $itemsWrapper.find('.item_row').get(0).outerHTML : '';
    var previewFirstElHtml = $preview.find('.triangle').length ? $preview.find('.triangle').get(0).outerHTML : '';


    var opt = {
        rotate: mainOption + ' [name="rotate"]',
        imgX: mainOption + ' [name="img_x"]',
        imgY: mainOption + ' [name="img_y"]',
        imgSize: mainOption + ' [name="img_size"]',
        spinsCount: mainOption + ' [name="spin_items_count"]',
        spinSize: mainOption + ' [name="size"]',
        textPosition: mainOption + ' [name="text_position"]',
        template: mainOption + ' [name="template"]',
        duration: mainOption + ' [name="duration"]',
        fontSize: mainOption + ' [name="font_size"]',
        borderSize: mainOption + ' [name="border_size"]',
        borderColor: mainOption + ' [name="border_color"]',
    };


    $('.select2_wrapper_gh select').select2();
    $(':not(.select2_wrapper_gh) .select2_gh').select2();

    templatesStyles();

    function templatesStyles() {
        var template = $(opt.template).val();
        var imgVisibility = 'visible';
        var imgReadonly = false, borderReadonly = false;

        if (template == 4) {
            $.each($itemsWrapper.find('.item_row'), function () {
                // var displaySelected = $('.gh_show').find('select[name="items[' + id + '][show]"]').val();
                var id = parseInt($(this).attr('data-id'));
                var show = $('[data-id="' + id + '"] .gh_show select').val();
                var image = $('.item_row[data-id="' + id + '"] .gh_image .cropped_image').val();
                var imgCss = 'none';
                if (image && ('image' == show || 'image_text' == show)) {
                    var imgCss = 'url(' + image + ')';
                }
                $preview.find('.triangle[data-id="' + id + '"]').css({'border-image-source': imgCss});
            });
            imgVisibility = 'hidden';
            imgReadonly = true;

        } else {
            $.each($itemsWrapper.find('.item_row'), function () {
                var id = parseInt($(this).attr('data-id'));
                var show = $('[data-id="' + id + '"] .gh_show select').val();
                $preview.find('.triangle[data-id="' + id + '"]').css({'border-image-source': 'none'});
                var image = $('.item_row[data-id="' + id + '"] .gh_image .real_image').val();
                if (image && ('image' == show || 'image_text' == show)) {
                    $preview.find('.triangle[data-id="' + id + '"]').find('.image').html('<img src="' + image + '" />')
                }
            });
        }


        if (template == 2) {
            borderReadonly = true;
            $(opt.borderColor).closest('.wp-picker-container').addClass('readonly');
            setTimeout(function () {
                $(opt.borderColor).closest('.wp-picker-container').addClass('readonly');

            }, 500);
        } else {

            $(opt.borderColor).closest('.wp-picker-container').removeClass('readonly');
        }

        $(opt.borderSize).attr('readonly', borderReadonly);

        $(opt.imgSize).attr('readonly', imgReadonly);
        $(opt.imgX).attr('readonly', imgReadonly);
        $(opt.imgY).attr('readonly', imgReadonly);
        $.each($preview.find('.triangle'), function () {
            $(this).find('.image').css({'visibility': imgVisibility});
        });

        $.each($('.gh_image'), function () {
            if (template == 4 && $(this).find('.image_wrapper img').length) {
                $(this).find('.edit_image').show();
            } else {
                $(this).find('.edit_image').hide();
            }
        });
    }

    //
    // $('.meta-box-sortables').sortable({
    //     disabled: true
    // });

    var count = parseInt($(opt.spinsCount).val());
    var _count = count;

    function generateInputs() {
        var count = parseInt($(opt.spinsCount).val());
        _count = $itemsWrapper.find('.item_row').length;

        if (_count < count) {
            for (var i = _count + 1; i <= count; i++) {
                var _item = itemsFirstElHtml.replace(/id="1"/g, 'id="' + i + '"').replace(/items\[1\]/g, 'items[' + i + ']').replace(/Item\s+1/g, 'Item ' + i);
                _item = _item.replace(/src=["|'][^"|']+"/, '');
                $itemsWrapper.append(_item);
                var _spinItem = previewFirstElHtml.replace(/id="1"/g, 'id="' + i + '"');

                /****remove first image info in current item****/
                _spinItem = _spinItem.replace(/<img[^>]+>/, '');
                $('[name="items[' + i + '][image]"]').val(0)

                $preview.append(_spinItem);
                $('[name="items[' + i + '][color]"]').val(getRandomColor()).change();
                $('.item_row[data-id="' + i + '"] .remove_image').click();
            }
        } else {
            $.each($itemsWrapper.find('.item_row'), function () {
                var id = parseInt($(this).attr('data-id'));
                if (id > count) {
                    $(this).remove();
                }
            });
            $.each($preview.find('.triangle'), function () {
                var id = parseInt($(this).attr('data-id'));
                if (id > count) {
                    $(this).remove();
                }
            });
        }

        $(".roulette .gh_spinner").attr('class', 'gh_spinner');
        colorPickerInit();
        reloadStyle();
        $('#gh_tab_mode').change();
    };


    //wp color-picker
    $mainOption.find('.border_color').wpColorPicker({
        change: function (event, ui) {
            generateInputs();
        }
    });
    colorPickerInit();

    function colorPickerInit() {
        $('.gh_color .gh_color_picker').wpColorPicker({
            clear: false,
            change: function (event, ui) {
                var newColor = $(this).val();
                var key = parseInt($(this).parents('.item_row').attr('data-id'));
                if (key) {
                    $preview.find('.triangle[data-id="' + key + '"]').css({
                        'color': newColor,
                        'border-top-color': newColor
                    });
                } else if ($(this).hasClass('border_color')) {
                    generateInputs();
                }
            }
        });
    }


    function reloadStyle() {
        var colors = [];
        $.each($itemsWrapper.find('.item_row .gh_color input.gh_color_picker'), function (key, color) {
            colors.push($(color).val());
        });
        var req = {
            'colors': colors.join(';'),
            'size': $(opt.spinSize).val(),
            'img_size': $(opt.imgSize).val(),
            'img_y': $(opt.imgY).val(),
            'img_x': $(opt.imgX).val(),
            'text_position': $(opt.textPosition).val(),
            'duration': $(opt.duration).val(),
            'template': $(opt.template).val(),
            'font_size': $(opt.fontSize).val(),
            'border': $('[name="border_size"]').val() + ';' + $('[name="border_color"]').val(),
            'rotate': $(opt.rotate).val(),
        };
        templatesStyles();
        var style = SpinData.pluginUrl + 'includes/style.php?' + build_query(req);
        $('#spin_style_gh-css').attr('href', style);
        $preview.find('.triangle .image img').css({'left': '', 'top': ''})

    }


    function build_query(obj, num_prefix, temp_key) {
        var output_string = [];
        Object.keys(obj).forEach(function (val) {
            var key = val;
            num_prefix && !isNaN(key) ? key = num_prefix + key : '';
            var key = encodeURIComponent(key.replace(/[!'()*]/g, escape));
            temp_key ? key = temp_key + '[' + key + ']' : '';
            if (typeof obj[val] === 'object') {
                var query = build_query(obj[val], null, key);
                output_string.push(query);
            } else {
                var value = encodeURIComponent(obj[val].replace(/[!'()*]/g, escape));
                output_string.push(key + '=' + value);
            }
        });
        return output_string.join('&');
    }


    function getRandomColor() {
        var letters = '0123456789ABCDEF';
        var color = '#';
        for (var i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }

    //image drag and drop position changer
    (function () {
        var posX = 0;
        var posY = 0;
        var left = 0;
        var top = 0;
        var active = false;
        $(document).on('dragstart', preview + ' .image img', function (ev) {
            ev.preventDefault();
        });
        $(document).on('mousedown', preview + ' .triangle[data-id="1"] .image img', function (ev) {
            posX = ev.clientX;
            posY = ev.clientY;
            top = parseFloat($preview.find('.image img').css('top'));
            left = parseFloat($preview.find('.image img').css('left'));
            // if ($mainOption.find('[name="rotate"]').val() == -90) {
            //     left *= -1;
            //     top *= -1;
            // }
            active = true;
            $('.spin_image_move_gh').fadeOut('slow');
        });
        $(document).on('mouseup', function (ev) {
            active = false;
        });
        $(document).on('mousemove', function (ev) {
            if (!active) return;
            var mousePosX = (posX - ev.clientX) / 4;
            var mousePosY = (posY - ev.clientY) / 4;
            var roteteDeg = $(opt.rotate).val();

            if (roteteDeg == -90) {
                mousePosX *= -1;
                mousePosY *= -1;
            } else if (roteteDeg == 0) {
                mousePosY *= -1;
            }

            var x = mousePosX + left;
            var y = mousePosY + top;

            if (roteteDeg == 0) {
                var temp = x;
                x = y;
                y = temp;
            }
            $preview.find(' .image img').css({
                'top': y,
                'left': x
            });
            $(opt.imgX).val(x.toFixed());
            $(opt.imgY).val(y.toFixed());

            moveIcon();
        });
    })();


    //spin text rotate
    $(document).on('change', opt.rotate, function () {
        var imgPosX, imgPosY;
        var rotate = $(this).val();
        if (rotate == 0) {
            imgPosX = -5;
            imgPosY = -40;
        } else if (rotate > 0) {
            imgPosX = -120;
            imgPosY = -8;
        } else {
            imgPosX = 110;
            imgPosY = -8;
        }

        $(opt.imgX).val(imgPosX);
        $(opt.imgY).val(imgPosY).change();
    });


    $(document).on('change', 'input[type="number"]', function () {
        if ($(this).get(0).hasAttributes('min')) {
            var min = parseInt($(this).attr('min')) || false;
            if (min !== false && $(this).val() < min)
                $(this).val(min)
        }
        if ($(this).get(0).hasAttribute('max')) {
            var max = parseInt($(this).attr('max')) || false;
            if (max !== false && $(this).val() > max)
                $(this).val(max)
        }
    });

    //on main option change
    $(document).on('change', mainOption + ' input, ' + mainOption + ' select', function () {
        generateInputs();
    });

    //text preview
    $(document).on('keyup', itemsWrapper + ' .gh_text input', function () {
        var id = $(this).parents('.item_row').attr('data-id');
        var val = $(this).val().replace(/\s/g, '&nbsp;');
        var show = $(this).parents('.item_row').find('.gh_show select').val();
        if (show == 'text' || show == 'image_text') {
            $preview.find('.triangle[data-id="' + id + '"] .content  .text').html(val);
        }
    });


    //add spin image
    $(document).on('click', itemsWrapper + ' .add_image', function (ev) {
        ev.preventDefault();
        var imgItemWrapper = $(this).parents('.gh_image');
        var wrapper = $(this).parents('.item_row');
        var button = $(this);
        var custom_uploader = wp.media({
            library: {type: 'image'},
            button: {text: 'Select'},
            multiple: false,
        });
        custom_uploader.on('select', function () {
            var attachment = custom_uploader.state().get('selection').first().toJSON();


            imgItemWrapper.find('input.image_id').val(attachment.id);
            imgItemWrapper.find('.image_wrapper').html('<img class="custom_media_image" src=""  />');
            imgItemWrapper.find('.custom_media_image').attr('src', attachment.url).css('display', 'block').change();
            wrapper.find('.gh_show select').change();
            $preview.find('.image img').mousedown().mousemove().mouseup();
            if ($(opt.template).val() == 4) {
                imgItemWrapper.find('.edit_image').show();
            }
        }).open();
        return false;
    });


    $(document).on('click', itemsWrapper + ' .remove_image', function (ev) {
        ev.preventDefault();
        var imgItemWrapper = $(this).parents('.gh_image');
        var wrapper = $(this).parents('.item_row');
        imgItemWrapper.find('input[type="hidden"]').val('');
        imgItemWrapper.find('.image_wrapper').html('');
        wrapper.find('.gh_show select').change();
        imgItemWrapper.find('.edit_image').hide();
    });


    $(document).on('change', '#spin_items_wrapper .gh_show select', function () {
        var id = $(this).parents('.item_row').attr('data-id');
        var val = $(this).val();
        var wrapper = $(this).parents('.item_row');
        var hiddenImg = wrapper.find('.image_id');
        var template = $(opt.template).val();
        if (template == 4 && wrapper.find('.image_id').val() != 0) {
            $.ajax({
                url: SpinData.ajaxUrl,
                method: 'post',
                data: {action: 'get_spin_cropped_image', id: wrapper.find('.image_id').val()}
            }).done(function (croppedImage) {
                changeSpin(val, wrapper, id, hiddenImg, croppedImage);
            });
        } else {
            changeSpin(val, wrapper, id, hiddenImg, '');
        }

    });


    function changeSpin(val, wrapper, id, hiddenImg, croppedImage) {
        var item = $('.gh_spinner [data-id="' + id + '"]');
        var text = $('.gh_text input').val().trim() || '&nbsp;';
        var image = wrapper.find('.image_wrapper img').length ? wrapper.find('.image_wrapper img').get(0).outerHTML :
            '<img src="' + SpinData.noImage + '" />';
        var template = $(opt.template).val();
        var croppedImageCss = croppedImage ? 'url(' + croppedImage + ')' : 'none';
        switch (val) {
            case 'text':
                item.find('.text').html(text);
                template == 4 ? item.css({'border-image-source': 'none'}) : item.find('.image').html('');
                break;
            case 'image':
                item.find('.text').html('&nbsp;');
                template == 4 ? item.css({'border-image-source': croppedImageCss}) : item.find('.image').html(image);
                break;
            case 'image_text':
                item.find('.text').html(text);
                template == 4 ? item.css({'border-image-source': croppedImageCss}) : item.find('.image').html(image);
                break;
        }
        item.find('.image img').attr('srcset', '').attr('sizes', '');
    }


    $(document).on('change', itemsWrapper + ' .gh_product_wrapper select, ' + itemsWrapper + ' .gh_coupon_wrapper select', function () {
        var wrapper = $(this).parents('.item_row');
        var id = wrapper.attr('data-id');
        var val = $(this).val() || 0;
        $('[name="items[' + id + '][obj_id]"]').val(val);
    });


    $(document).on('change', itemsWrapper + ' .gh_type select', function () {
        var id = $(this).parents('.item_row').attr('data-id');
        var item = $('.gh_spinner [data-id="' + id + '"]');
        var val = $(this).val();
        var wrapper = $(this).parents('.item_row');
        wrapper.find('.gh_product_wrapper').hide();
        wrapper.find('.gh_coupon_wrapper').hide();
        wrapper.find('.gh_show select [value="pr-image"]').prop('disabled', true);
        wrapper.find('.gh_show select [value="pr-image_text"]').prop('disabled', true);
        if (val == 'product') {
            wrapper.find('.gh_show select [value="pr-image"]').prop('disabled', false);
            wrapper.find('.gh_show select [value="pr-image_text"]').prop('disabled', false);
            wrapper.find('.gh_product_wrapper').show();
        } else if (val == 'coupon') {
            wrapper.find('.gh_coupon_wrapper').show();
        }
    });
    $itemsWrapper.find('.gh_type select').change();


    $(document).on('change', '#gh_tab_mode', function () {
        var wrapper = $('#spin_items_wrapper .nav_bar_wrapper');
        if ($(this).is(':checked')) {
            var activeClass = 'nav-tab-active';
            var nav = '<nav class="nav-tab-wrapper">';
            $.each($('#spin_items_wrapper .item_row'), function () {
                var id = $(this).attr('data-id');
                nav += '<a href="#" class="nav-tab ' + activeClass + '" data-id="' + id + '" >' + $(this).find('h4').text() + '</a>';
                activeClass = '';
            });
            nav += '</nav>';
            wrapper.html(nav);
            wrapper.slideDown();
            $('#spin_items_wrapper .item_row').not(':first').slideUp();
        } else {
            wrapper.slideUp();
            $('#spin_items_wrapper .item_row').slideDown();
        }
    });
    $('#gh_tab_mode').click();
    $(document).on('click', '#spin_items_wrapper .nav_bar_wrapper a', function (ev) {
        ev.preventDefault();
        $('#spin_items_wrapper .nav_bar_wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        var id = $(this).attr('data-id');
        $('#spin_items_wrapper .item_row').slideUp();
        $('#spin_items_wrapper .item_row[data-id="' + id + '"]').slideDown();
    });
    $(document).on('click', '.roulette .spin-start', function () {
        var count = $('.gh_spinner .triangle').length;
        var $spinner = $('.roulette .gh_spinner');
        var value = parseInt(Math.random() * (count - 1) + 1);
        var preffix = 'index-';
        $spinner.toggleClass('spin');
        $spinner.get(0).className = $spinner.get(0).className.replace(new RegExp('(^|\\s)' + preffix + '\\S+', 'g'), '');
        $spinner.addClass(preffix + value);

        setTimeout(function () {
            $spinner.css({'transition': 'none'});
            $spinner.get(0).className = $spinner.get(0).className.replace(new RegExp('(^|\\s)' + preffix + '\\S+', 'g'), '');
            setTimeout(function () {
                $spinner.css({'transition': ''});
            }, 1)
        }, 1500 + parseInt($(opt.duration).val()));
        //
        // $spinner.addClass('in-progress');
        // setTimeout(function () {
        //     $spinner.removeClass('in-progress')
        // }, parseInt($(opt.duration).val()));
    });


    $(document).on('click', '#spin_game_form  .add_image', function (ev) {
        ev.preventDefault();
        var $input = $('[name="form[bg_image]"]');
        var custom_uploader = wp.media({
            library: {type: 'image'},
            button: {text: 'Select'},
            multiple: false,
        });
        custom_uploader.on('select', function () {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $input.val(attachment.url);
        }).open();
        return false;
    });

    $(document).on('click', '#spin_game_form .remove_image', function (ev) {
        ev.preventDefault();
        var $input = $('[name="form[bg_image]"]');
        $input.val('');
    });


    $(document).on('click', '#spin_messages_wrapper .add_image', function (ev) {
        ev.preventDefault();
        var $input = $('[name="result[bg_image]"]');
        var uploader = wp.media({
            library: {type: 'image'},
            button: {text: 'Select'},
            multiple: false,
        });
        uploader.on('select', function () {
            var attachment = uploader.state().get('selection').first().toJSON();
            $input.val(attachment.url);
        }).open();
        return false;
    });

    $(document).on('click', '#spin_messages_wrapper .remove_image', function (ev) {
        ev.preventDefault();
        var $input = $('[name="result[bg_image]"]');
        $input.val('');
    });


    $(document).on('click', '#gh_audio_section .add_audio', function (ev) {
        ev.preventDefault();
        var $wrapper = $(this).parents('.item');
        var $input = $wrapper.find('input');
        var $audio = $wrapper.find('audio');
        var uploader = wp.media({
            library: {type: 'audio'},
            button: {text: 'Select'},
            multiple: false,
        });
        uploader.on('select', function () {
            var attachment = uploader.state().get('selection').first().toJSON();
            $input.val(attachment.id);
            var src = attachment.url + '?_=' + Math.random();
            var baseName = attachment.url.split('/').reverse()[0];
            $audio.attr('src', src);
            $audio.find('source').attr('src', src);
            $audio.find('a').attr('href', src).html(attachment.url);

            $wrapper.find('.name').html('( ' + baseName + ' )');
        }).open();
        return false;
    });

    $(document).on('click', '#gh_audio_section .remove_audio', function (ev) {
        ev.preventDefault();
        var $wrapper = $(this).parents('.item');
        var $input = $wrapper.find('input');
        $input.val('0');
        $wrapper.find('.name').html('( )');
    });

    $(document).on('click', '#gh_audio_section .default_audio', function (ev) {
        ev.preventDefault();
        var $wrapper = $(this).parents('.item');
        var $audio = $wrapper.find('audio');
        var $input = $wrapper.find('input');
        var src = $wrapper.attr('data-default') + '?_=' + Math.random();
        var baseName = $wrapper.attr('data-default').split('/').reverse()[0];
        $audio.attr('src', src);
        $audio.find('source').attr('src', src);
        $audio.find('a').attr('href', src).html($wrapper.attr('data-default'));
        $input.val('');


        $wrapper.find('.name').html('( ' + baseName + ' )');

    });


    moveIcon(true);

    function moveIcon(show) {
        var img = $preview.find('.triangle[data-id="1"] .image img');

        if (img.length) {
            $('.spin_image_move_gh').css({
                'top': parseInt(img.offset().top) + (parseInt(img.width()) / 2),
                'left': parseInt(img.offset().left) + (parseInt(img.width()))
            })
        } else {
            $('.spin_image_move_gh').fadeOut();
            return;
        }

        var visible_area = parseInt($preview.get(0).getBoundingClientRect().left) + parseInt($preview.width());
        var moveIconPos = parseInt($('.spin_image_move_gh').css('left'));

        if (show) {
            $('.spin_image_move_gh').fadeIn();
        }

        if (visible_area > moveIconPos) {
            $('.spin_image_move_gh').css({'pointer-events': 'none'});
            setTimeout(function () {
                $('.spin_image_move_gh').fadeOut('slow');
            }, 8000)
        } else {
            $('.spin_image_move_gh').css({'pointer-events': ''});
            $('.spin_image_move_gh').fadeIn();
        }

    }

    $(document).on('mousedown', '.spin_image_move_gh', function (ev) {
        var visible_area = parseInt($preview.get(0).getBoundingClientRect().left) + parseInt($preview.width());
        var moveIconPos = parseInt($('.spin_image_move_gh').css('left'));
        if (visible_area < moveIconPos) {
            $(opt.rotate).change();
            $('.spin_image_move_gh').fadeOut('slow');
        }
    });


    $(document).on('change', '.bulk_action .bulk_prize_type select', function (ev) {
        if (!$(this).val()) {
            return;
        }
        $itemsWrapper.find('.gh_type select').val($(this).val()).change();
        $(this).val('');
    });

    $(document).on('change', '.bulk_action .bulk_show select', function (ev) {
        if (!$(this).val()) {
            return;
        }
        $itemsWrapper.find('.gh_show select').val($(this).val()).change();
        $(this).val('');
    });

    //disable meta-box sorting
    if (typeof $.fn.sortable == 'function') {
        $('.meta-box-sortables').sortable({
            disabled: true
        });
    }

});