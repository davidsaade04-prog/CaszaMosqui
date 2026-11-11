// Pruebas del riesgo en el navegador (assets/js/clima.js → window.riesgoConClima)
// con los MISMOS casos que tests/unit.php, para que la página y el servidor coincidan.
//   node tests/riesgo.test.mjs
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const dir = path.dirname(fileURLToPath(import.meta.url));
const casos = JSON.parse(readFileSync(path.join(dir, 'casos-riesgo.json'), 'utf8'));
const codigo = readFileSync(path.join(dir, '../assets/js/clima.js'), 'utf8');

// Navegador mínimo: clima.js solo necesita window, document y fetch al cargar
const ventana = {};
const contexto = vm.createContext({
  window: ventana,
  document: { getElementById: () => null, addEventListener() {}, hidden: false, dispatchEvent() {} },
  fetch: () => new Promise(() => {}),
  setInterval() {}, setTimeout() {}, CustomEvent: class {}, console, Date,
});
vm.runInContext(codigo, contexto);

let ok = 0, fallas = 0;
function prueba(nombre, fn) {
  try { fn(); ok++; console.log(`  \x1b[32m✓\x1b[0m ${nombre}`); }
  catch (e) { fallas++; console.log(`  \x1b[31m✗ ${nombre}\x1b[0m\n      → ${e.message}`); }
}
function igual(esp, obt, msg) {
  if (JSON.stringify(esp) !== JSON.stringify(obt)) throw new Error(`${msg}: se esperaba ${JSON.stringify(esp)} y se obtuvo ${JSON.stringify(obt)}`);
}

console.log('\n\x1b[1mRiesgo en el navegador (clima.js)\x1b[0m');
prueba('clima.js expone riesgoConClima', () => igual('function', typeof ventana.riesgoConClima, 'tipo'));
for (const c of casos.riesgo_con_clima) {
  prueba(`${c.activos} activos + bonus ${c.bonus} → ${c.total} (${c.nivel})`, () => {
    ventana.CaszaClima.bonus = c.bonus;
    igual({ total: c.total, nivel: c.nivel }, ventana.riesgoConClima(c.activos), 'resultado');
  });
}
for (const [indice, nivel] of casos.nivel_por_indice) {
  prueba(`sin clima: ${indice} activo(s) → ${nivel}`, () => {
    ventana.CaszaClima.bonus = 0;
    igual(nivel, ventana.riesgoConClima(indice).nivel, 'nivel');
  });
}

console.log(fallas === 0 ? `\n\x1b[32m✔ ${ok} pruebas OK\x1b[0m` : `\n\x1b[31m✘ ${fallas} de ${ok + fallas} pruebas fallaron\x1b[0m`);
process.exit(fallas === 0 ? 0 : 1);
