"""
CaszaMosqui — Genera assets/data/casas.bin (casas ilustrativas del modo inmersivo).

Cómo funciona:
  1. Del plano vectorial (assets/img/mapa-el-colorado.svg) se dejan solo los contornos
     grises de las manzanas y se rasteriza a 3200 px de ancho → contornos-3200.png
     (con Playwright: ver el bloque "Paso 1" abajo, o cualquier rasterizador de SVG).
  2. Cada zona cerrada por esos contornos es una manzana (o una parcela, si es chica).
  3. Manzana: lotes de ~10 m de frente a lo largo de cada borde, con la casa retirada
     de la vereda y el fondo libre. Parcela chica (barrios de viviendas): una casa.
  4. Se guarda cada casa como 3 int16: x, z (centésimas de unidad de escena) y el
     ángulo de la calle (milésimas de radián).

Uso (desde la raíz del repositorio, con numpy, scipy y opencv instalados):
  python3 scripts/generar-casas.py      # lee contornos-3200.png, escribe casas.bin
  mv casas.bin assets/data/casas.bin

Paso 1 (rasterizar solo los contornos), por ejemplo con Python + Playwright:
  - Filtrar del SVG los <path> con stroke="rgb(32.94%, 32.94%, 32.94%)" y los rojos de grosor 9.
  - Abrir ese SVG a 3200 × 3567 px en un navegador sin cabecera y guardar la captura.
"""
import numpy as np, cv2, json, struct
from scipy import ndimage as ndi
im = cv2.imread('contornos-3200.png', cv2.IMREAD_GRAYSCALE)
H, W = im.shape
pared = im < 200
pared = cv2.dilate(pared.astype(np.uint8), np.ones((3, 3), np.uint8)) > 0
lab, n = ndi.label(~pared)
objs = ndi.find_objects(lab)
PX_M = 3500 / W                     # ≈ 1.09 m por píxel (100 unidades = 3200 px ≈ 3,5 km)
LOTE = 10 / PX_M                    # frente de lote ≈ 10 m
RETIRO = 3 / PX_M                   # retiro desde la vereda
FONDO = 11 / PX_M                   # profundidad de la casa
ANCHO_CASA = 8 / PX_M
casas = []                           # (px, py, angulo)
vis = cv2.cvtColor(im, cv2.COLOR_GRAY2BGR)
nman = nlote = 0
for i, sl in enumerate(objs, start=1):
    if sl is None: continue
    y0, y1, x0, x1 = sl[0].start, sl[0].stop, sl[1].start, sl[1].stop
    if y0 <= 2 or x0 <= 2 or y1 >= H - 2 or x1 >= W - 2: continue      # toca el borde: afuera del pueblo
    sub = ndi.binary_fill_holes(lab[sl] == i)
    area = int(sub.sum())
    if area < 120 or area > 45000: continue
    cnts, _ = cv2.findContours(sub.astype(np.uint8), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_NONE)
    c = max(cnts, key=cv2.contourArea)
    (cx, cy), (rw, rh), ang = cv2.minAreaRect(c)
    lado_min = min(rw, rh)
    if lado_min < 6: continue
    rect = area / max(1, rw * rh)
    if rect < 0.6: continue
    off = np.array([x0, y0])
    (ccx, ccy), rad = cv2.minEnclosingCircle(c)
    if area / (np.pi * rad * rad) > 0.7 and rad < 70: continue        # círculo con el número de manzana
    if area < 1800:
        # Parcela chica (barrios de viviendas ya loteados): una casa por parcela, alineada al lado largo
        a = np.deg2rad(ang if rw >= rh else ang + 90)
        casas.append((cx + x0, cy + y0, a)); nlote += 1
        cv2.circle(vis, (int(cx + x0), int(cy + y0)), 3, (0, 140, 255), -1)
        continue
    if lado_min < 22: continue                  # franja angosta: es calle, no manzana
    nman += 1
    # Manzana: casas a lo largo de cada borde, mirando a la calle
    ero = cv2.erode(sub.astype(np.uint8), np.ones((3, 3), np.uint8))
    poli = cv2.approxPolyDP(c, 0.012 * cv2.arcLength(c, True), True)[:, 0, :].astype(np.float32)
    # orientación del contorno (para saber hacia dónde está el interior)
    signo = 1 if cv2.contourArea(poli, oriented=True) > 0 else -1
    puestas = []
    for k in range(len(poli)):
        p, q = poli[k], poli[(k + 1) % len(poli)]
        d = q - p; L = float(np.hypot(*d))
        if L < LOTE * 0.9: continue
        u = d / L
        nrm = np.array([-u[1], u[0]]) * signo        # normal hacia adentro (se verifica abajo)
        prueba = (p + q) / 2 + nrm * (RETIRO + FONDO / 2)
        px, py = int(prueba[0]), int(prueba[1])
        if not (0 <= py < ero.shape[0] and 0 <= px < ero.shape[1] and ero[py, px]): nrm = -nrm
        m = int(L // LOTE)
        ini = (L - m * LOTE) / 2
        for j in range(m):
            t = ini + (j + 0.5) * LOTE
            pos = p + u * t + nrm * (RETIRO + FONDO / 2)
            px, py = int(round(pos[0])), int(round(pos[1]))
            if not (0 <= py < ero.shape[0] and 0 <= px < ero.shape[1] and ero[py, px]): continue
            if any((pos[0] - a) ** 2 + (pos[1] - b) ** 2 < (ANCHO_CASA * 1.05) ** 2 for a, b in puestas): continue
            puestas.append((pos[0], pos[1]))
            casas.append((pos[0] + x0, pos[1] + y0, float(np.arctan2(u[1], u[0]))))
            cv2.circle(vis, (int(pos[0] + x0), int(pos[1] + y0)), 3, (255, 120, 0), -1)
print('manzanas', nman, 'parcelas', nlote, 'casas', len(casas))
cv2.imwrite('casas-vis.png', cv2.resize(vis, (1600, int(1600 * H / W)), interpolation=cv2.INTER_AREA))
cv2.imwrite('casas-zoom.png', vis[1300:2100, 1100:2100])
# Binario: int16 x, z (centésimas de unidad de escena) + int16 ángulo (milésimas de radián)
ANCHO, LARGO = 100.0, 100.0 * 2675 / 2400
buf = bytearray()
for px, py, a in casas:
    x = (px / W - 0.5) * ANCHO; z = (py / H - 0.5) * LARGO
    a = (a + np.pi) % np.pi                      # una casa es simétrica a 180°
    buf += struct.pack('<hhh', int(round(x * 100)), int(round(z * 100)), int(round(a * 1000)))
open('casas.bin', 'wb').write(buf)
print('bytes', len(buf))
