/* Seitenblick: only aggregate measurements, opt-in, memory-only state. */
(() => {
  "use strict";
  const node = document.getElementById("sbk-config");
  if (
    (!node && !window.SeitenblickConfig) ||
    navigator.doNotTrack === "1" ||
    navigator.globalPrivacyControl === true ||
    document.getElementById("wpadminbar")
  )
    return;
  let config;
  try {
    config = node ? JSON.parse(node.textContent) : window.SeitenblickConfig;
  } catch {
    return;
  }
  const routes = new Set(config.routes),
    endpoint = new URL(config.endpoint, location.href);
  if (endpoint.origin !== location.origin) return;
  let allowed = false,
    view = null,
    generation = 0,
    lastActivity = performance.now();
  let lastTick = performance.now(),
    wasActive = !document.hidden && document.hasFocus();
  const api = (name, data, keepalive = false) => {
    if (!navigator.onLine) return Promise.resolve(null);
    const url = new URL(endpoint);
    url.searchParams.set("rest_route", "/seitenblick/v1/" + name);
    return fetch(url, {
      method: "POST",
      credentials: "same-origin",
      cache: "no-store",
      mode: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
      keepalive,
    })
      .then((r) => (r.ok ? r.json() : null))
      .catch(() => null);
  };
  function path() {
    // Never transmit queries, searches, progress, certificate names or unknown fragments.
    const hash = location.hash.split("?")[0];
    let value =
      hash && hash !== "#/" && hash !== "#"
        ? location.pathname + hash
        : location.pathname;
    if (value === "/index.html") value = "/";
    return routes.has(value) ? value : null;
  }
  function sample() {
    const now = performance.now();
    if (allowed && view?.token && wasActive) {
      view.millis += Math.max(
        0,
        Math.min(now, lastActivity + 60000) - lastTick,
      );
      view.millis = Math.min(1800000, view.millis);
    }
    lastTick = now;
    wasActive = !document.hidden && document.hasFocus();
  }
  function send(target = view, keepalive = false) {
    if (!allowed || !target?.token || target.sending || target.expired) return;
    const seconds = Math.floor(target.millis / 1000);
    if (seconds === target.sentSeconds) return;
    // Cumulative counters are idempotent on replay; no more than 60 seconds per batch.
    const total = Math.min(seconds, target.sentSeconds + 60);
    const sequence = ++target.sequence;
    target.sending = true;
    api(
      "heartbeat",
      { token: target.token, seconds: total, sequence },
      keepalive,
    ).then((result) => {
      target.sending = false;
      if (result) target.sentSeconds = total;
    });
  }
  function startView() {
    sample();
    const next = path();
    if (view?.path === next) return;
    if (view) send(view, true);
    const stamp = ++generation;
    view = null;
    if (!allowed || !next || !navigator.onLine) return;
    const target = {
      path: next,
      token: null,
      millis: 0,
      sentSeconds: 0,
      sequence: 0,
      sending: false,
      expired: false,
      started: performance.now(),
    };
    view = target;
    api("open", { path: next, consent: config.version, adult: true }).then(
      (result) => {
        if (!result?.token) {
          if (view === target) view = null;
          return;
        }
        if (!allowed || stamp !== generation) {
          api("revoke", { token: result.token }, true);
          return;
        }
        target.token = result.token;
      },
    );
  }
  function consentChanged(event) {
    if (event.detail?.config) {
      config.statisticsAvailable = !!event.detail.config.statisticsAvailable;
      routes.clear();
      for (const route of event.detail.config.routes || []) routes.add(route);
    }
    const selected =
      event.detail?.saved &&
      event.detail?.statistics &&
      config.statisticsAvailable;
    if (selected && !allowed) {
      allowed = true;
      lastActivity = performance.now();
      lastTick = lastActivity;
      startView();
    } else if (!selected && allowed) {
      allowed = false;
      generation++;
      if (view?.token) api("revoke", { token: view.token }, true);
      view = null;
    }
  }
  window.addEventListener("seitenblick:consent", consentChanged);
  if (window.SeitenblickConsent)
    consentChanged({ detail: window.SeitenblickConsent.get() });
  for (const name of ["pointerdown", "keydown", "scroll", "touchstart"]) {
    window.addEventListener(
      name,
      (event) => {
        if (event.isTrusted) {
          sample();
          lastActivity = performance.now();
        }
      },
      { passive: true },
    );
  }
  window.addEventListener("hashchange", startView);
  window.addEventListener("popstate", startView);
  window.addEventListener("blur", () => {
    sample();
    send(view, true);
  });
  window.addEventListener("focus", () => {
    sample();
    wasActive = true;
    lastActivity = performance.now();
  });
  document.addEventListener("visibilitychange", () => {
    sample();
    if (document.hidden) send(view, true);
  });
  window.addEventListener("pagehide", () => {
    sample();
    send(view, true);
  });
  window.addEventListener("online", () => {
    if (allowed && !view) startView();
  });
  setInterval(() => {
    sample();
    if (view && performance.now() - view.started >= 1800000)
      view.expired = true;
  }, 2000);
  setInterval(() => send(), 20000);
})();
