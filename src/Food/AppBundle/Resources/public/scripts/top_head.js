// google analytic
(function (i, s, o, g, r, a, m) {
    i['GoogleAnalyticsObject'] = r;
    i[r] = i[r] || function () {
        (i[r].q = i[r].q || []).push(arguments)
    }, i[r].l = 1 * new Date();
    a = s.createElement(o),
        m = s.getElementsByTagName(o)[0];
    a.async = 1;
    a.src = g;
    m.parentNode.insertBefore(a, m)
})(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga', document.createElement('script'));

(function () {
    var s = document.createElement('script');
    s.type = 'text/javascript';
    s.async = true;
    s.src = 'https://track.adform.net/serving/scripts/trackpoint/async/';
    var x = document.getElementsByTagName('script')[0];
    x.parentNode.insertBefore(s, x);
})();

// mailsoft

(function () {
    var mlo = document.createElement('script');
    mlo.type = 'text/javascript';
    mlo.async = true;
    mlo.src = '//app.mailersoft.com/ecommerce/v4/track.js';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(mlo, s);
})();

//conversion

(function () {
    var s = document.getElementsByTagName('body')[0];
    var a = document.createElement("iframe");
    if (typeof s != 'undefined') {
        a.style.cssText = "width: 0; height: 0; border: 0; display: none;";
        a.src = "javascript:false";
        var b = function () {
            setTimeout(function () {
                var b = a.contentDocument || a.contentWindow.document, c = b.createElement("script");
                c.src = "//static-trackers.adtarget.me/javascripts/pixel.min.js";
                c.id = "GIHhtQfW-atm-pixel";
                c["data-pixel"] = "990d8a91b1fde610d377fae55d911552";
                c["allow-flash"] = true;
                b.body.appendChild(c);
                s.parentNode.insertBefore(c, s);
            }, 0);
        };
        a.addEventListener ? a.addEventListener("load", b, !1) : a.attachEvent ? a.attachEvent("onload", b) : a.onload = b;

        s.parentNode.insertBefore(a, s);
    }
})();