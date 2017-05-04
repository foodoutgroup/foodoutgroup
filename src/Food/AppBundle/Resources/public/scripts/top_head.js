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

// google tag manager
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-PNDJF9');

// facebook pixel
!function (f, b, e, v, n, t, s) {
    if (f.fbq)return;
    n = f.fbq = function () {
        n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments)
    };
    if (!f._fbq) f._fbq = n;
    n.push = n;
    n.loaded = !0;
    n.version = '2.0';
    n.queue = [];
    t = b.createElement(e);
    t.async = !0;
    t.src = v;
    s = b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t, s)
}(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');

// adform

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

})();