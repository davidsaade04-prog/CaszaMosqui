<?php
/**
 * CaszaMosqui — Base de conocimiento del chatbot "Mosqui"
 * --------------------------------------------------------
 * Cada tema tiene:
 *   'claves'    => palabras o frases (sin tildes, en minúscula) => peso
 *   'respuesta' => texto (admite **negrita** y listas con "- ")
 * El buscador suma los pesos de las claves que aparecen en la pregunta.
 * Para agregar un tema: copiar un bloque y cambiar claves y respuesta.
 *
 * Contenido basado en recomendaciones generales de salud pública (Ministerio de Salud
 * de la Nación / OPS). No reemplaza la consulta médica.
 */

return [

  /* ===================== SÍNTOMAS Y SALUD ===================== */

  'sintomas_dengue' => [
    'claves' => ['sintoma' => 3, 'sintomas' => 3, 'como me doy cuenta' => 3, 'como se si tengo' => 3, 'que se siente' => 2,
                 'fiebre' => 1.5, 'dolor de cabeza' => 1.5, 'tengo dengue' => 2, 'dengue' => 0.5, 'signos' => 1],
    'respuesta' => "Los síntomas más comunes del **dengue** son:\n- **Fiebre alta** (más de 38 °C) de comienzo brusco\n- Dolor de cabeza y **dolor detrás de los ojos**\n- Dolores musculares y de articulaciones\n- Cansancio, náuseas o vómitos\n- Sarpullido o manchas en la piel\n\nSuelen aparecer entre **4 y 10 días** después de la picadura. Ante fiebre, **consultá en el centro de salud** y no te automediques. También podés hacer el **Test de Síntomas** de esta página.",
  ],

  'signos_alarma' => [
    'claves' => ['alarma' => 3, 'grave' => 2, 'guardia' => 2, 'urgencia' => 2, 'urgente' => 2, 'sangrado' => 3, 'sangra' => 3,
                 'sangre' => 2, 'dolor de panza' => 2, 'dolor abdominal' => 3, 'vomito' => 1.5, 'vomitos' => 1.5,
                 'somnolencia' => 3, 'desmayo' => 2, 'empeora' => 2, 'dengue grave' => 3, 'hemorragico' => 3],
    'respuesta' => "🚨 **Signos de alarma: ir a la guardia de inmediato** si aparece alguno de estos:\n- Dolor de panza **intenso y continuo**\n- **Vómitos que no paran**\n- Sangrado de encías, nariz o en la orina/materia fecal\n- Mucho sueño, confusión o irritabilidad\n- Dificultad para respirar\n- Piel fría, pálida o sudorosa; orinar muy poco\n\nOjo: suelen aparecer **cuando baja la fiebre** (entre el día 3 y 7), que es la etapa más delicada. Ante una emergencia llamá al **107**.",
  ],

  'que_hacer_sintomas' => [
    'claves' => ['que hago' => 2.5, 'que tengo que hacer' => 2.5, 'que hacer si' => 2.5, 'creo que tengo' => 3, 'me siento mal' => 2,
                 'tengo fiebre' => 3, 'a donde voy' => 2, 'donde me atiendo' => 3, 'consultar' => 1.5, 'medico' => 1],
    'respuesta' => "Si tenés fiebre u otros síntomas:\n- **Consultá en el centro de salud u hospital** más cercano (en El Colorado, el hospital figura en el barrio San Martín según el plano municipal)\n- **Tomá mucho líquido** (agua, jugos, sales de rehidratación)\n- Hacé **reposo**\n- **No te automediques**: nada de aspirina ni ibuprofeno\n- Usá repelente y mosquitero para que ningún mosquito te pique y lleve el virus a otra persona\n\nSi aparecen **signos de alarma** (dolor de panza fuerte, vómitos que no paran, sangrados, mucho sueño), andá a la guardia o llamá al **107**.",
  ],

  'medicamentos' => [
    'claves' => ['aspirina' => 3, 'ibuprofeno' => 3, 'paracetamol' => 3, 'remedio' => 2, 'medicamento' => 2, 'medicacion' => 2,
                 'pastilla' => 2, 'antiinflamatorio' => 3, 'diclofenac' => 3, 'que tomo' => 3, 'que puedo tomar' => 3, 'automedic' => 2],
    'respuesta' => "Con sospecha de dengue:\n- ❌ **No tomes aspirina, ibuprofeno, diclofenac ni otros antiinflamatorios**: aumentan el riesgo de sangrado.\n- ❌ No te apliques inyecciones intramusculares.\n- ✅ Para la fiebre, el médico suele indicar **paracetamol**, en la dosis que te indique.\n- ✅ Mucho líquido y reposo.\n\nLo más importante: **consultá antes de tomar cualquier cosa**. No hay un remedio específico contra el virus; el tratamiento es acompañar y controlar.",
  ],

  'tratamiento' => [
    'claves' => ['tratamiento' => 3, 'se cura' => 3, 'cura' => 2, 'cuanto dura' => 3, 'cuantos dias' => 2, 'recuper' => 2, 'curar' => 2],
    'respuesta' => "No existe un medicamento que elimine el virus del dengue: el tratamiento es **reposo, mucha hidratación y control médico**. La mayoría de las personas se recupera en **1 a 2 semanas**, aunque el cansancio puede durar un poco más.\n\nEl control es importante porque los días en que **baja la fiebre** (entre el 3 y el 7) son los de mayor riesgo de complicaciones. Seguí las indicaciones del equipo de salud y volvé a consultar si aparecen signos de alarma.",
  ],

  'contagio' => [
    'claves' => ['contagi' => 3, 'transmit' => 2.5, 'se pega' => 3, 'persona a persona' => 3, 'como se transmite' => 3,
                 'como se contagia' => 3, 'beso' => 2, 'tocar' => 1, 'mismo cuarto' => 2],
    'respuesta' => "El dengue **no se contagia de persona a persona** (ni por tocar, besar o compartir el mate). Se transmite así:\n1. Un mosquito *Aedes aegypti* pica a una persona que tiene el virus (sobre todo en los días de fiebre).\n2. Después de unos días, ese mosquito puede transmitirlo a **cada persona que pique**.\n\nPor eso, si alguien en casa tiene dengue, es clave que **use repelente y mosquitero**: así cortamos la cadena.",
  ],

  'segunda_vez' => [
    'claves' => ['segunda vez' => 3, 'otra vez' => 2.5, 'de nuevo' => 2, 'ya tuve' => 3, 'dos veces' => 3, 'serotipo' => 3,
                 'volver a tener' => 3, 'inmune' => 2.5, 'inmunidad' => 2.5],
    'respuesta' => "Sí, **se puede tener dengue más de una vez**. Hay **4 tipos (serotipos)** del virus: al tener uno quedás protegido solo contra ese tipo.\n\nUna **segunda infección con otro tipo tiene más riesgo de dengue grave**, así que si ya tuviste dengue es todavía más importante prevenir y consultar rápido ante fiebre.",
  ],

  'vacuna' => [
    'claves' => ['vacuna' => 4, 'vacunar' => 4, 'qdenga' => 4, 'dosis' => 2, 'vacunacion' => 4],
    'respuesta' => "En Argentina está aprobada una **vacuna contra el dengue** (Qdenga, de laboratorio Takeda). Se aplica en **2 dosis separadas por 3 meses**.\n\nQuiénes la reciben gratis depende de la estrategia del Ministerio de Salud y de cada provincia (edad y zona). **Consultá en tu centro de salud o vacunatorio** si te corresponde.\n\nImportante: la vacuna **no reemplaza** la eliminación de criaderos ni el uso de repelente.",
  ],

  'embarazo' => [
    'claves' => ['embaraz' => 4, 'bebe' => 2, 'lactancia' => 3, 'amamant' => 3, 'recien nacido' => 3],
    'respuesta' => "Si estás **embarazada** y tenés fiebre, **consultá enseguida**: el embarazo requiere un control más cercano.\n\nPara protegerte:\n- Usá repelente aprobado (los que tienen DEET, IR3535 o icaridina son seguros en el embarazo siguiendo las indicaciones del envase)\n- Ropa clara de manga larga y mosquiteros\n\nEn **bebés menores de 2 meses no se usa repelente**: protegelos con mosquitero en el cochecito y la cuna. El **zika** en el embarazo es especialmente riesgoso para el bebé.",
  ],

  'ninos' => [
    'claves' => ['chico' => 1.5, 'chicos' => 1.5, 'nene' => 3, 'nena' => 3, 'nino' => 3, 'ninos' => 3, 'hijo' => 3, 'hija' => 3, 'hijos' => 3, 'escuela' => 1.5],
    'respuesta' => "Con los **chicos**:\n- Ante fiebre, llevalos a control y **no les des aspirina ni ibuprofeno**\n- Que tomen mucho líquido y vigilá signos de alarma (dolor de panza, vómitos, mucho sueño, sangrado)\n- Repelente: aplicalo vos en tus manos y después en su piel (no en manos ni cara), siguiendo la edad indicada en el envase\n- En menores de 2 meses, solo **mosquitero**\n\nEn la escuela y en casa: revisar juguetes, baldes y botellas que junten agua.",
  ],

  /* ===================== EL MOSQUITO ===================== */

  'mosquito' => [
    'claves' => ['aedes' => 3, 'aegypti' => 3, 'que mosquito' => 3, 'como es el mosquito' => 4, 'como reconozco' => 3,
                 'patas blancas' => 3, 'rayas' => 2, 'manchas blancas' => 3, 'mosquito del dengue' => 3],
    'respuesta' => "El mosquito que transmite dengue, zika y chikungunya es el **Aedes aegypti**:\n- Es chico y oscuro, con **manchas blancas en las patas y el cuerpo**\n- **Pica de día**, sobre todo a la mañana temprano y al atardecer\n- Vive **dentro y cerca de las casas** y vuela poco (alrededor de 100 metros)\n- Pica la **hembra**, que necesita sangre para poner huevos\n\nComo vuela poco, **si hay mosquitos en tu casa, el criadero probablemente está en tu casa o la de tus vecinos**.",
  ],

  'horario_picadura' => [
    'claves' => ['a que hora' => 3, 'horario' => 3, 'de noche' => 2.5, 'de dia' => 2.5, 'cuando pica' => 3, 'que hora' => 3],
    'respuesta' => "El *Aedes aegypti* **pica de día**, con más actividad **temprano a la mañana y al atardecer**. También puede picar dentro de la casa en lugares con sombra.\n\nPor eso el repelente y la ropa clara de manga larga son importantes **durante el día**, no solo a la noche.",
  ],

  'ciclo' => [
    'claves' => ['ciclo' => 3, 'larva' => 3, 'larvas' => 3, 'huevo' => 3, 'huevos' => 3, 'pupa' => 3, 'cuanto vive' => 3,
                 'cuanto tarda' => 3, 'se reproduce' => 3, 'reproduc' => 2.5, 'nace' => 2],
    'respuesta' => "El ciclo del *Aedes aegypti*:\n1. 🥚 **Huevo**: la hembra los pega en la pared de recipientes, justo arriba del agua. **Resisten meses en seco**.\n2. 🐛 **Larva**: nacen cuando el recipiente vuelve a mojarse (por ejemplo, con la lluvia).\n3. ⏳ **Pupa**: última etapa dentro del agua.\n4. 🦟 **Mosquito adulto**.\n\nCon calor, de huevo a mosquito pasan **7 a 10 días**. Por eso hay que revisar y vaciar recipientes **una vez por semana**, y **cepillar las paredes** para sacar los huevos pegados.",
  ],

  'agua_limpia' => [
    'claves' => ['agua limpia' => 3, 'agua sucia' => 3, 'donde se cria' => 3, 'donde pone' => 3, 'se cria' => 3, 'cria' => 1.5,
                 'agua estancada' => 2.5, 'charco' => 2],
    'respuesta' => "El *Aedes aegypti* se cría en **agua limpia y quieta**, acumulada en **recipientes**: baldes, tachos, botellas, latas, cubiertas, floreros, platitos de macetas, bebederos, canaletas tapadas, tanques sin tapa, piletas sin mantenimiento.\n\nNo es un mosquito de pantanos ni de agua de cloaca: **el peligro está en los objetos de nuestros patios**.",
  ],

  'otras_enfermedades' => [
    'claves' => ['zika' => 4, 'chikungunya' => 4, 'chikungunia' => 4, 'fiebre amarilla' => 4, 'que enfermedades' => 3, 'otras enfermedades' => 3],
    'respuesta' => "El mismo mosquito (*Aedes aegypti*) puede transmitir:\n- **Dengue**: fiebre alta, dolor de cuerpo y detrás de los ojos.\n- **Zika**: síntomas más leves (fiebre baja, sarpullido, ojos rojos). Es **muy riesgoso en el embarazo** y también se transmite por vía sexual.\n- **Chikungunya**: fiebre alta y **dolor de articulaciones muy fuerte**, que puede durar meses.\n- **Fiebre amarilla**: hay vacuna, indicada según la zona.\n\nLa prevención es la misma para todas: **eliminar criaderos y evitar picaduras**.",
  ],

  /* ===================== PREVENCIÓN ===================== */

  'prevencion_general' => [
    'claves' => ['prevenir' => 3, 'prevencion' => 3, 'evitar' => 2, 'como me cuido' => 3, 'cuidarme' => 3, 'cuidar' => 2,
                 'que puedo hacer' => 2, 'proteger' => 2],
    'respuesta' => "La prevención tiene dos partes:\n\n**1. Eliminar criaderos (lo más importante):**\n- **Tapá** tanques y recipientes con agua\n- **Vaciá y dá vuelta** baldes, macetas, bebederos, juguetes\n- **Tirá** latas, botellas, cubiertas y cacharros que no uses\n- Hacelo **una vez por semana**\n\n**2. Evitar picaduras:**\n- Repelente, ropa clara de manga larga\n- Mosquiteros en puertas, ventanas y camas\n\nSi ves un criadero en la vía pública, **reportalo en esta página** 📍.",
  ],

  'descacharrado' => [
    'claves' => ['descacharr' => 4, 'elimin' => 3, 'sacar criaderos' => 3, 'cacharro' => 3, 'cacharros' => 3, 'patio' => 2, 'limpiar el patio' => 3, 'basura' => 1.5, 'chatarra' => 3],
    'respuesta' => "**Descacharrar** es eliminar todo objeto que pueda juntar agua:\n- Tirá latas, botellas, envases, tapitas y chatarra\n- Guardá bajo techo o **boca abajo** baldes, palanganas y juguetes\n- Revisá rincones, techos, canaletas y detrás de la casa\n- Sacá la basura en bolsas cerradas\n\nHacelo **cada semana**, sobre todo **después de cada lluvia**. Muchos municipios hacen operativos de descacharrado: aprovechalos para sacar lo que no usás.",
  ],

  'tanques' => [
    'claves' => ['tanque' => 3, 'tanques' => 3, 'aljibe' => 3, 'tacho de agua' => 3, 'bidon' => 2, 'agua para tomar' => 2],
    'respuesta' => "Tanques, aljibes y recipientes que guardan agua:\n- Mantenelos **siempre tapados** con tapa bien ajustada (o con tela mosquitera atada)\n- Si no tienen tapa, **vacialos y cepillá las paredes** una vez por semana: los huevos quedan pegados justo arriba del nivel del agua\n- Revisá que no haya rajaduras por donde entre el mosquito",
  ],

  'neumaticos' => [
    'claves' => ['neumatico' => 4, 'neumaticos' => 4, 'cubierta' => 4, 'cubiertas' => 4, 'goma' => 2, 'gomeria' => 3, 'rueda' => 2.5],
    'respuesta' => "Las **cubiertas (neumáticos)** son uno de los criaderos más comunes porque el agua queda atrapada adentro:\n- Guardalas **bajo techo**\n- O **perforalas** para que no junten agua\n- O rellenalas con tierra si las usás en la huerta o como decoración\n- Si no las usás, entregalas en los operativos de descacharrado\n\nSi ves cubiertas acumuladas en un baldío o gomería, **reportalo en la app**.",
  ],

  'plantas_floreros' => [
    'claves' => ['florero' => 4, 'floreros' => 4, 'maceta' => 3, 'macetas' => 3, 'planta' => 2, 'plantas' => 2, 'platito' => 4,
                 'platitos' => 4, 'cementerio' => 3, 'flores' => 2, 'agua de las plantas' => 3],
    'respuesta' => "Plantas y floreros:\n- **Floreros**: reemplazá el agua por **arena húmeda**, o cambiá el agua y lavá el florero cada 2 días (muy importante en los **cementerios**)\n- **Platitos de macetas**: sacalos o llenalos con arena\n- **Plantas en agua** (como potus): cambiá el agua y lavá el recipiente al menos 2 veces por semana\n- Revisá plantas que juntan agua entre sus hojas",
  ],

  'bebederos' => [
    'claves' => ['bebedero' => 4, 'bebederos' => 4, 'perro' => 2.5, 'gato' => 2.5, 'mascota' => 3, 'animales' => 2, 'gallina' => 2.5, 'agua del perro' => 4],
    'respuesta' => "Bebederos de mascotas y animales:\n- **Cambiá el agua todos los días**\n- **Lavá y cepillá** el recipiente para sacar huevos pegados en las paredes\n- Si son grandes (para caballos o gallinas), vacialos y limpialos al menos una vez por semana",
  ],

  'piletas' => [
    'claves' => ['pileta' => 4, 'piletas' => 4, 'pelopincho' => 4, 'piscina' => 4, 'cloro' => 3, 'pileta de lona' => 4],
    'respuesta' => "Piletas:\n- **En uso**: mantenelas con **cloro** y filtrado, el agua tratada no es criadero\n- **Piletas de lona / pelopincho**: vacialas si no las usás y guardalas secas y dobladas\n- Una pileta abandonada con agua verde es un **criadero enorme**: si ves una, reportala en la app",
  ],

  'canaletas_desagues' => [
    'claves' => ['canaleta' => 4, 'canaletas' => 4, 'desague' => 4, 'rejilla' => 3, 'techo' => 2, 'zanja' => 3, 'cuneta' => 3, 'alcantarilla' => 3],
    'respuesta' => "Canaletas, desagües y zanjas:\n- Limpiá **canaletas** y bajadas de agua para que no se tapen con hojas\n- Revisá **techos y losas** donde quede agua acumulada\n- Tapá **rejillas** con malla fina o echá agua con lavandina\n- Zanjas y cunetas tapadas con basura: avisá al municipio y **reportalas en la app** para que queden en el mapa de riesgo",
  ],

  'repelente' => [
    'claves' => ['repelente' => 4, 'repelentes' => 4, 'off' => 2, 'deet' => 4, 'icaridina' => 4, 'crema' => 2, 'spray' => 2, 'citronela' => 3],
    'respuesta' => "Repelentes:\n- Usá productos **aprobados por ANMAT** con **DEET, IR3535 o icaridina**\n- Aplicalo en la **piel expuesta** y renovalo según lo que indique el envase (y después de transpirar o mojarte)\n- Si usás protector solar, primero el protector y **después el repelente**\n- En chicos: aplicalo vos, evitando manos, ojos y boca. En **menores de 2 meses no se usa**: solo mosquitero\n\nLa **citronela** y los productos caseros protegen poco o por muy poco tiempo.",
  ],

  'proteger_casa' => [
    'claves' => ['mosquitero' => 4, 'mosquiteros' => 4, 'tela mosquitera' => 4, 'espiral' => 3, 'espirales' => 3, 'tableta' => 3,
                 'ventilador' => 2.5, 'aire acondicionado' => 2.5, 'ropa' => 2, 'manga larga' => 3],
    'respuesta' => "Para protegerte dentro de casa:\n- **Telas mosquiteras** en puertas y ventanas\n- **Mosquitero** en camas y cunas, sobre todo si alguien tiene fiebre\n- **Espirales o tabletas** en ambientes ventilados\n- Ventilador o aire acondicionado ayudan (el mosquito vuela mal con corriente de aire)\n- **Ropa clara** de manga larga y pantalón largo",
  ],

  'fumigacion' => [
    'claves' => ['fumig' => 4, 'fumigacion' => 4, 'fumigar' => 4, 'insecticida' => 3, 'veneno' => 2, 'rociar' => 3],
    'respuesta' => "La **fumigación** solo mata a los mosquitos **adultos** que están volando en ese momento: **no elimina huevos ni larvas**.\n\nPor eso **no reemplaza** la eliminación de criaderos. Se usa como apoyo cuando hay casos. Lo que más sirve es que **cada casa revise y vacíe sus recipientes cada semana**.",
  ],

  'lluvia_clima' => [
    'claves' => ['lluvia' => 3, 'llueve' => 3, 'llovio' => 3, 'clima' => 3, 'calor' => 2, 'verano' => 2.5, 'invierno' => 3, 'temporada' => 3, 'frio' => 2.5],
    'respuesta' => "El clima influye mucho:\n- **Después de las lluvias** los recipientes se llenan y los huevos que estaban secos **eclosionan**\n- Con **calor** (más de 20-25 °C) el ciclo es más rápido: 7 a 10 días de huevo a mosquito\n- En **invierno** hay menos mosquitos, pero **los huevos sobreviven**: por eso hay que descacharrar todo el año\n\nEn esta página, la **Alerta climática** usa la lluvia y temperatura reales de El Colorado para avisar cuándo el riesgo sube.",
  ],

  /* ===================== MITOS ===================== */

  'mitos' => [
    'claves' => ['mito' => 4, 'mitos' => 4, 'es verdad' => 3, 'es cierto' => 3, 'verdad que' => 3, 'dicen que' => 3],
    'respuesta' => "Algunos **mitos** frecuentes:\n- ❌ *\"Se cría en agua sucia\"* → Prefiere **agua limpia** y quieta en recipientes.\n- ❌ *\"Si ya tuve dengue, no me agarra más\"* → Hay **4 tipos**; se puede repetir y la segunda puede ser más grave.\n- ❌ *\"Con fumigar alcanza\"* → Solo mata adultos; **hay que eliminar criaderos**.\n- ❌ *\"Solo pica de noche\"* → **Pica de día**, sobre todo al amanecer y atardecer.\n- ❌ *\"Se contagia de persona a persona\"* → Solo por la **picadura del mosquito**.\n- ❌ *\"En invierno no hay riesgo\"* → Los **huevos sobreviven** meses esperando la lluvia.",
  ],

  /* ===================== LA APP ===================== */

  'como_reportar' => [
    'claves' => ['report' => 4, 'denunci' => 3, 'cargar un' => 2, 'avisar' => 2, 'criadero en la calle' => 4, 'baldio' => 3, 'vecino' => 2],
    'respuesta' => "Para **reportar un criadero** en CaszaMosqui:\n1. Tocá **\"+ Reportar\"** en el menú\n2. Elegí el **tipo** (recipientes, neumáticos, piletas, zanjas, basurales, botellas) y el **barrio**\n3. En **Referencia** escribí la esquina (te sugerimos las calles de tu barrio)\n4. Poné un título y una descripción y enviá\n\nTu reporte aparece al instante en el **mapa de riesgo** y el equipo de salud puede verificarlo y controlarlo.",
  ],

  'mapa_colores' => [
    'claves' => ['mapa' => 3, 'colores' => 3, 'rojo' => 2.5, 'amarillo' => 2, 'naranja' => 2.5, 'verde' => 2, 'semaforo' => 4, 'mi barrio' => 3, 'riesgo' => 1.5],
    'respuesta' => "El **mapa de riesgo** muestra cada barrio de El Colorado con un color:\n- 🔴 **Rojo (alto)**: muchos criaderos sin controlar\n- 🟠 **Naranja (medio)**\n- 🟢 **Verde (bajo)**\n\nEl número al lado del nombre son los **criaderos sin controlar**. El color también tiene en cuenta el **clima de la semana**: después de lluvias con calor, el mismo barrio puede pasar a un nivel más alto. Tocá un barrio para ver sus criaderos.",
  ],

  'que_es_app' => [
    'claves' => ['caszamosqui' => 4, 'esta pagina' => 3, 'esta app' => 3, 'que es esto' => 3, 'para que sirve' => 3, 'quien sos' => 3, 'que sos' => 3, 'mosqui' => 2],
    'respuesta' => "Soy **Mosqui** 🦟, el asistente de **CaszaMosqui**, una plataforma comunitaria de El Colorado (Formosa) para prevenir el dengue, zika y chikungunya.\n\nEn la página podés:\n- 📍 **Reportar criaderos** y verlos en el **mapa de riesgo** por barrio\n- 🌧️ Ver la **alerta climática** de la semana\n- 📖 Leer la **guía de prevención**\n- 🎯 Hacer el **cuestionario** y el **test de síntomas**\n\nPreguntame lo que quieras sobre mosquitos, prevención o síntomas.",
  ],

  /* ===================== CONVERSACIÓN ===================== */

  'saludo' => [
    'claves' => ['hola' => 3, 'buen dia' => 3, 'buenas' => 3, 'buenos dias' => 3, 'buenas tardes' => 3, 'buenas noches' => 3, 'que tal' => 2],
    'respuesta' => "¡Hola! Soy **Mosqui** 🦟, el asistente de CaszaMosqui. Puedo ayudarte con dudas sobre **dengue, zika y chikungunya**: síntomas, prevención, criaderos, repelentes y cómo usar la página. ¿Qué querés saber?",
  ],

  'gracias' => [
    'claves' => ['gracias' => 4, 'genial' => 2, 'perfecto' => 2, 'buenisimo' => 2, 'excelente' => 2, 'chau' => 3, 'adios' => 3],
    'respuesta' => "¡De nada! 🙌 Recordá: **tapá, vaciá y dá vuelta, tirá**, una vez por semana. Si ves un criadero en tu barrio, reportalo en la página. ¡Entre todos frenamos al mosquito!",
  ],
];
