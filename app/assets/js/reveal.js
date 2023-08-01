/* This mini-plugin was written by Marcus Ekwall
 * and published at https://stackoverflow.com/a/7031800/237176 .
 *
 * License: MIT
 *
 * More details: https://meta.stackexchange.com/questions/272956/a-new-code-license-the-mit-this-time-with-attribution-required
 */
(function ($) {
    $.fn.reveal = function () {
        var args = Array.prototype.slice.call(arguments);

        return this.each(function () {
            var img = $(this),
                src = img.data("src");

            src && img.attr("src", src).load(function () {
                img[args[0]||"show"].apply(img, args.splice(1));
            });
        });
    }
})(jQuery);
