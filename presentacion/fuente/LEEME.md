# Presentación 3D de CaszaMosqui: archivos para editar

Recorrido 3D en 16:9, con 7 etapas: un mosquito *Aedes aegypti* realista vuela en tercera persona sobre escenas hiperrealistas de El Colorado, Formosa. El estilo futurista va en los títulos 3D, las marcas holográficas y el HUD.

- **Publicada en:** https://caszamosqui.onrender.com/presentacion/
- **Se usa así:** → o clic para avanzar, ← para volver, 1–7 para ir a una etapa, N para las notas, F para pantalla completa y M para el sonido.

## Archivos

| Archivo | Qué es |
| --- | --- |
| `plantilla.html` | Toda la presentación: diseño (CSS), contenido y motor 3D (Three.js r170). Se edita acá. |
| `img/e1.webp` … `img/e7.webp` | Fondo hiperrealista de cada etapa (1280×720). |
| `img/e5.mp4`, `img/e6.mp4`, `img/e7.mp4` | Fondos en video de las etapas 5, 6 y 7 (10 s, 1280×720, sin audio). Si una etapa tiene `video` en `plantilla.html`, se usa el video y la foto `eN.webp` queda de respaldo. `armar.py` los copia junto a `index.html`: hay que subirlos con él. |
| `img/logo.webp` | Logo de CaszaMosqui. |
| `armar.py` | Genera `presentacion/index.html` con las imágenes incrustadas. |
| `../e5.mp4` … `../e7.mp4` | Copias de los videos para publicar (las genera `armar.py`). |
| `../index.html` | La presentación final que se publica. No editarla a mano: se regenera con `armar.py`. |

## Cómo editar

1. Cambiá `plantilla.html`. Casi todo el contenido está en la lista `ETAPAS`, al principio del script. Cada etapa tiene:
   - `titulo3d`: el título en letras 3D. `titulo`, `bajada`, `puntos` y `cierre` son la tarjeta de la izquierda.
   - `notas`: lo que dice el expositor. Se ven con la tecla N.
   - `img`: el fondo (`__IMG1__` … `__IMG7__`).
   - `marcas`: los carteles holográficos sobre la foto, como `[x %, y %, 'TEXTO', tipo, 'subtexto opcional']`. El tipo es `alerta`, `ok`, `medio`, `info`, `salud` o `violeta`.
   - `hover`: dónde flota el mosquito, como `[x 0–1, y 0–1, giro en radianes]`.
   - `titulo3dPos`: posición y ancho máximo del título 3D, como `[x, y, ancho]` (fracciones de pantalla).
   - `luz`: las luces que tiñen al mosquito según la escena.
2. Ejecutá `python3 presentacion/fuente/armar.py`.
3. Subí los cambios a `main`. Render publica solo en unos minutos.

## Reglas que seguimos

- **Escenas:** tienen que parecer El Colorado, Formosa: llanura chaqueña, calles de tierra, casas bajas con techo de chapa, lapachos y palmeras. Sin playa, sin mar y sin montañas.
- **Fondos sin texto.** Todo lo que se lee lo pone la presentación (títulos, marcas y tarjeta), para que no haya errores de ortografía ni datos inventados.
- **Composición:** el tercio izquierdo de cada foto queda tranquilo y oscuro, porque ahí va la tarjeta. Lo importante va al centro y a la derecha.
- **El mosquito es una hembra de *Aedes aegypti*:** lira blanca en el tórax, patas con anillos blancos y la punta de la pata trasera blanca.

## Cómo se hicieron los fondos

Con ElevenLabs (modelo `gpt-image-2`, 16:9, una imagen por etapa). Todos los prompts empiezan con *"Cinematic hyperrealistic photograph, 16:9"* y terminan con *"Realistic lighting, shallow depth of field, film grain, high detail. Absolutely no text, no letters, no numbers, no logos, no user interface."* La escena de cada etapa:

1. **Problemática:** noche después de la lluvia en un patio humilde. Cubiertas apiladas con agua, balde azul, botellas verdes, maceta con plato, tanque sin tapa y basura. Brillo rojo de alerta y un proyector holográfico chico.
2. **Solución + mapa:** plaza de noche. Una mesa proyectora metálica con el mapa holográfico cian del pueblo y columnas de luz roja, amarilla y verde.
3. **Reporte + clima:** tormenta de verano en una calle de tierra. La mano de un vecino fotografía con el celular una cubierta con agua y hay relámpagos lejanos.
4. **Prevención + salud:** patio ordenado al atardecer. Balde dado vuelta, tanque tapado, maceta con arena, bolsa atada y un centro de salud con cruz iluminada al fondo.
5. **Comunidad:** vecinos de todas las edades limpiando la cuadra al atardecer, con líneas holográficas que los conectan.
6. **Administración:** oficina municipal de noche. Una persona de espaldas revisa en el monitor la foto de una cubierta con agua y hay un escudo holográfico con candado.
7. **Impacto y cierre:** amanecer sobre El Colorado desde un punto alto. Llanura sin agua en el horizonte, campanario, chicos en bicicleta y un anillo holográfico en el cielo.

Para reemplazar un fondo: generá la imagen en 16:9 y convertila a `.webp` de 1280×720 con el nombre `eN.webp`. Después revisá las coordenadas de `marcas` de esa etapa, porque dependen de dónde quedaron los objetos en la foto.
