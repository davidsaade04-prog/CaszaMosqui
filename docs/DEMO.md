# 🖥️ DEMO — CaszaMosqui

> **Guion de presentación — FormosaHack 2026** (3 a 5 minutos).

## 1. El problema (30 seg) 🎬
> "En los barrios existen condiciones que favorecen la proliferación del mosquito
> Aedes aegypti: recipientes, neumáticos, piletas, zanjas, basurales. Pero no hay
> información accesible ni organizada sobre dónde están esos criaderos, y sin esa
> información la comunidad no puede participar de la prevención del dengue."

## 2. La solución (30 seg) 💡
> "CaszaMosqui es una plataforma comunitaria donde cada vecino reporta criaderos;
> la plataforma los organiza y los muestra en un **mapa de riesgo por barrio** con
> semáforo, y guía con acciones de prevención. Cada reporte = un criadero menos."

## 3. Recorrido en vivo (2 min) — ¡lo más importante! ⭐

1. **Alerta climática:** "Con la lluvia y el calor de estas dos semanas, el riesgo
   climático está en ___: cada criadero pesa más". Mostrar el gráfico de lluvia.
2. **Mapa de riesgo sobre el plano oficial de El Colorado:** barrios con semáforo y
   número de criaderos sin controlar. Tocar **"Alto"** → quedan solo los barrios en
   rojo. **Clic en El Arco** → lista filtrada de ese barrio.
3. **KPIs:** totales reportados / sin controlar / verificados / controlados y %
   de controlados (conectados a la base real).
4. **Criaderos más reportados:** ranking por tipo (iconos y colores).
5. **Crear un reporte en vivo:** elegir tipo + **barrio** → en "referencia" aparecen
   las **calles reales del barrio** → aparece al instante en el mapa (**POST**).
   Tip: reportar en un barrio en amarillo para que el jurado lo vea pasar a rojo.
6. **Gestionar:** verificar → controlar el reporte (los KPIs y el mapa cambian
   en vivo) (**PATCH**).
7. **Votar:** 👍 suma votos (participación).
8. **Prevención y quiz:** mostrar la guía y responder el quiz (concientización).

## 4. Valor e impacto (1 min) 📈
- **Alerta anticipada:** cruza cada reporte con la lluvia y la temperatura reales de la semana.
- Trabajamos sobre el **plano oficial** de El Colorado: 28 barrios y 147 calles reales.
- Información **accesible y comprensible** (mapa + semáforo + guía).
- **Participación real de la comunidad** (reportar, votar, aprender).
- Cero costos de infraestructura: funciona en una PC local con XAMPP.
- Escalable: se adapta a otras localidades agregando barrios en la BD.

## 5. Cierre (15 seg) 🙌
> "CaszaMosqui: la comunidad identifica, el mapa organiza y juntos prevenimos."

## Cómo levantar el proyecto desde cero (para el jurado)

```bash
# 1. Copiar la carpeta a C:\xampp\htdocs\formosahack-2026
# 2. Con Apache y MySQL corriendo:
C:\xampp\php\php.exe scripts\setup.php
# 3. Abrir: http://localhost/formosahack-2026/
#    Clima: http://localhost/formosahack-2026/api/clima.php
#    API de ejemplo: http://localhost/formosahack-2026/api/?route=stats
```

## Capturas de pantalla

(agregar capturas o gif de la demo aquí)

---

*Equipo CaszaMosqui — FormosaHack 2026*