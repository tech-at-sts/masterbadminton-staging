(function ($) {

    // cookies
    $(document).ready(function () {

        var data = {
            closeClass: _wpcb_ajax_obj.closeClass,
            mess: _wpcb_ajax_obj.mess,
            expire: _wpcb_ajax_obj.expire,
            posi: _wpcb_ajax_obj.posi,
            linkRules: _wpcb_ajax_obj.linkRules,
            textlinkRules: _wpcb_ajax_obj.textlinkRules
        };


        if ($.cookie("cookiebar") === undefined) {

            $(".wpcb-cookie-bar").append('<div id="cookie-message" class="cookie-message">' + data.mess + ' <a id="cookie-rules" class="cookie-rules" href="' + data.linkRules + '">' + data.textlinkRules + '</a><div id="cookie-btn" class="' + data.closeClass + '">Ok</div></div>');
            $("#cookie-message").css(data.posi, "0");

            $("#cookie-btn").click(function (e) {
                e.preventDefault();
                $("#cookie-message").fadeOut();
                $.cookie("cookiebar", "viewed", {expires: parseInt(data.expire, 10)});// because wp_localize_script() send only strings we need to type cast
            });
        }

        return false;

    });

})(jQuery);