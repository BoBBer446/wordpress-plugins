/* Preference cookie is signed, HttpOnly and set only after an explicit choice. */
(async () => {
  "use strict";
  const node = document.getElementById("sbk-config");
  if (!node && !window.SeitenblickConfig) return;
  let config;
  try {
    config = node ? JSON.parse(node.textContent) : window.SeitenblickConfig;
  } catch {
    return;
  }
  const endpoint = new URL(config.endpoint, location.href);
  if (endpoint.origin !== location.origin) return;
  let state = {
    saved: false,
    choices: { statistics: false, external: false, marketing: false },
    adult: false,
  };
  let busy = false,
    pending = null;
  const loadedScripts = new Set();
  const api = (data) => {
    if (!navigator.onLine) return Promise.resolve(null);
    const url = new URL(endpoint);
    url.searchParams.set("rest_route", "/seitenblick/v1/consent");
    const controller = new AbortController(),
      timeout = setTimeout(() => controller.abort(), 5000);
    return fetch(url, {
      method: "POST",
      signal: controller.signal,
      credentials: "same-origin",
      mode: "same-origin",
      cache: "no-store",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    })
      .then((r) => (r.ok ? r.json() : null))
      .catch(() => null)
      .finally(() => clearTimeout(timeout));
  };
  const initial = await api({ action: "read" });
  if (initial?.config) {
    Object.assign(config, initial.config);
  }
  const t = (key, fallback) =>
    typeof config.texts?.[key] === "string" && config.texts[key]
      ? config.texts[key]
      : fallback;
  const el = (tag, text, className) => {
    const x = document.createElement(tag);
    if (text) x.textContent = text;
    if (className) x.className = className;
    return x;
  };
  const panel = el("aside", null, "sbk-consent");
  panel.hidden = true;
  panel.setAttribute("role", "dialog");
  panel.setAttribute("aria-label", t("reopen", "Datenschutz-Auswahl"));
  panel.setAttribute("aria-modal", "false");
  panel.append(el("strong", t("title", "Deine Datenschutz-Auswahl")));
  panel.append(
    el(
      "p",
      t(
        "intro",
        "Notwendige Funktionen halten die Website nutzbar. Zusätzliche Statistik und externe Medien sind freiwillig. Du kannst auch mit „Nur notwendige“ uneingeschränkt weitermachen.",
      ),
    ),
  );
  const content = el("div", null, "sbk-consent-content");
  panel.append(content);
  const list = el("div", null, "sbk-consent-categories");
  const inputs = {};
  function category(key, label, description, necessary = false) {
    const box = el("div", null, "sbk-consent-category"),
      heading = el("label"),
      input = el("input");
    input.type = "checkbox";
    input.checked = necessary;
    input.disabled = necessary;
    input.dataset.sbkCategory = key;
    heading.append(input, el("span", label));
    box.append(heading, el("p", description, "sbk-consent-description"));
    list.append(box);
    inputs[key] = input;
  }
  category(
    "necessary",
    t("necessary_label", "Notwendige Funktionen"),
    t(
      "necessary_description",
      "Anmeldung, von dir gewünschte Funktionen und das Merken deiner Datenschutz-Auswahl. Das Einstellungs-Cookie gilt bis zu 90 Tage und enthält keine Besucherkennung.",
    ),
    true,
  );
  if (config.statisticsAvailable) {
    category(
      "statistics",
      t("statistics_label", "Statistik"),
      t(
        "statistics_description",
        "Seiten und aktive Zeit werden zusammengefasst. Keine Statistik-Cookies, keine gespeicherten IP-Adressen, keine Besucherprofile. Messbelege verfallen nach 30 Minuten.",
      ),
    );
  }
  const services = Object.entries(config.services || {});
  if (services.some(([, s]) => s.category === "external"))
    category(
      "external",
      t("external_label", "Externe Medien"),
      t(
        "external_description",
        "Eingebundene Videos und registrierte Fremdinhalte laden erst nach deiner Freigabe. Dabei können Anbieter Daten erhalten und eigene Cookies setzen.",
      ),
    );
  if (services.some(([, s]) => s.category === "marketing"))
    category(
      "marketing",
      t("marketing_label", "Weitere registrierte Fremddienste"),
      t(
        "marketing_description",
        "Diese Dienste können eigene Kennungen und Cookies verwenden. Einzelheiten findest du in der Diensteliste und in den Datenschutzhinweisen.",
      ),
    );
  content.append(list);
  if (services.length) {
    const details = el("details"),
      summary = el(
        "summary",
        t("services_label", "Angebundene Dienste ansehen"),
      );
    details.append(summary);
    for (const [id, s] of services) {
      const p = el("p");
      p.append(
        el("b", s.label),
        document.createTextNode(" · " + s.description),
      );
      if (s.privacy) {
        try {
          const url = new URL(s.privacy);
          if (url.protocol === "https:") {
            const a = el(
              "a",
              " " + t("provider_privacy", "Datenschutz beim Anbieter"),
            );
            a.href = url.href;
            a.target = "_blank";
            a.rel = "noopener noreferrer";
            p.append(a);
          }
        } catch {}
      }
      details.append(p);
    }
    content.append(details);
  }
  const adultLabel = el("label", null, "sbk-consent-adult"),
    adult = el("input");
  adult.type = "checkbox";
  adultLabel.append(
    adult,
    document.createTextNode(
      t(
        "adult",
        " Ich bin mindestens 16 Jahre alt oder sorgeberechtigt. Für freiwillige Freigaben erforderlich.",
      ),
    ),
  );
  content.append(adultLabel);
  content.append(
    el(
      "p",
      t(
        "basic",
        "Allgemeine Serveraufrufe werden ohne Besucherprofil als Summen gezählt. IP-Adressen werden zur technischen Übertragung verarbeitet; die Statistik speichert sie nicht.",
      ),
      "sbk-consent-basic",
    ),
  );
  const buttons = el("div", null, "sbk-consent-buttons"),
    necessary = el("button", t("reject", "Nur notwendige")),
    save = el("button", t("save", "Auswahl speichern"));
  necessary.type = save.type = "button";
  buttons.append(necessary, save);
  panel.append(buttons);
  const status = el("p", null, "sbk-consent-status");
  status.setAttribute("role", "status");
  status.setAttribute("aria-live", "polite");
  panel.append(status);
  const privacy = el("a", t("privacy", "Datenschutzhinweise"));
  privacy.href = config.privacy;
  panel.append(privacy);
  const choice = el("button", t("reopen", "Datenschutz-Auswahl"), "sbk-choice");
  choice.type = "button";
  choice.setAttribute("aria-expanded", "false");
  document.body.append(panel, choice);
  const emit = () =>
    window.dispatchEvent(
      new CustomEvent("seitenblick:consent", {
        detail: {
          ...state.choices,
          saved: state.saved,
          config: {
            statisticsAvailable: config.statisticsAvailable,
            routes: config.routes,
          },
        },
      }),
    );
  function permitted(id) {
    const service = config.services?.[id];
    return !!(service && state.saved && state.choices[service.category]);
  }
  function loadAllowed() {
    for (const script of document.querySelectorAll(
      'script[type="application/x-seitenblick"][data-sbk-service]',
    )) {
      const id = script.dataset.sbkService;
      if (!permitted(id) || script.dataset.sbkLoaded) continue;
      let src;
      try {
        src = new URL(script.dataset.sbkSrc, location.href);
      } catch {
        continue;
      }
      if (src.protocol !== "https:" && src.origin !== location.origin) continue;
      const live = document.createElement("script");
      live.src = src.href;
      live.async = false;
      for (const attr of [
        "id",
        "type",
        "nonce",
        "integrity",
        "crossorigin",
        "referrerpolicy",
      ]) {
        const v = script.dataset["sbk" + attr[0].toUpperCase() + attr.slice(1)];
        if (v) live.setAttribute(attr, v);
      }
      script.dataset.sbkLoaded = "true";
      loadedScripts.add(id);
      script.after(live);
    }
    for (const holder of document.querySelectorAll(
      "template[data-sbk-style]",
    )) {
      const id = holder.dataset.sbkService;
      if (!permitted(id) || holder.dataset.sbkLoaded) continue;
      let href;
      try {
        href = new URL(holder.dataset.sbkSrc, location.href);
      } catch {
        continue;
      }
      if (href.protocol !== "https:") continue;
      const link = document.createElement("link");
      link.rel = "stylesheet";
      link.href = href.href;
      for (const attr of [
        "media",
        "nonce",
        "integrity",
        "crossorigin",
        "referrerpolicy",
      ]) {
        const v = holder.dataset["sbk" + attr[0].toUpperCase() + attr.slice(1)];
        if (v) link.setAttribute(attr, v);
      }
      holder.dataset.sbkLoaded = "true";
      loadedScripts.add(id);
      holder.after(link);
    }
    for (const box of document.querySelectorAll(
      ".sbk-embed[data-sbk-service]",
    )) {
      const id = box.dataset.sbkService;
      if (!permitted(id)) {
        box.querySelector("iframe")?.remove();
        for (const child of box.children)
          if (child.tagName !== "IFRAME") child.hidden = false;
        continue;
      }
      if (box.querySelector("iframe")) continue;
      let url;
      try {
        url = new URL(box.dataset.sbkSrc);
      } catch {
        continue;
      }
      const valid =
        (id === "youtube" &&
          url.origin === "https://www.youtube-nocookie.com" &&
          /^\/embed\/[A-Za-z0-9_-]{11}$/.test(url.pathname)) ||
        (id === "vimeo" &&
          url.origin === "https://player.vimeo.com" &&
          /^\/video\/[0-9]{1,12}$/.test(url.pathname));
      if (!valid || url.search || url.hash) continue;
      const frame = document.createElement("iframe");
      frame.src = url.href;
      frame.title = box.dataset.sbkTitle || "Video";
      frame.referrerPolicy = "no-referrer";
      frame.loading = "lazy";
      frame.allow = "fullscreen";
      frame.setAttribute("allowfullscreen", "");
      frame.setAttribute(
        "sandbox",
        "allow-scripts allow-same-origin allow-presentation",
      );
      frame.className = "sbk-video";
      for (const child of box.children) child.hidden = true;
      box.append(frame);
    }
  }
  function open() {
    for (const [key, input] of Object.entries(inputs))
      if (key !== "necessary") input.checked = !!state.choices[key];
    adult.checked = !!state.adult;
    panel.hidden = false;
    choice.setAttribute("aria-expanded", "true");
    status.textContent = "";
  }
  function close() {
    panel.hidden = true;
    choice.setAttribute("aria-expanded", "false");
  }
  async function store(flags, adultConfirmed) {
    if (busy) return;
    if (Object.values(flags).some(Boolean) && !adultConfirmed) {
      status.textContent = t(
        "adult_error",
        "Bitte bestätige die Alters- oder Sorgeberechtigung für freiwillige Freigaben.",
      );
      adult.focus();
      return;
    }
    // A withdrawal takes effect locally before its persistence request succeeds.
    const reduced = { ...state.choices };
    let withdrawing = false;
    for (const key of ["statistics", "external", "marketing"])
      if (state.choices[key] && !flags[key]) {
        reduced[key] = false;
        withdrawing = true;
      }
    if (withdrawing) {
      state = { ...state, choices: reduced };
      emit();
      loadAllowed();
    }
    pending = { flags, adultConfirmed };
    busy = true;
    save.disabled = necessary.disabled = true;
    status.textContent = t("saving", "Auswahl wird gespeichert …");
    const result = await api({
      action: "save",
      choices: flags,
      adult: adultConfirmed,
      policy: config.policy,
    });
    busy = false;
    save.disabled = necessary.disabled = false;
    if (!result?.saved) {
      status.textContent = t(
        "save_error",
        "Die Auswahl konnte nicht gespeichert werden. Ohne bestätigte Freigabe bleiben neue optionale Dienste gesperrt. Bitte versuche es mit einer Internetverbindung erneut.",
      );
      return;
    }
    const revokeScript = [...loadedScripts].some((id) => {
      const cat = config.services[id]?.category;
      return cat && !result.choices[cat];
    });
    state = result;
    pending = null;
    emit();
    loadAllowed();
    close();
    choice.focus();
    // Executed third-party code cannot be safely unloaded. Reload after its withdrawal.
    if (revokeScript) location.reload();
  }
  necessary.addEventListener("click", () =>
    store({ statistics: false, external: false, marketing: false }, false),
  );
  save.addEventListener("click", () =>
    store(
      {
        statistics: !!inputs.statistics?.checked,
        external: !!inputs.external?.checked,
        marketing: !!inputs.marketing?.checked,
      },
      adult.checked,
    ),
  );
  choice.addEventListener("click", () => (panel.hidden ? open() : close()));
  document.addEventListener("click", (event) => {
    if (event.target.closest("[data-sbk-open-consent]")) open();
  });
  window.addEventListener("hashchange", loadAllowed);
  window.addEventListener("online", async () => {
    if (pending) {
      await store(pending.flags, pending.adultConfirmed);
      return;
    }
    if (!state.saved) {
      const result = await api({ action: "read" });
      if (result?.saved) {
        state = result;
        emit();
        loadAllowed();
        close();
      }
    }
  });
  window.SeitenblickConsent = Object.freeze({
    open,
    get: () => ({
      ...state.choices,
      saved: state.saved,
      config: {
        statisticsAvailable: config.statisticsAvailable,
        routes: config.routes,
      },
    }),
    refreshEmbeds: loadAllowed,
  });
  if (initial?.saved) {
    state = initial;
    emit();
    loadAllowed();
  } else open();
})();
