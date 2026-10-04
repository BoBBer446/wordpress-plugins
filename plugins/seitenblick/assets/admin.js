(() => {
  "use strict";
  const t = (value) => seitenblickAdmin.strings?.[value] || value;
  const app = document.getElementById("sbk-app");
  if (!app || typeof seitenblickAdmin === "undefined") return;
  const $ = (id) => document.getElementById(id),
    nf = new Intl.NumberFormat("de-DE");
  const num = (value) => Math.max(0, Number(value) || 0);
  const fmt = (value) => nf.format(Math.round(num(value)));
  const time = (value) =>
    value == null
      ? "—"
      : num(value) < 60
        ? fmt(value) + " s"
        : num(value) < 3600
          ? Math.floor(num(value) / 60) +
            " min " +
            Math.round(num(value) % 60) +
            " s"
          : Math.floor(num(value) / 3600) +
            " h " +
            Math.floor((num(value) % 3600) / 60) +
            " min";
  const text = (id, value) => {
    $(id).textContent = value;
  };
  const element = (tag, content, className) => {
    const el = document.createElement(tag);
    if (content != null) el.textContent = content;
    if (className) el.className = className;
    return el;
  };
  const colors = ["#8171df", "#75b8e0", "#ffa975", "#78c6a6", "#b1a6eb"];
  let days = 7,
    report = null,
    demo = false,
    requestGeneration = 0;
  const dateLabel = (value) =>
    new Date(value + "T12:00:00Z").toLocaleDateString("de-DE", {
      day: "2-digit",
      month: "2-digit",
    });
  const svgElement = (tag, attrs, content) => {
    const el = document.createElementNS("http://www.w3.org/2000/svg", tag);
    for (const [k, v] of Object.entries(attrs || {}))
      el.setAttribute(k, String(v));
    if (content != null) el.textContent = content;
    return el;
  };
  function dailyValues(r) {
    const byDay = new Map(r.daily.map((row) => [row.day, row])),
      rows = [],
      date = new Date(r.start + "T00:00:00Z");
    for (let i = 0; i < r.days; i++) {
      const day = date.toISOString().slice(0, 10);
      rows.push({ day, server_views: 0, browser_views: 0, ...byDay.get(day) });
      date.setUTCDate(date.getUTCDate() + 1);
    }
    return rows;
  }
  function chart(r) {
    const data = dailyValues(r),
      box = $("sbk-chart");
    box.replaceChildren();
    const W = 680,
      H = 235,
      left = 40,
      right = 16,
      top = 15,
      bottom = 30,
      max = Math.max(4, ...data.map((d) => num(d.server_views)));
    const svg = svgElement("svg", {
      viewBox: "0 0 " + W + " " + H,
      "aria-hidden": "true",
    });
    for (let i = 0; i <= 4; i++) {
      const y = top + ((H - top - bottom) * i) / 4;
      svg.append(
        svgElement("line", {
          x1: left,
          y1: y,
          x2: W - right,
          y2: y,
          class: "sbk-gridline",
        }),
      );
      svg.append(
        svgElement(
          "text",
          {
            x: left - 8,
            y: y + 4,
            "text-anchor": "end",
            class: "sbk-chart-label",
          },
          fmt(max * (1 - i / 4)),
        ),
      );
    }
    const points = data.map((d, i) => [
      left + ((W - left - right) * i) / Math.max(1, data.length - 1),
      H - bottom - ((H - top - bottom) * num(d.server_views)) / max,
    ]);
    svg.append(
      svgElement("path", {
        d:
          "M " +
          points[0][0] +
          " " +
          (H - bottom) +
          " L " +
          points.map((p) => p.join(" ")).join(" L ") +
          " L " +
          points.at(-1)[0] +
          " " +
          (H - bottom) +
          " Z",
        class: "sbk-chart-area",
      }),
    );
    svg.append(
      svgElement("path", {
        d: "M " + points.map((p) => p.join(" ")).join(" L "),
        class: "sbk-chart-line",
      }),
    );
    const skip = Math.max(1, Math.ceil(data.length / 7));
    for (let i = 0; i < data.length; i++) {
      if (i % skip === 0 || i === data.length - 1) {
        const dot = svgElement("circle", {
          cx: points[i][0],
          cy: points[i][1],
          r: 4,
          fill: "#fff",
          stroke: "#7564e1",
          "stroke-width": 2,
        });
        dot.append(
          svgElement(
            "title",
            {},
            dateLabel(data[i].day) +
              ": " +
              fmt(data[i].server_views) +
              " Aufrufe",
          ),
        );
        svg.append(dot);
        svg.append(
          svgElement(
            "text",
            {
              x: points[i][0],
              y: H - 7,
              "text-anchor": "middle",
              class: "sbk-chart-label",
            },
            dateLabel(data[i].day),
          ),
        );
      }
    }
    box.append(svg);
    box.setAttribute(
      "aria-label",
      fmt(r.current.server_views) +
        t(" Seitenaufrufe vom ") +
        dateLabel(r.start) +
        " bis " +
        dateLabel(r.end) +
        t(". Tageswerte stehen in der Tabelle."),
    );
    const body = $("sbk-daily");
    body.replaceChildren();
    for (const row of data) {
      const tr = element("tr");
      for (const value of [
        row.day,
        fmt(row.server_views),
        fmt(row.browser_views),
      ])
        tr.append(element("td", value));
      body.append(tr);
    }
  }
  function breakdown(id, items, donut = false) {
    const root = $(id);
    root.replaceChildren();
    const total = items.reduce((sum, item) => sum + num(item.value), 0),
      angles = [];
    let offset = 0;
    for (const [i, item] of items.entries()) {
      const value = num(item.value),
        percent = total ? (value / total) * 100 : 0;
      const row = element("div", null, "sbk-breakdown"),
        label = element("span", null, "sbk-breakdown-label"),
        dot = element("i", null, "sbk-dot");
      dot.style.background = colors[i];
      label.append(dot, document.createTextNode(item.label));
      const number = element("span");
      number.append(
        element("strong", fmt(value)),
        element("small", Math.round(percent) + " %"),
      );
      row.append(label, number);
      root.append(row);
      if (!donut) {
        const track = element("div", null, "sbk-source-track"),
          bar = element("i");
        bar.style.width = percent + "%";
        bar.style.background = colors[i];
        track.append(bar);
        root.append(track);
      }
      angles.push(colors[i] + " " + offset + "% " + (offset + percent) + "%");
      offset += percent;
    }
    if (donut)
      $("sbk-donut").style.background = total
        ? "conic-gradient(" + angles.join(",") + ")"
        : "#edeef4";
  }
  function pages() {
    if (!report) return;
    const search = $("sbk-search").value.trim().toLocaleLowerCase("de-DE");
    const filtered = report.pages.filter((p) =>
      (p.title + " " + p.path).toLocaleLowerCase("de-DE").includes(search),
    );
    const root = $("sbk-pages");
    root.replaceChildren();
    for (const page of filtered) {
      const tr = element("tr"),
        td = element("td"),
        title = element("span", page.title, "sbk-page-title"),
        tag = element("span", page.group, "sbk-tag");
      title.append(tag);
      td.append(title, element("span", page.path, "sbk-page-path"));
      tr.append(td);
      for (const value of [
        fmt(page.server_views),
        fmt(page.browser_views),
        time(page.average),
        time(page.active_seconds),
      ])
        tr.append(element("td", value));
      root.append(tr);
    }
    if (!filtered.length) {
      const tr = element("tr"),
        td = element(
          "td",
          search
            ? t("Keine passende Seite.")
            : t(
                "Noch keine Messwerte. Die Statistik beginnt mit neuen Aufrufen.",
              ),
          "sbk-empty",
        );
      td.colSpan = 5;
      tr.append(td);
      root.append(tr);
    }
    text(
      "sbk-page-count",
      fmt(filtered.length) +
        t(" Seiten · maximal 1.000 Seiten im gewählten Zeitraum."),
    );
  }
  function hours(r) {
    const sums = Array(24).fill(0);
    for (const row of r.hours) {
      const h = Number(row.hour);
      if (Number.isInteger(h) && h >= 0 && h < 24) sums[h] += num(row.views);
    }
    const max = Math.max(1, ...sums),
      root = $("sbk-hours");
    root.replaceChildren();
    for (let h = 0; h < 24; h++) {
      const bar = element("div", null, "sbk-hour");
      bar.style.height = Math.max(3, (sums[h] / max) * 122) + "px";
      bar.title = h + ":00–" + h + ":59 · " + fmt(sums[h]) + " Aufrufe";
      bar.setAttribute("aria-label", bar.title);
      bar.tabIndex = 0;
      if (h % 3 === 0) bar.append(element("span", String(h).padStart(2, "0")));
      root.append(bar);
    }
    root.setAttribute(
      "aria-label",
      t("Stündliche Seitenaufrufe. Jeder Balken zeigt Uhrzeit und Summe."),
    );
  }
  function render(r) {
    report = r;
    const c = r.current,
      p = r.previous;
    text("sbk-views", fmt(c.server_views));
    text("sbk-browser", fmt(c.browser_views));
    text(
      "sbk-average",
      time(c.timed_views ? num(c.active_seconds) / num(c.timed_views) : null),
    );
    text("sbk-timed", fmt(c.timed_views) + t(" Ansichten mit Zeitmessung"));
    text(
      "sbk-engaged",
      num(c.browser_views)
        ? Math.round((num(c.engaged_views) / num(c.browser_views)) * 100) + " %"
        : "—",
    );
    const full =
      demo ||
      (r.oldest &&
        r.oldest <= r.previousStart &&
        new Date(r.started) <= new Date(r.previousStart + "T00:00:00Z"));
    text(
      "sbk-change",
      !full
        ? t("Noch kein vollständiger Vergleich")
        : num(p.server_views)
          ? ((num(c.server_views) - num(p.server_views)) /
              num(p.server_views) >=
            0
              ? "+"
              : "") +
            Math.round(
              ((num(c.server_views) - num(p.server_views)) /
                num(p.server_views)) *
                100,
            ) +
            t(" % zum Vorzeitraum")
          : t("Vorzeitraum ohne Aufrufe"),
    );
    text(
      "sbk-range",
      r.start +
        " bis " +
        r.end +
        t(" · laufender Tag enthalten; Vergleich ") +
        r.previousStart +
        " bis " +
        r.previousEnd +
        ".",
    );
    text(
      "sbk-timezone",
      t("Stunden nach ") +
        r.timezone +
        t(" · über den gewählten Zeitraum zusammengefasst."),
    );
    text(
      "sbk-retention",
      "Aufbewahrung: " +
        r.settings.retention +
        " Tage · Zeitmessung " +
        (r.settings.browser ? t("mit Einwilligung aktiv") : "ausgeschaltet") +
        t(" · allgemeine Zählung ") +
        (r.settings.enabled ? "aktiv" : "pausiert") +
        ".",
    );
    text(
      "sbk-status",
      demo
        ? t("Vorschau mit Beispieldaten · keine echten Zugriffszahlen")
        : (r.settings.enabled
            ? t("Aktuelle Statistik")
            : t("Messung pausiert")) +
            " · aktualisiert " +
            new Date(r.generated).toLocaleTimeString("de-DE", {
              hour: "2-digit",
              minute: "2-digit",
            }) +
            t(" · keine Zählung einzelner Besucher"),
    );
    chart(r);
    pages();
    hours(r);
    breakdown(
      "sbk-devices",
      [
        { label: t("Desktop"), value: c.desktop },
        { label: "Handy / Tablet", value: c.mobile },
        { label: "Andere / unbekannt", value: c.other_device },
      ],
      true,
    );
    breakdown("sbk-sources", [
      { label: "Direkt / unbekannt", value: c.direct },
      { label: "Suchmaschinen", value: c.search_source },
      { label: t("Soziale Netzwerke"), value: c.social },
      { label: t("Innerhalb der Website"), value: c.internal_source },
      { label: t("Andere"), value: c.other_source },
    ]);
    $("sbk-demo-note").hidden = !demo;
    $("sbk-demo").textContent = demo
      ? t("Echte Statistik ansehen")
      : t("Beispieldaten ansehen");
    $("sbk-export").hidden = demo;
  }
  function examples() {
    const today = new Date().toISOString().slice(0, 10),
      date = new Date(today + "T00:00:00Z");
    date.setUTCDate(date.getUTCDate() - (days - 1));
    const start = date.toISOString().slice(0, 10),
      previous = new Date(date);
    previous.setUTCDate(previous.getUTCDate() - days);
    const previousStart = previous.toISOString().slice(0, 10);
    previous.setUTCDate(previous.getUTCDate() + days - 1);
    const daily = [];
    let sum = 0;
    for (let i = 0; i < days; i++) {
      const views = Math.round(215 + (i % 7) * 38 + Math.sin(i * 0.9) * 72);
      sum += views;
      daily.push({
        day: date.toISOString().slice(0, 10),
        server_views: views,
        browser_views: Math.round(views * 0.58),
      });
      date.setUTCDate(date.getUTCDate() + 1);
    }
    const current = {
      server_views: sum,
      browser_views: Math.round(sum * 0.58),
      timed_views: Math.round(sum * 0.5),
      active_seconds: Math.round(sum * 0.5 * 82),
      engaged_views: Math.round(sum * 0.36),
      desktop: Math.round(sum * 0.31),
      mobile: Math.round(sum * 0.65),
      other_device: Math.round(sum * 0.04),
      direct: Math.round(sum * 0.45),
      search_source: Math.round(sum * 0.29),
      social: Math.round(sum * 0.12),
      internal_source: Math.round(sum * 0.1),
      other_source: Math.round(sum * 0.04),
    };
    const names = [
      [t("Entdecken"), "/", t("Startseite")],
      [t("Amsel"), "/#/art/amsel", t("Lernkarte")],
      [t("Waldtiere"), "/#/thema/animals", t("Lernthema")],
      [t("Lernbuch"), "/#/lexikon", t("App")],
      [t("Eiche"), "/#/art/eiche", t("Lernkarte")],
      [t("Wald-Schein Prüfung"), "/#/waldschein", t("App")],
      [t("Knoten"), "/#/thema/knots", t("Lernthema")],
    ];
    const pages = names.map(([title, path, group], i) => ({
      title,
      path,
      group,
      server_views: i === 0 ? sum : 0,
      browser_views: Math.round(current.browser_views / (i + 2)),
      timed_views: Math.round(current.timed_views / (i + 2)),
      average: 52 + i * 17,
      active_seconds: Math.round(current.timed_views / (i + 2)) * (52 + i * 17),
    }));
    const hours = Array.from({ length: 24 }, (_, i) => ({
      day: today,
      hour: i,
      views:
        i < 7
          ? 5 + i * 2
          : Math.round(22 + Math.sin(((i - 7) / 16) * Math.PI) * 83),
    }));
    return {
      days,
      start,
      end: today,
      previousStart,
      previousEnd: previous.toISOString().slice(0, 10),
      current,
      previous: { server_views: Math.round(sum * 0.81) },
      daily,
      pages,
      hours,
      oldest: previousStart,
      started: previousStart,
      settings: { retention: 90, browser: true, enabled: true },
      timezone: "Europe/Berlin",
      generated: new Date().toISOString(),
    };
  }
  async function load() {
    const generation = ++requestGeneration;
    if (demo) {
      render(examples());
      return;
    }
    text("sbk-status", t("Statistik wird geladen …"));
    try {
      const url = new URL(seitenblickAdmin.url);
      url.searchParams.set("days", days);
      const response = await fetch(url, {
        credentials: "same-origin",
        cache: "no-store",
        headers: { "X-WP-Nonce": seitenblickAdmin.nonce },
      });
      if (!response.ok)
        throw new Error(
          response.status === 403
            ? t("Bitte neu anmelden; die Sitzung ist abgelaufen.")
            : t("Statistik derzeit nicht erreichbar."),
        );
      const data = await response.json();
      if (generation !== requestGeneration) return;
      render(data);
    } catch (error) {
      if (generation === requestGeneration)
        text(
          "sbk-status",
          error.message || t("Statistik konnte nicht geladen werden."),
        );
    }
  }
  for (const button of app.querySelectorAll("[data-sbk-days]"))
    button.addEventListener("click", () => {
      days = Number(button.dataset.sbkDays);
      for (const b of app.querySelectorAll("[data-sbk-days]"))
        b.setAttribute("aria-pressed", String(b === button));
      const url = new URL(seitenblickAdmin.exportUrl);
      url.searchParams.set("days", days);
      $("sbk-export").href = url.href;
      load();
    });
  $("sbk-search").addEventListener("input", pages);
  $("sbk-demo").addEventListener("click", () => {
    demo = !demo;
    load();
  });
  setInterval(() => {
    if (!document.hidden && !demo) load();
  }, 60000);
  load();
})();
