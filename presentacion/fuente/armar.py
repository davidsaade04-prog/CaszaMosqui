"""Arma presentacion/index.html a partir de plantilla.html y las imágenes de img/.

Uso (desde la carpeta del proyecto):
    python3 presentacion/fuente/armar.py

Las imágenes se incrustan en el HTML, así la presentación es un solo archivo
que funciona en cualquier servidor (Render, Netlify, GitHub Pages).
"""
import base64
import shutil
from pathlib import Path

AQUI = Path(__file__).resolve().parent
SALIDA = AQUI.parent / "index.html"


def b64(nombre: str) -> str:
    datos = (AQUI / "img" / nombre).read_bytes()
    return "data:image/webp;base64," + base64.b64encode(datos).decode()


plantilla = (AQUI / "plantilla.html").read_text(encoding="utf-8")
pagina = plantilla.replace("__LOGO__", b64("logo.webp"))
for i in range(1, 8):
    pagina = pagina.replace(f"__IMG{i}__", b64(f"e{i}.webp"))

# La plantilla es el contenido de la página: se le agrega el documento completo
corte = pagina.index("<style>")
cabeza, resto = pagina[:corte], pagina[corte:]
fin_estilo = resto.index("</style>") + len("</style>")
estilo, cuerpo = resto[:fin_estilo], resto[fin_estilo:]

SALIDA.write_text(f"""<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="description" content="Recorrido 3D de CaszaMosqui: un mosquito Aedes aegypti vuela por El Colorado, Formosa.">
{cabeza}<style>*,*::before,*::after{{box-sizing:border-box}}body{{margin:0}}[hidden]{{display:none!important}}</style>
{estilo}
</head>
<body>
{cuerpo}
</body>
</html>
""", encoding="utf-8")
print(f"Listo: {SALIDA} ({SALIDA.stat().st_size // 1024} KB)")

# Los fondos en video (img/eN.mp4) no se incrustan: se copian junto a index.html
for i in range(1, 8):
    video = AQUI / "img" / f"e{i}.mp4"
    if video.exists():
        shutil.copy2(video, SALIDA.parent / video.name)
        print(f"Video: {video.name} ({video.stat().st_size // 1024} KB)")
