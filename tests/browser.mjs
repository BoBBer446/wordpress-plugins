import assert from "node:assert/strict";
import fs from "node:fs";
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || "playwright");
const base = process.env.TEST_URL || "http://127.0.0.1:18124";
const hostname = new URL(base).hostname;
if (process.env.ALLOW_MUTATING_WP_TESTS !== "1" || !["127.0.0.1", "localhost"].includes(hostname)) {
  throw new Error("Diese schreibende Prüfung benötigt eine ausdrücklich freigegebene lokale Testinstallation.");
}
const password = process.env.SEITENBLICK_TEST_PASSWORD;
assert.ok(password, "Testpasswort fehlt.");
const output = "tests/evidence";
fs.mkdirSync(output, { recursive: true });
const browser = await chromium.launch({ headless: true });
let checks = 0;
const check = (value, label) => {
  assert.ok(value, label);
  checks++;
  console.log("PASS " + label);
};
const errors = [];
const bridge = async (context) => {
  await context.route(base + "/**", async (route) => {
    try {
      await route.fulfill({ response: await route.fetch({ maxRedirects: 0 }) });
    } catch {
      await route.abort().catch(() => {});
    }
  });
};
try {
  const guest = await browser.newContext({ viewport: { width: 1440, height: 1000 }, userAgent: "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/150.0.0.0 Safari/537.36", serviceWorkers: "block" });
  await bridge(guest);
  const page = await guest.newPage();
  page.on("pageerror", (error) => errors.push(error.message));
  const external = [];
  page.on("request", (request) => {
    if (new URL(request.url()).origin !== new URL(base).origin) external.push(request.url());
  });
  await page.goto(base + "/sample-page/");
  await page.locator(".sbk-consent").waitFor({ state: "visible" });
  check(await page.evaluate(() => !!window.SeitenblickConfig), "Standardtheme erhält Konfiguration über WordPress.");
  check((await guest.cookies()).length === 0, "Ohne Auswahl kein Einstellungs-Cookie.");
  check(await page.locator('[data-sbk-category="statistics"]').count() === 0, "Browsermessung bei neuer Installation ausgeschaltet.");
  check(await page.locator('[data-sbk-category="external"]').count() === 0, "Keine ungenutzten Videodienste angeboten.");
  check(external.length === 0, "Seitenblick lädt keine fremden Ressourcen.");
  check((await guest.request.get(base + "/?rest_route=/seitenblick/v1/stats")).status() === 403, "Statistik-API für Gäste gesperrt.");
  for (const width of [1440, 390, 320]) {
    await page.setViewportSize({ width, height: 1000 });
    check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), "Einwilligung ohne Überlauf " + width);
    await page.locator(".sbk-consent").screenshot({ path: output + "/consent-" + width + ".png" });
  }
  await page.locator(".sbk-consent-buttons button").first().click();
  await page.locator(".sbk-consent").waitFor({ state: "hidden" });
  check((await guest.cookies()).some((cookie) => cookie.httpOnly && cookie.sameSite === "Lax"), "Explizite Ablehnung wird als geschütztes Cookie gespeichert.");
  await guest.close();

  const admin = await browser.newContext({ viewport: { width: 1440, height: 1200 }, userAgent: "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/150.0.0.0 Safari/537.36", serviceWorkers: "block" });
  await admin.route("https://**", (route) => route.abort());
  await bridge(admin);
  const view = await admin.newPage();
  view.on("pageerror", (error) => errors.push(error.message));
  await view.goto(base + "/wp-login.php");
  await view.locator("#user_login").fill("review");
  await view.locator("#user_pass").fill(password);
  await view.locator("#wp-submit").click();
  await view.goto(base + "/wp-admin/admin.php?page=seitenblick", { waitUntil: "domcontentloaded" });
  await view.waitForFunction(() => document.querySelector("#sbk-status")?.textContent.includes("Aktuelle Statistik"));
  const links = await view.locator(".sbk-donate-button").getAttribute("href");
  check(links === "https://www.paypal.com/donate/?hosted_button_id=G72QV8CQAL6DY&locale.x=de_DE", "Freiwilliger Backend-Link mit deutscher PayPal-Seite.");
  await view.locator("#sbk-demo").click();
  check((await view.locator("#sbk-status").innerText()).includes("keine echten Zugriffszahlen"), "Beispieldaten klar gekennzeichnet.");
  check(await view.locator("#sbk-export").isHidden(), "Keine erfundenen Zahlen exportierbar.");
  for (const width of [1440, 390, 320]) {
    await view.setViewportSize({ width, height: 1200 });
    check(await view.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), "Dashboard ohne Überlauf " + width);
    await view.locator("#sbk-app").screenshot({ path: output + "/dashboard-" + width + ".png" });
  }
  await view.locator("#sbk-settings > summary").click();
  check(await view.locator('[name^="texts["]').count() === 25, "Alle 25 Textfelder vorhanden.");
  check(await view.locator('[name="services[youtube][enabled]"]').count() === 1, "Dienstfelder haben gültige HTML-Attribute.");
  check(await view.locator('[name="donation[enabled]"]').isChecked(), "Spendenhinweis ausblendbar.");
  await view.locator('[name="browser"]').check();
  await view.locator('[name="texts[title]"]').fill("Deine Auswahl auf der Testseite");
  await view.locator('[name="donation[title]"]').fill("Seitenblick freiwillig unterstützen");
  await view.getByRole("button", { name: "Einstellungen speichern", exact: true }).click();
  await view.waitForURL(/saved=1/);
  await view.waitForFunction(() => document.querySelector("#sbk-status")?.textContent.includes("Aktuelle Statistik"));
  await view.locator("#sbk-settings > summary").click();
  check(await view.locator('[name="browser"]').isChecked(), "Einstellungen auf Testinstallation gespeichert.");
  check(await view.locator('[name="texts[title]"]').inputValue() === "Deine Auswahl auf der Testseite", "Bearbeiteter Text bleibt erhalten.");

  const measured = await browser.newContext({ viewport: { width: 1200, height: 900 }, userAgent: "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/150.0.0.0 Safari/537.36", serviceWorkers: "block" });
  await bridge(measured);
  const reader = await measured.newPage();
  reader.on("pageerror", (error) => errors.push(error.message));
  const opens = [];
  reader.on("response", (response) => {
    if (decodeURIComponent(response.url()).includes("/seitenblick/v1/open")) opens.push(response.status());
  });
  await reader.goto(base + "/sample-page/");
  await reader.locator(".sbk-consent").waitFor({ state: "visible" });
  check(await reader.locator(".sbk-consent strong").first().innerText() === "Deine Auswahl auf der Testseite", "Öffentliche Auswahl verwendet den gespeicherten Text.");
  await reader.locator('[data-sbk-category="statistics"]').check();
  await reader.locator(".sbk-consent-buttons button").last().click();
  check((await reader.locator(".sbk-consent-status").innerText()).includes("Bitte bestätige"), "Optionale Freigabe verlangt die vorgesehene Bestätigung.");
  await reader.locator(".sbk-consent-adult input").check();
  await reader.locator(".sbk-consent-buttons button").last().click();
  await reader.locator(".sbk-consent").waitFor({ state: "hidden" });
  await reader.bringToFront();
  await reader.mouse.click(700, 400);
  await reader.waitForTimeout(23000);
  check(opens.some((status) => status === 200), "Echte Browseransicht nach Einwilligung eröffnet.");
  check(await reader.evaluate(() => !Object.keys(localStorage).some((key) => key.includes("sbk") || key.includes("seitenblick"))), "Keine Statistikdaten im Browser-Lokalspeicher.");
  await reader.locator(".sbk-choice").click();
  await reader.locator(".sbk-consent-buttons button").first().click();
  await reader.locator(".sbk-consent").waitFor({ state: "hidden" });
  check(await reader.evaluate(() => !window.SeitenblickConsent.get().statistics), "Widerruf stoppt die Browsermessung.");
  await measured.close();

  await view.reload({ waitUntil: "domcontentloaded" });
  await view.waitForFunction(() => document.querySelector("#sbk-status")?.textContent.includes("Aktuelle Statistik"));
  check(Number((await view.locator("#sbk-browser").innerText()).replace(/\D/g, "")) >= 1, "Dashboard zeigt den tatsächlich gemessenen Aufruf.");
  check((await view.locator("#sbk-timed").innerText()).startsWith("1"), "Aktive Zeit tatsächlich übertragen.");
  const logout = await view.locator("#wp-admin-bar-logout a").getAttribute("href");
  if (logout) await admin.request.get(logout);
  await admin.unrouteAll({ behavior: "ignoreErrors" });
  await admin.close();
  check(errors.filter((error) => !error.includes("ViewTransition opt-in disabled")).length === 0, "Keine JavaScriptfehler.");
  console.log("PASS " + checks + " Browserprüfungen auf isolierter Testinstallation.");
} finally {
  await browser.close();
}
