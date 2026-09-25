/* ============================================================
   CaszaMosqui — Calles de El Colorado por barrio
   Fuente: plano municipal de barrios (2023). Leído a mano: verificar dudosas.
   - window.CALLES_POR_BARRIO  → { "San Martín": [...], ... }
   - Autocompleta el campo de referencia del formulario (datalist).
   - Si el formulario tiene un select de barrio, sugiere primero sus calles.
   ============================================================ */
(function () {
  const C = {
    'Mosconi': ['Avellaneda', 'Tierra del Fuego', 'Santa Cruz', 'Chubut', 'Neuquén', 'Río Negro', 'Julio A. Roca', 'Bernardino Rivadavia', 'Juan D. Perón', 'Mosconi'],
    'Eva Perón': ['La Pampa', 'Río Negro', 'San Luis', 'J. J. Castelli', 'Antártida Argentina', 'Av. Carlos Gardel'],
    'Los Halcones': ['La Rioja', 'Victorica', 'Catamarca', 'Santiago del Estero', 'Formosa', 'Misiones', 'Chaco', 'D. Silva', 'Italia', 'Polonia', 'Raúl Alfonsín', 'Av. Alicia Moreau de Justo', 'Av. Carlos Gardel'],
    'La Paz': ['Ruta Prov. N° 1'],
    'Mitre': ['Avellaneda', 'Hertelendi', 'Tomás Rojas', 'B. Mitre', 'Belgrano', 'Mosconi'],
    'San Pantaleón': ['Mosconi', 'Vélez Sarsfield'],
    'San Juan Norte': ['Pueyrredón', 'N. R. Peña', 'J. M. de Rosas', 'Salta', 'Av. Carlos Gardel', 'Av. Larralde'],
    'Diego F. Sevilla': ['Raúl Alfonsín', 'Pueyrredón', 'J. M. de Rosas', 'Mendoza', 'Jovita Lucila Balbi de Pedrozo', 'Cayo Novoa Gil', 'Félix L. Navarro', 'Jorge "Gigante" González', 'Enfermera Hilda', 'Cirilo Luis Pourcel', 'Nicolás Sawczuk', 'Carlos Hauff', 'Av. Larralde', 'Av. 11 de Febrero'],
    '2 de Abril': ['Corrientes', 'Los Inmigrantes', 'Suipacha', '9 de Julio', 'Belgrano'],
    'San Martín': ['Salta', 'Mendoza', 'Tucumán', 'Santa Fe', 'Buenos Aires', 'Sarmiento', 'Entre Ríos', '20 de Junio', 'H. Yrigoyen', 'Ucrania', 'Av. San Martín', 'Av. 25 de Mayo', 'Av. Larralde'],
    '103 Viviendas': ['Simón de J. Palacios', 'Mihalovic Vda. de Ivancevic', 'Dip. Pedro Castagne', 'Dr. Ernesto Zeitler', 'Felipe N. Núñez', 'Rep. Argentina', 'Pablo R. Machado', 'Av. Larralde'],
    'Independiente': ['Mendoza', 'Santa Fe', 'Cayo Novoa Gil', 'Félix L. Navarro', 'Juan Bacik', 'Nicolás Barrios'],
    'Terminal de Ómnibus': ['Islas Malvinas', 'Iris B. Fernández de la Rosa', 'Av. Néstor Kirchner', 'Av. 11 de Febrero'],
    'La Pileta': ['Pioneros', 'Lapacho', 'Urunday'],
    'San Miguel': ['B. Méndez', 'J. V. González', 'Av. Larralde'],
    '500 Viviendas I.P.V.': ['Vito Raspanti', 'Olga Ivancevic', 'Los Claveles', 'Lirios', 'Las Calas', 'Las Lilas', 'Las Rosas', 'Alegría del Hogar', 'Dalias', 'Azaleas', 'Margaritas', 'Santa Rita', 'Adonair Florencio "Churo" Werning', 'Eugenio Gregorio Baucero', 'Héctor Adolfo "Mencho" Sosa', 'Dr. Roberto Enrique Godoy', 'Dr. Vladimiro Riveros Viera', 'Jorge "Flaco" Ramón Díaz', 'Pilcomayo', 'Eva Perón', 'Fontana'],
    'Aeropuerto': ['Orlando Van Bredan', 'Amalia de la Rosa', 'Mario A. Olmedo', 'Fontana', 'Av. Néstor Kirchner', 'Ruta Prov. N° 1'],
    'La Peña': ['Brasil', 'Ameghino', 'F. M. Esquiú', 'Av. 25 de Mayo'],
    'Itatí': ['San Juan', 'J. J. Paso', 'Güemes', 'Av. Carlos Pellegrini', 'Av. 25 de Mayo', 'Av. Larralde'],
    '140 Viviendas': ['Chile', 'Bolivia'],
    'Vicentín': ['Ceibo', 'Urunday'],
    '46 Viviendas': ['Pje. Insp. O. Iladay', 'Pje. San Antonio'],
    'INTA': ['Pje. M. Moreno', 'Pje. C. Saavedra'],
    '382 Viviendas I.P.V.': ['Fontana', 'Papa Francisco', 'Dr. Ariel de la Rosa', 'Dr. Pedro Duarte', 'Güemes', 'Bermejo', 'Laishi', 'Pirané', 'Dr. Julio C. Amicone', 'J. V. González', 'Av. 11 de Febrero'],
    'M. M. Giroldi': ['Robinson L. Miño', 'Alberdi', 'Jujuy', 'Pasaje Uruguay', 'J. V. González'],
    'Pellegrini': ['Sauce', 'Ombú', 'Av. Carlos Pellegrini'],
    'Vial': ['Fontana', 'J. V. González', 'Ruta Prov. N° 1'],
    'El Arco': ['8 de Abril', 'Maipú', 'San Lorenzo', 'Algarrobo', 'José Hernández', 'Fortín Yunka', 'Antártida Argentina', 'Fray Luis Beltrán', 'Remedios de Escalada', 'Tacuarí', 'Aychuma', 'Deán Funes', 'Ayacucho', 'R. Levene', 'Eulalia Azetti', 'M. S. de Thompson', 'G. Mistral', 'Río Bermejo', 'Av. Carlos Pellegrini', 'J. V. González'],
  };
  window.CALLES_POR_BARRIO = C;

  const todas = [...new Set(Object.values(C).flat())].sort((a, b) => a.localeCompare(b, 'es'));

  function init() {
    const input = document.querySelector('[name="referencia"]');
    if (!input) return;
    const dl = document.createElement('datalist');
    dl.id = 'calles-el-colorado';
    document.body.appendChild(dl);
    input.setAttribute('list', dl.id);
    input.setAttribute('autocomplete', 'off');

    const selBarrio = input.form && input.form.querySelector('select[name="barrio_id"], select[name="barrio"]');
    function llenar() {
      const nombre = selBarrio && selBarrio.selectedOptions[0] ? selBarrio.selectedOptions[0].textContent.trim() : '';
      const propias = C[nombre] || [];
      const lista = [...propias, ...todas.filter(c => !propias.includes(c))];
      dl.innerHTML = lista.map(c => `<option value="${c.replace(/"/g, '&quot;')}">`).join('');
    }
    llenar();
    if (selBarrio) selBarrio.addEventListener('change', llenar);
  }
  document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
})();
