(function() {
    var $ = jQuery;
    var current_image = null;
    var lity_open = false;
    var lightbox = lity();
    $(document).ready(function () {
        // Limestone accordions: generate the accessible toggle buttons.
        $('.js-accordion').accordion({ buttonsGeneratedContent: 'html' });

        // Limestone ships these init functions but never calls them
        if (typeof image_gallery === 'function') {
            image_gallery();
        }
        if (typeof togglebutton === 'function') {
            togglebutton();
        }
        if (typeof modals === 'function') {
            modals();
        }

        // Masonry measures image heights at init, but thumbnails lazy-load via
        // unveil, so re-layout each gallery as its images actually arrive.
        $('.image-gallery').each(function () {
            var gallery = this;
            var pending;
            gallery.addEventListener('load', function () {
                clearTimeout(pending);
                pending = setTimeout(function () {
                    $(gallery).masonry('layout');
                }, 50);
            }, true);
        });

        // Highlight the table-of-contents entry matching the current URL hash.
        function highlight_current() {
            $('.fa-toc-entry').each(function () {
                if ($(this).attr('href') === window.location.hash) {
                    $(this).addClass('fa-toc-entry-highlight');
                } else {
                    $(this).removeClass('fa-toc-entry-highlight');
                }
            });
        }
        $(window).on('hashchange', function () { highlight_current(); });
        highlight_current();

        function play_media(trigger, tag) {
            var href_id = trigger.attr('data-id');
            var href = trigger.attr('data-href');
            var block = trigger.closest('.image-gallery__block');
            var gallery = block.closest('.image-gallery');
            block.addClass('image-gallery__block--player');
            trigger.after(
                '<' + tag + ' id="' + href_id + '" class="fa-media" src="' + href + '"></' + tag + '>'
            );
            trigger.remove();
            $('#' + href_id).mediaelementplayer({
                success: function (media) {
                    media.play();
                    relayout(gallery);
                    // wait for the mejs control bar to put focus on controls
                    setTimeout(function () {
                        $('#' + href_id).closest('.mejs-container')
                            .find('.mejs-playpause-button button').trigger('focus');
                    }, 0);
                }
            });
        }
        $('.click-to-play-audio').click(function () {
            play_media($(this), 'audio');
        });
        $('.click-to-play-video').click(function () {
            play_media($(this), 'video');
        });
        // The controls live in an .image-gallery__controls sibling that follows
        // the gallery, so hop between the two rather than assuming a shared parent.
        function gallery_of(controls) {
            return controls.closest('.image-gallery__controls').prev('.image-gallery');
        }
        function controls_of(gallery) {
            return gallery.next('.image-gallery__controls');
        }
        // Revealed overflow images were measured at zero size while hidden, so
        // masonry must re-read them before laying out.
        function relayout(gallery) {
            if (gallery.data('masonry')) {
                gallery.masonry('reloadItems').masonry('layout');
            }
        }
        // Move overflow images into the visible masonry flow: load the thumbnail,
        // drop the marker classes on both the <img> and its block wrapper.
        function reveal_overflow(imgs) {
            imgs.reveal().removeClass('image-overflow');
            imgs.closest('.image-gallery__block').removeClass('image-gallery__block--overflow');
        }
        function update_click_to_load(gallery) {
            var controls = controls_of(gallery);
            var images_remaining = gallery.find('img.image-overflow').length;
            if (images_remaining == 0) {
                controls.find('.click-to-load-images').remove();
                controls.find('.click-to-load-all-images').remove();
            }
            else {
                controls.find('.click-to-load-all-images').removeClass('fa-hidden');
            }
        }
        $('.click-to-load-images').click(function () {
            var gallery = gallery_of($(this));
            reveal_overflow(gallery.find('img.image-overflow').slice(0, 4));
            update_click_to_load(gallery);
            relayout(gallery);
        });
        $('.click-to-load-all-images').click(function () {
            var gallery = gallery_of($(this));
            reveal_overflow(gallery.find('img.image-overflow'));
            controls_of(gallery).find('.click-to-load-images').remove();
            $(this).remove();
            relayout(gallery);
        });
        function set_viewer_control(control, enabled) {
            control.prop('disabled', !enabled);
        }
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
        // All the image anchors of the gallery containing #id, in document order.
        function sequence_of(id) {
            return $('#' + id).closest('.image-gallery').find('.image-sequence');
        }
        function lity_load(id) {
            var a = $('#' + id);
            if (a.length > 0) {
                var gallery = a.closest('.image-gallery');
                var overflow = a.find('img.image-overflow');
                if (overflow.length > 0) {
                    reveal_overflow(overflow);
                    update_click_to_load(gallery);
                    relayout(gallery);
                }
                $('#viewer-img').attr('src', a.attr('href'));
                $('#viewer-img').attr('alt', a.find('img').attr('alt') || '');
                var seq = sequence_of(id);
                var idx = seq.index(a);
                set_viewer_control($('.viewer-prev'), idx > 0);
                set_viewer_control($('.viewer-next'), idx > -1 && idx < seq.length - 1);
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
                var seq = sequence_of(current_image);
                var idx = seq.index($('#' + current_image));
                if (idx > 0) {
                    lity_load(seq.eq(idx - 1).attr('id'));
                }
            }
        }
        function next_image() {
            if (lity_open && current_image) {
                var seq = sequence_of(current_image);
                var idx = seq.index($('#' + current_image));
                if (idx > -1 && idx < seq.length - 1) {
                    lity_load(seq.eq(idx + 1).attr('id'));
                }
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
        (function () {
            if (!document.referrer) return;
            var refUrl;
            try { refUrl = new URL(document.referrer); } catch (e) { return; }
            if (refUrl.origin !== window.location.origin) return;
            if (refUrl.pathname !== '/catalog/' || !refUrl.search) return;
            var li = document.querySelector('.breadcrumbs .back-to-search');
            var a  = li && li.querySelector('[data-back-to-search]');
            if (!li || !a) return;
            a.href = refUrl.href;
            li.classList.remove('fa-hidden');
        })();

        $('.fa-expand-all').on('click', function () {
            var $btn = $(this);
            var expanded = $btn.attr('data-state') === 'expanded';
            var $scope = $btn.closest('.editorial');
            $scope.find('button.js-accordion__header').attr('aria-expanded', expanded ? 'false' : 'true');
            $scope.find('.js-accordion__panel').attr('aria-hidden', expanded ? 'true' : 'false');
            $btn.attr('data-state', expanded ? 'collapsed' : 'expanded')
            .text(expanded ? 'Expand all' : 'Collapse all');
        });
    });
})();
