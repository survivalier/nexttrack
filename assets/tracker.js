/**
 * Script de tracking à inclure sur les pages du site à analyser :
 *
 *   <script>window.TRACKER_ENDPOINT = "https://mon-domaine.com/track.php";</script>
 *   <script src="https://mon-domaine.com/assets/tracker.js" defer></script>
 *
 * Si TRACKER_ENDPOINT n'est pas défini, "track.php" (chemin relatif) est utilisé.
 */
(function () {
  "use strict";

  var endpoint = window.TRACKER_ENDPOINT || "track.php";

  function getUtmParams() {
    var params = new URLSearchParams(window.location.search);
    var utm = {};
    ["utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content"].forEach(function (key) {
      var val = params.get(key);
      if (val) utm[key] = val;
    });
    return utm;
  }

  function collect() {
    var data = {
      path: window.location.pathname,
      url: window.location.href,
      referrer: document.referrer || "",
      lang: navigator.language || "",
      screen: screen.width + "x" + screen.height,
    };
    Object.assign(data, getUtmParams());
    return data;
  }

  function send(data) {
    var payload = JSON.stringify(data);

    if (navigator.sendBeacon) {
      var blob = new Blob([payload], { type: "application/json" });
      navigator.sendBeacon(endpoint, blob);
      return;
    }

    fetch(endpoint, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: payload,
      keepalive: true,
    }).catch(function () {
      /* échec silencieux : ne doit jamais casser le site tracké */
    });
  }

  send(collect());
})();