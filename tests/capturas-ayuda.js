// Genera las capturas (assets/img/ayuda) y los videos (assets/video/ayuda) de la página de Ayuda
// contra un servidor de PRUEBA en http://127.0.0.1:8100 (ver tests/README.md). Requiere Playwright y ffmpeg.
//   node tests/capturas-ayuda.js
const { chromium } = require('playwright');
const B = 'http://127.0.0.1:8100/';
const OUT = '/home/claude/repo-casza/assets/img/ayuda/';
const VID = '/tmp/claude-0/sp/video/';
const tile = (r) => r.fulfill({ status: 200, contentType: 'image/png', body: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==', 'base64') });
const ruta = { code: 'Ok', routes: [{ distance: 1430, duration: 1050, geometry: { coordinates: [[-59.35937,-26.31685],[-59.3625,-26.3135],[-59.3655,-26.3105],[-59.36795,-26.30876]] }, legs: [{ steps: [
  { maneuver: { type: 'depart', bearing_after: 300 }, name: 'Av. San Martín', distance: 420 }, { maneuver: { type: 'turn', modifier: 'right' }, name: 'Tucumán', distance: 500 },
  { maneuver: { type: 'turn', modifier: 'left' }, name: 'Santa Fe', distance: 510 }, { maneuver: { type: 'arrive' }, name: 'Santa Fe', distance: 0 } ] }] }] };
const slow = async (p, ms = 700) => p.waitForTimeout(ms);
(async () => {
  const b = await chromium.launch();
  const mk = async (video) => {
    const c = await b.newContext({ viewport: { width: 420, height: 760 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true, locale: 'es-AR',
      timezoneId: 'America/Argentina/Buenos_Aires', geolocation: { latitude: -26.3168546, longitude: -59.3593719 }, permissions: ['geolocation'],
      ...(video ? { recordVideo: { dir: VID, size: { width: 420, height: 760 } } } : {}) });
    const p = await c.newPage();
    await p.route('https://tile.openstreetmap.org/**', tile);
    await p.route('https://routing.openstreetmap.de/**', (r) => r.fulfill({ status: 200, contentType: 'application/json', headers: { 'access-control-allow-origin': '*' }, body: JSON.stringify(ruta) }));
    return [c, p];
  };
  const shot = (p, n, o = {}) => p.screenshot({ path: OUT + n + '.png', ...o });

  // --- Capturas ---
  let [c, p] = await mk(false);
  await p.goto(B); await slow(p, 1800); await shot(p, 'inicio');
  await p.locator('#mapa').scrollIntoViewIfNeeded(); await slow(p, 600); await shot(p, 'mapa');
  await p.click('[data-vista="real"]'); await slow(p, 1200); await shot(p, 'mapa-real');
  await p.goto(B + 'reportar.php'); await slow(p, 1500); await shot(p, 'reportar');
  await p.locator('[data-js-marcar]').scrollIntoViewIfNeeded(); await p.click('[data-js-marcar]'); await slow(p, 1200); await shot(p, 'reportar-ubicacion');
  await p.goto(B + 'centros-salud.php'); await slow(p, 1500); await shot(p, 'centros');
  await p.click('.salud-card[data-centro="hospital"] [data-ir]'); await slow(p, 1800);
  await p.locator('[data-js-ruta]').scrollIntoViewIfNeeded(); await slow(p, 400); await shot(p, 'como-llegar');
  await p.goto(B + 'prevencion.php'); await slow(p, 1000); await shot(p, 'prevencion');
  await p.goto(B + 'cuestionario.php'); await slow(p, 1200); await shot(p, 'cuestionario');
  await p.goto(B + 'test-sintomas.php'); await slow(p, 800); await shot(p, 'test-sintomas');
  await p.goto(B + 'comentarios.php'); await slow(p, 1000); await shot(p, 'comentarios');
  await p.goto(B); await slow(p, 1500);
  const chat = p.locator('.mosqui button').first(); await chat.click().catch(() => {}); await slow(p, 800); await shot(p, 'chatbot');
  await c.close();

  // sin señal
  [c, p] = await mk(false);
  await p.goto(B + 'reportar.php'); await slow(p, 2500);
  await c.setOffline(true); await slow(p, 300);
  await p.selectOption('select[name=tipo_id]', { index: 1 }); await p.selectOption('select[name=barrio_id]', { index: 1 });
  await p.fill('input[name=titulo]', 'Tacho con agua en la vereda'); await p.fill('textarea[name=descripcion]', 'Hay un tacho azul lleno de agua hace una semana.');
  await p.click('form[data-js-form] button[type=submit]'); await slow(p, 1500); await shot(p, 'sin-senal');
  await c.close();

  // Admin
  [c, p] = await mk(false);
  await p.goto(B + '?acceso=1'); await slow(p, 800); await shot(p, 'admin-acceso');
  await p.fill('input[name=usuario]', 'admin'); await p.fill('input[name=contrasena]', 'admin123'); await p.click('form button[type=submit], form input[type=submit]'); await slow(p, 2500);
  await shot(p, 'admin-panel');
  await c.close();

  // --- Videos ---
  const vid = async (nombre, fn) => { const [c, p] = await mk(true); await fn(p, c); const v = p.video(); await c.close(); const ruta = await v.path(); require('fs').renameSync(ruta, VID + nombre + '.webm'); };
  await vid('reportar', async (p) => {
    await p.goto(B + 'reportar.php'); await slow(p, 2500);
    await p.selectOption('select[name=tipo_id]', { index: 1 }); await slow(p, 900);
    await p.selectOption('select[name=barrio_id]', { index: 2 }); await slow(p, 900);
    await p.fill('input[name=referencia]', 'Esquina de la escuela'); await slow(p, 600);
    await p.type('input[name=titulo]', 'Cubiertas con agua en un baldío', { delay: 40 }); await slow(p, 600);
    await p.type('textarea[name=descripcion]', 'Hay cubiertas viejas que juntan agua de lluvia. Se ven larvas.', { delay: 25 }); await slow(p, 700);
    await p.locator('[data-js-gps]').scrollIntoViewIfNeeded(); await slow(p, 600); await p.click('[data-js-gps]'); await slow(p, 2200);
    await p.locator('form[data-js-form] button[type=submit]').scrollIntoViewIfNeeded(); await slow(p, 600);
    await p.click('form[data-js-form] button[type=submit]'); await slow(p, 3500);
  });
  await vid('como-llegar', async (p) => {
    await p.goto(B + 'centros-salud.php'); await slow(p, 2000);
    await p.click('[data-js-mas-cerca]'); await slow(p, 2000);
    await p.click('.salud-card[data-centro="hospital"] [data-ir]'); await slow(p, 2500);
    await p.locator('[data-js-ruta]').scrollIntoViewIfNeeded(); await slow(p, 2500);
    await p.click('[data-modo="auto"]'); await slow(p, 2500);
  });
  await vid('mapa', async (p) => {
    await p.goto(B); await slow(p, 2000);
    await p.locator('#mapa').scrollIntoViewIfNeeded(); await slow(p, 1500);
    await p.click('[data-nivel="alto"]'); await slow(p, 1500);
    await p.click('[data-nivel="todos"]'); await slow(p, 1000);
    await p.click('[data-vista="real"]'); await slow(p, 3000);
  });
  await b.close();
})();
