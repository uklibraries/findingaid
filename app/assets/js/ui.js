(function() {
    var $ = jQuery;
    var current_image = null;
    var lity_open = false;
    var lightbox = lity();
    $(document).ready(function () {
        $('.toplink').click(function (e) {
            $('.fa-long').animate({ scrollTop: 0 }, 'fast');
        });
        $('.click-to-play-audio').click(function () {
            var href_id = $(this).attr('data-id');
            var href = $(this).attr('data-href');
            $(this).after('<audio id="' + href_id + '" src="' + href  + '" style="display:block, width: 305px; height: 30px;" width="305" height="30"></audio>');
            $('#' + href_id).mediaelementplayer();
            var player = new MediaElement(href_id);
            player.pause();
            player.setSrc(href);
            player.play();
            $(this).remove();
        });
        $('.click-to-play-video').click(function () {
            var href_id = $(this).attr('data-id');
            var href = $(this).attr('data-href');
            $(this).after('<video id="' + href_id + '" src="' + href  + '" style="display:block, width: 360px; height: 240px;" width="360" height="240"></audio>');
            $('#' + href_id).mediaelementplayer();
            var player = new MediaElement(href_id);
            player.pause();
            player.setSrc(href);
            player.play();
            $(this).remove();
        });
        function update_click_to_load(element) {
            var p = element.parent();
            var images_remaining = p.find('.image-overflow').length;
            if (images_remaining == 0) {
                p.find('.click-to-load-images').remove();
                p.find('.click-to-load-all-images').remove();
            }
            else {
                p.find('.click-to-load-all-images').removeClass('fa-hidden');
            }
        }
        $('.click-to-load-images').click(function () {
            $(this).siblings().find('.image-overflow').slice(0, 4).reveal().removeClass('image-overflow');
            update_click_to_load($(this));
        });
        $('.click-to-load-all-images').click(function () {
            $(this).siblings().find('.image-overflow').reveal();
            $(this).siblings('.click-to-load-images').remove();
            $(this).remove();
        });
        $('img.lazy').unveil(200);
        $('.image-sequence').click(function () {
            current_image = $(this).attr('id');
        });
        $(document).on('lity:open', function () {
            lity_open = true;
        });
        $(document).on('lity:close', function () {
            lity_open = false;
        });
        function lity_load(id) {
            if ($('#' + id).length > 0) {
                $('#' + id).find('.image-overflow').reveal().removeClass('image-overflow');
                update_click_to_load($('#' + id));
                $('#viewer-img').attr(
                    'src',
                    $('#' + id).attr('href')
                );
                if ($('#' + id).prev('.image-sequence').length > 0) {
                    $('.viewer-prev').css('color', 'white');
                }
                else {
                    $('.viewer-prev').css('color', 'transparent');
                }
                if ($('#' + id).next('.image-sequence').length > 0) {
                    $('.viewer-next').css('color', 'white');
                }
                else {
                    $('.viewer-next').css('color', 'transparent');
                }
                current_image = id;
            }
        }
        $('.image-sequence').click(function (event) {
            event.stopPropagation();
            lightbox('#viewer');
            lity_load($(this).attr('id'));
            return false;
        });
        function prev_image() {
            if (lity_open && current_image) {
                lity_load($('#' + current_image).prev('.image-sequence').attr('id'));
            }
        }
        function next_image() {
            if (lity_open && current_image) {
                lity_load($('#' + current_image).next('.image-sequence').attr('id'));
            }
        }
        Mousetrap.bind('left', function () {
            prev_image();
        });
        $('.viewer-prev').on('click', function () {
            prev_image();
        });
        Mousetrap.bind('right', function () {
            next_image();
        });
        $('.viewer-next').on('click', function () {
            next_image();
        });
    });
})();
