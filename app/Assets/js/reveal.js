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

            // Bind the load handler before assigning src, and use .one('load')
            // rather than the .load(fn) event shorthand, which jQuery 3 removed
            src && img.one("load", function () {
                img[args[0]||"show"].apply(img, args.slice(1));
            }).attr("src", src);
        });
    }
})(jQuery);
