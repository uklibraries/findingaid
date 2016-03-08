(function() {
    var $ = jQuery;
    $(document).ready(function () {
        $('.toplink').click(function (e) {
            $('.fa-long').animate({ scrollTop: 0 }, 'fast');
        });
    });
})();
