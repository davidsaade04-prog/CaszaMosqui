// Pruebas de los centros de salud en el navegador (assets/js/centros-salud.js):
// "abierto ahora", próxima apertura e indicaciones de la ruta en castellano.
//   node tests/salud.test.mjs
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const dir = path.dirname(fileURLToPath(import.meta.url));
const ventana = {};
vm.runInContext(readFileSync(path.join(dir, '../assets/js/centros-salud.js'), 'utf8'),
  vm.createContext({ window: ventana, document: { querySelector: () => null }, Intl, Date, console }));
const S = ventana.CaszaSalud;

let ok = 0, fallas = 0;
function prueba(nombre, fn) {
  try { fn(); ok++; console.log(`  \x1b[32m✓\x1b[0m ${nombre}`); }
  catch (e) { fallas++; console.log(`  \x1b[31m✗ ${nombre}\x1b[0m\n      → ${e.message}`); }
}
function igual(esp, obt, msg = 'resultado') {
  if (JSON.stringify(esp) !== JSON.stringify(obt)) throw new Error(`${msg}: se esperaba ${JSON.stringify(esp)} y se obtuvo ${JSON.stringify(obt)}`);
}
// Hora de Argentina (UTC−3, sin horario de verano)
const ar = (fechaHora) => new Date(fechaHora + ':00-03:00');

const CONSULTORIO = [{ dias: [1, 2, 4, 5], desde: '15:00', hasta: '20:00' }];
const INSTITUTO = [
  { dias: [1, 2, 3, 4, 5], desde: '10:00', hasta: '12:00' },
  { dias: [1, 2, 3, 4, 5], desde: '17:30', hasta: '20:00' },
];

console.log('\n\x1b[1mCentros de salud · horario\x1b[0m');
prueba('24 h siempre abierto', () => igual('abierto', S.estadoHorario('24h', ar('2026-11-15T03:00')).estado));
prueba('sin horario → a confirmar', () => igual({ estado: 'sin-dato', texto: 'Horario a confirmar' }, S.estadoHorario(null)));
prueba('jueves 16:00 → abierto, cierra 20 h', () =>
  igual({ estado: 'abierto', texto: 'Abierto ahora · cierra a las 20 h' }, S.estadoHorario(CONSULTORIO, ar('2026-11-12T16:00'))));
prueba('miércoles 23:00 (no atiende) → abre mañana 15 h', () =>
  igual('Cerrado · abre mañana a las 15 h', S.estadoHorario(CONSULTORIO, ar('2026-11-11T23:00')).texto));
prueba('viernes 20:00 en punto → cerrado, abre el lunes', () =>
  igual('Cerrado · abre el lunes a las 15 h', S.estadoHorario(CONSULTORIO, ar('2026-11-13T20:00')).texto));
prueba('jueves 9:00 → abre hoy', () =>
  igual('Cerrado · abre hoy a las 15 h', S.estadoHorario(CONSULTORIO, ar('2026-11-12T09:00')).texto));
prueba('horario cortado: lunes 13:00 → abre hoy 17:30', () =>
  igual('Cerrado · abre hoy a las 17:30 h', S.estadoHorario(INSTITUTO, ar('2026-11-16T13:00')).texto));
prueba('horario cortado: lunes 11:00 → abierto hasta 12', () =>
  igual('Abierto ahora · cierra a las 12 h', S.estadoHorario(INSTITUTO, ar('2026-11-16T11:00')).texto));
prueba('sábado → abre el lunes', () =>
  igual('Cerrado · abre el lunes a las 10 h', S.estadoHorario(INSTITUTO, ar('2026-11-14T11:00')).texto));
prueba('usa la hora de Argentina aunque el celular esté en otra zona', () =>
  // 2026-11-12 18:30 UTC = 15:30 en Argentina (jueves) → abierto
  igual('abierto', S.estadoHorario(CONSULTORIO, new Date('2026-11-12T18:30:00Z')).estado));

console.log('\n\x1b[1mCentros de salud · cómo llegar\x1b[0m');
prueba('indicaciones en castellano y tramos por la misma calle sumados', () => {
  const pasos = S.indicaciones([
    { maneuver: { type: 'depart', bearing_after: 0 }, name: 'Tucumán', distance: 100 },
    { maneuver: { type: 'turn', modifier: 'right' }, name: 'Santa Fe', distance: 200 },
    { maneuver: { type: 'new name', modifier: 'straight' }, name: 'Santa Fe', distance: 50 },
    { maneuver: { type: 'continue', modifier: 'straight' }, name: '', distance: 30 },
    { maneuver: { type: 'roundabout', modifier: 'right', exit: 2 }, name: 'Av. San Martín', distance: 80 },
    { maneuver: { type: 'arrive' }, name: '', distance: 0 },
  ], 'Hospital El Colorado');
  igual(['Salí hacia el norte por Tucumán', 'Doblá a la derecha por Santa Fe', 'Seguí por Santa Fe',
         'En la rotonda, tomá la 2ª salida hacia Av. San Martín', 'Llegaste a Hospital El Colorado'], pasos.map((p) => p.texto));
  igual(80, pasos[2].distancia, 'el tramo sin nombre se suma al anterior');
});
prueba('distancias y tiempos legibles', () => {
  igual(['50 m', '1,4 km', '10 m'], [S.metros(47), S.metros(1430), S.metros(3)]);
  igual(['1 min', '18 min', '1 h 5 min'], [S.minutos(20), S.minutos(1050), S.minutos(3900)]);
});
prueba('Google Maps: con punto exacto usa coordenadas; sin punto, la dirección', () => {
  igual(true, S.enlaceGoogle({ lat: -26.3, lng: -59.36 }, 'pie').includes('destination=-26.3%2C-59.36&travelmode=walking'));
  igual(true, S.enlaceGoogle({ lat: null, direccion: 'Buenos Aires 552' }, 'auto').includes('Buenos%20Aires%20552%2C%20El%20Colorado%2C%20Formosa&travelmode=driving'));
});

console.log(fallas === 0 ? `\n\x1b[32m✔ ${ok} pruebas OK\x1b[0m` : `\n\x1b[31m✘ ${fallas} de ${ok + fallas} pruebas fallaron\x1b[0m`);
process.exit(fallas === 0 ? 0 : 1);
