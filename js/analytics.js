(function(d, e, j, h, f, c, b) {
  d.GoogleAnalyticsObject = f;
  d[f] = d[f] || function() {
    (d[f].q = d[f].q || []).push(arguments)
  }, d[f].l = 1 * new Date();
  c = e.createElement(j), b = e.getElementsByTagName(j)[0];
  c.async = 1;
  c.src = h;
  b.parentNode.insertBefore(c, b)
})(window, document, "script", "//www.google-analytics.com/analytics.js", "ga");
ga("create", "UA-60838090-1", "auto");
ga("send", "pageview");
</script>
<meta name=msvalidate.01 content=00888EC7EEFC76AE9ECEC39F00B5543F>
<script>
var _prum = [
  ["id", "55775fa3abe53d0557f94071"],
  ["mark", "firstbyte", (new Date()).getTime()]
];
(function() {
  var a = document.getElementsByTagName("script")[0],
    b = document.createElement("script");
  b.async = "async";
  b.src = "//rum-static.pingdom.net/prum.min.js";
  a.parentNode.insertBefore(b, a)
})();

var _kmq = _kmq || [];
var _kmk = _kmk || '0adf9ea1b0fdea0f4e71364876abdfd108104963';

function _kms(u) {
  setTimeout(function() {
    var d = document,
      f = d.getElementsByTagName('script')[0],
      s = d.createElement('script');
    s.type = 'text/javascript';
    s.async = true;
    s.src = u;
    f.parentNode.insertBefore(s, f);
  }, 1);
}
_kms('//i.kissmetrics.com/i.js');
_kms('//scripts.kissmetrics.com/' + _kmk + '.2.js');
</script>
<!-- End Kissmetrics Code -->

<!-- Start Alexa Certify Javascript -->
<script type="text/javascript">
_atrk_opts = {
  atrk_acct: "s/i/m1aMp4Z3fn",
  domain: "freeyourinbox.com",
  dynamic: true
};
(function() {
  var as = document.createElement('script');
  as.type = 'text/javascript';
  as.async = true;
  as.src = "https://d31qbv1cthcecs.cloudfront.net/atrk.js";
  var s = document.getElementsByTagName('script')[0];
  s.parentNode.insertBefore(as, s);
})();
(function(h, o, t, j, a, r) {
  h.hj = h.hj || function() {
    (h.hj.q = h.hj.q || []).push(arguments)
  };
  h._hjSettings = {
    hjid: 44320,
    hjsv: 5
  };
  a = o.getElementsByTagName('head')[0];
  r = o.createElement('script');
  r.async = 1;
  r.src = t + h._hjSettings.hjid + j + h._hjSettings.hjsv;
  a.appendChild(r);
})(window, document, '//static.hotjar.com/c/hotjar-', '.js?sv=');
(function(e, a) {
  if (!a.__SV) {
    var b = window;
    try {
      var c, l, i, j = b.location,
        g = j.hash;
      c = function(a, b) {
        return (l = a.match(RegExp(b + "=([^&]*)"))) ? l[1] : null
      };
      g && c(g, "state") && (i = JSON.parse(decodeURIComponent(c(g, "state"))), "mpeditor" === i.action && (b.sessionStorage.setItem("_mpcehash", g), history.replaceState(i.desiredHash || "", e.title, j.pathname + j.search)))
    } catch (m) {}
    var k, h;
    window.mixpanel = a;
    a._i = [];
    a.init = function(b, c, f) {
      function e(b, a) {
        var c = a.split(".");
        2 == c.length && (b = b[c[0]], a = c[1]);
        b[a] = function() {
          b.push([a].concat(Array.prototype.slice.call(arguments,
            0)))
        }
      }
      var d = a;
      "undefined" !== typeof f ? d = a[f] = [] : f = "mixpanel";
      d.people = d.people || [];
      d.toString = function(b) {
        var a = "mixpanel";
        "mixpanel" !== f && (a += "." + f);
        b || (a += " (stub)");
        return a
      };
      d.people.toString = function() {
        return d.toString(1) + ".people (stub)"
      };
      k =
        "disable time_event track track_pageview track_links track_forms register register_once alias unregister identify name_tag set_config reset people.set people.set_once people.increment people.append people.union people.track_charge people.clear_charges people.delete_user"
        .split(" ");
      for (h = 0; h < k.length; h++) e(d, k[h]);
      a._i.push([b, c, f])
    };
    a.__SV = 1.2;
    b = e.createElement("script");
    b.type = "text/javascript";
    b.async = !0;
    b.src = "undefined" !== typeof MIXPANEL_CUSTOM_LIB_URL ? MIXPANEL_CUSTOM_LIB_URL : "file:" === e.location.protocol && "//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js".match(/^\/\//) ? "https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js" :
      "//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js";
    c = e.getElementsByTagName("script")[0];
    c.parentNode.insertBefore(b, c)
  }
})(document, window.mixpanel || []);
mixpanel.init("490f1ac4c17537d5466bbb6a0dcd50d8");

! function(f, b, e, v, n, t, s) {
  if (f.fbq) return;
  n = f.fbq = function() {
    n.callMethod ?
      n.callMethod.apply(n, arguments) : n.queue.push(arguments)
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
}(window,
  document, 'script', '//connect.facebook.net/en_US/fbevents.js');

fbq('init', '536462993188270');
fbq('track', "PageView");
fbq('track', 'ViewContent');
fbq('track', 'Lead');
window.intercomSettings = {
  app_id: "xpc089go"
};
(function() {
  var w = window;
  var ic = w.Intercom;
  if (typeof ic === "function") {
    ic('reattach_activator');
    ic('update', intercomSettings);
  } else {
    var d = document;
    var i = function() {
      i.c(arguments)
    };
    i.q = [];
    i.c = function(args) {
      i.q.push(args)
    };
    w.Intercom = i;

    function l() {
      var s = d.createElement('script');
      s.type = 'text/javascript';
      s.async = true;
      s.src = 'https://widget.intercom.io/widget/xpc089go';
      var x = d.getElementsByTagName('script')[0];
      x.parentNode.insertBefore(s, x);
    }
    if (w.attachEvent) {
      w.attachEvent('onload', l);
    } else {
      w.addEventListener('load', l, false);
    }
  }
})()
