
6. Guía de Uso
7. Arquitectura del Código
8. Tecnologías Utilizadas
9. Notas Técnicas


───────────────────────────────────────────────────────────────────────────────
I. DISEÑO RESPONSIVE
───────────────────────────────────────────────────────────────────────────────

✓ Adaptación automática a diferentes pantallas:
  • Desktop (> 768px): Tablero grande, panel lateral
  • Tablet (480px - 768px): Tablero medio, panel adaptado
  • Móvil (< 480px): Tablero compacto, panel debajo

✓ Imágenes de piezas escalables
✓ Botones táctiles optimizados
✓ Texto legible en todas las resoluciones


================================================================================
6. GUÍA DE USO
================================================================================



───────────────────────────────────────────────────────────────────────────────
JUGANDO UNA PARTIDA
───────────────────────────────────────────────────────────────────────────────


CAPTURAS:
   - Haz clic en una casilla con borde rojo
   - La pieza enemiga será capturada
   - Aparecerá en el panel de capturas

PROMOCIÓN:
   - Si tu peón llega al extremo opuesto
   - Se abre un modal para elegir pieza: Dama, Torre, Alfil o Caballo
   - La partida se pausa hasta que confirmes la elección

ENROQUE:
   - Para intentar enroque: 
     1. Haz clic en el REY (se resaltará en amarillo)
     2. Haz clic en la casilla destino 2 posiciones hacia la torre (G1/G8 para enroque corto, C1/C8 para enroque largo)
   - Si es válido, aparece un modal de confirmación preguntando si deseas hacer el enroque
   - Puedes CONFIRMAR para ejecutarlo (rey y torre se mueven automáticamente) o CANCELAR para posponer el enroque y hacer otro movimiento

PRUEBA DE ENROQUE (DESDE INICIO DE PARTIDA):
   La siguiente secuencia permite hacer enroque corto (O-O) con blancas en el movimiento 7:

   1. Blancas:  E2 → E4  (peón)
   2. Negras:   E7 → E5  (peón)
   3. Blancas:  G1 → F3  (caballo)
   4. Negras:   B8 → C6  (caballo)
   5. Blancas:  F1 → C4  (alfil)
   6. Negras:   D7 → D6  (peón, o cualquier movimiento)
   7. Blancas:  E1 → G1  (REY - Se abrirá modal de confirmación de enroque)
      → Confirma: Rey a G1, Torre a F1
      → Historial: O-O


───────────────────────────────────────────────────────────────────────────────
CONTROLES DE PARTIDA
───────────────────────────────────────────────────────────────────────────────

↶ DESHACER: Retrocede un movimiento (máximo 10)
💾 GUARDAR: Guarda la partida actual (solo en pausa)
📁 CARGAR: Carga una partida guardada
🔄 REINICIAR: Comienza una nueva partida
⚙️ AJUSTES: Configuración visual
❌ SALIR: Abandona la partida


───────────────────────────────────────────────────────────────────────────────
VER HISTORIAL DE MOVIMIENTOS
───────────────────────────────────────────────────────────────────────────────

1. Bajo el tablero, haz clic en el encabezado “📋 Historial de movimientos”.
2. Se desplegará un panel con las jugadas en notación algebraica.
   - Ejemplo: 1. e4 e5, 2. Cf3 Cc6, 3. Ab5 O-O
3. El historial se guarda junto con la partida y se recupera al cargar.

Tecnología: el historial se genera y persiste en servidor (PHP) mediante
`Partida::registrarMovimientoEnNotacion()` y `getHistorialMovimientos()` en
[modelo/Partida.php](modelo/Partida.php). El desplegable del panel se gestiona
con una pequeña función de cliente en
[public/script.js](public/script.js) (`toggleHistorial()`), sin lógica de juego.


───────────────────────────────────────────────────────────────────────────────
PRUEBA DE PROMOCIÓN DE PEÓN (DESDE INICIO DE PARTIDA)
───────────────────────────────────────────────────────────────────────────────

La siguiente secuencia permite promocionar un peón (blancas) en el movimiento 5:

   1. Blancas:  E2 → E4  (peón)
   2. Negras:   A7 → A5  (peón)
   3. Blancas:  E4 → E5  (peón avanza)
   4. Negras:   A5 → A4  (peón avanza)
   5. Blancas:  E5 → E6  (peón avanza)
   6. Negras:   A4 → A3  (peón avanza)
   7. Blancas:  E6 → E7  (peón avanza hacia la promoción)
   8. Negras:   A3 → A2  (peón avanza)
   9. Blancas:  E7 → E8  (PEÓN LLEGA AL FINAL - Modal de promoción)
      → Se abre modal con 4 opciones: Dama, Torre, Alfil, Caballo
      → Selecciona tu pieza preferida
      → Historial: e8=D (si eliges Dama), e8=T (Torre), e8=A (Alfil), e8=C (Caballo)

NOTA: El peón negro también podría llegar a A1 en el movimiento 10, mostrando
promoción en la fila 1 (a1=D, a1=T, etc.).

───────────────────────────────────────────────────────────────────────────────
GLOSARIO DE NOTACIÓN
───────────────────────────────────────────────────────────────────────────────

PIEZAS (letras en español):
- R: Rey, D: Dama, T: Torre, A: Alfil, C: Caballo, Peón: sin letra (ej. `e4`).

SÍMBOLOS:
- x: captura (ej. `Txd4`).
- +: jaque (ej. `Dg7+`).
- #: jaque mate (ej. `Dg7#`).
- O-O: enroque corto; O-O-O: enroque largo.
- =pieza: promoción (ej. `e8=D`, `c1=C`).
- e.p.: captura al paso (ej. `exd6 e.p.`).

EJEMPLOS:
- `1. e4 e5 2. Cf3 Cc6 3. Ab5 O-O`.
- `Txd4`, `Dg7+`, `e8=D`, `exd6 e.p.`.


───────────────────────────────────────────────────────────────────────────────
CÓMO SE GENERA LA NOTACIÓN (INTERNO)
───────────────────────────────────────────────────────────────────────────────

- Motor y registro:
   • La notación se construye en servidor dentro de [modelo/Partida.php](modelo/Partida.php) mediante `generarNotacionAlgebraica()`.
   • Cada jugada válida llama a `registrarMovimientoEnNotacion()` y se añade a `historialMovimientos`.

- Desambiguación de piezas iguales:
   • Si dos piezas del mismo tipo pueden ir a la misma casilla, se añade columna o fila del origen: `Tae1` o `T1e1` según corresponda.
   • Para peones, se indica columna en capturas: `exd5`.

- Capturas, jaque y mate:
   • Captura añade `x` (ej. `Axf7`).
   • Tras aplicar la jugada, si el rey rival queda en jaque se añade `+`; si es jaque mate se añade `#`.

- Enroque y promoción:
   • Enroque se anota como `O-O` (corto) o `O-O-O` (largo).
   • Promoción añade `=pieza` usando la elección del modal: `e8=D`, `c1=C`, etc.

- Captura al paso:
   • Detectada por el último movimiento de peón a doble paso y posición adyacente; se puede anotar como `e.p.` para claridad.

- Persistencia:
   • El array `historialMovimientos` se guarda y se restaura en JSON al usar guardar/cargar partida, por lo que el historial es permanente.

 
───────────────────────────────────────────────────────────────────────────────
SITUACIONES ESPECIALES
───────────────────────────────────────────────────────────────────────────────

JAQUE:
   - Mensaje: "Jaque a [Jugador]"
   - DEBES mover el rey o bloquear la amenaza
   - No puedes hacer movimientos que te dejen en jaque

JAQUE MATE:
   - Mensaje: "¡Jaque mate! [Ganador] ha ganado"
   - Partida finalizada
   - Puedes reiniciar o ver el tablero final

TABLAS:
   - Stalemate: No hay movimientos legales disponibles
   - Material insuficiente: Imposible dar jaque mate
   - Partida terminada en empate

TIEMPO AGOTADO:
   - Si tu tiempo llega a 0:00
   - Pierdes automáticamente
   - El oponente gana


================================================================================
7. ARQUITECTURA DEL CÓDIGO
================================================================================

───────────────────────────────────────────────────────────────────────────────
PARADIGMA: PROGRAMACIÓN ORIENTADA A OBJETOS
───────────────────────────────────────────────────────────────────────────────

JERARQUÍA DE CLASES:

Pieza (Abstracta)
│
├── Torre
├── Caballo
├── Alfil
├── Dama
├── Rey
└── Peon

Jugador
│
└── contiene 16 Piezas (array)

Partida
│
├── contiene 2 Jugadores
├── gestiona turnos
├── valida movimientos
├── detecta jaque/jaque mate
└── mantiene historial


───────────────────────────────────────────────────────────────────────────────
CLASE PIEZA (BASE ABSTRACTA)
───────────────────────────────────────────────────────────────────────────────

ATRIBUTOS:
- posicion: String (notación ajedrez: "A1"-"H8")
- color: String ("blancas" / "negras")
- valor: Int (puntuación)
- haMovido: Bool (para enroque y peón)

MÉTODOS ABSTRACTOS:
- movimiento($nuevaPosicion): Bool
- simulaMovimiento($nuevaPosicion): Array

MÉTODOS COMUNES:
- getPosicion(), setPosicion()
- getColor(), getValor()
- estCapturada(), capturar()
- haMovido(), setHaMovido()
- notacionACoords(), coordsANotacion()


───────────────────────────────────────────────────────────────────────────────
CLASE JUGADOR
───────────────────────────────────────────────────────────────────────────────

RESPONSABILIDADES:
- Inicializar las 16 piezas en posiciones correctas
- Gestionar piezas activas y capturadas
- Proporcionar acceso a piezas específicas
- Implementar promoción de peones
- Calcular puntuación total

MÉTODOS PRINCIPALES:
- getPiezas(): Array
- getPiezaEnPosicion($pos): Pieza|null
- getRey(): Rey|null
- promoverPeon($peon, $tipo): Bool
- haPerdido(): Bool


───────────────────────────────────────────────────────────────────────────────
CLASE PARTIDA (MOTOR PRINCIPAL)
───────────────────────────────────────────────────────────────────────────────

RESPONSABILIDADES:
- Gestionar el flujo completo del juego
- Validar todas las jugadas
- Detectar jaque, jaque mate y tablas
- Cambiar turnos automáticamente
- Mantener historial de movimientos
- Generar mensajes de estado

MÉTODOS PRINCIPALES:
- jugada($origen, $destino): Bool
- estaEnJaque($color): Bool
- esJaqueMate($color): Bool
- esTablas(): Bool
- deshacerJugada(): Bool
- tieneHistorial(): Bool
- muestraTablero(): String (HTML)
- marcador(): Array


───────────────────────────────────────────────────────────────────────────────
SEPARACIÓN DE RESPONSABILIDADES
───────────────────────────────────────────────────────────────────────────────

index.php:
   - Punto de entrada
   - Gestión de sesiones
   - Enrutamiento de acciones
   - Renderizado final

src/controladores.php:
   - Lógica de negocio
   - Procesamiento de formularios
   - Guardado/Carga de partidas
   - Control de tiempo

src/vistas.php:
   - Renderizado HTML
   - Formularios y modales
   - Tablero y componentes visuales

src/funciones_auxiliares.php:
   - Utilidades de tiempo
   - Gestión de archivos
   - Helpers generales

public/script.js:
   - Interactividad cliente
   - AJAX para relojes
   - Validación de formularios
   - Efectos visuales


================================================================================
8. TECNOLOGÍAS UTILIZADAS
================================================================================

BACKEND:
- PHP 7.4+ (POO, Sesiones, Serialización)
- Almacenamiento JSON para persistencia
- Sistema de archivos para avatares

FRONTEND:
- HTML5 semántico
- CSS3 (Flexbox, Grid, Animaciones, Media Queries)
- JavaScript Vanilla (ES6+)
- AJAX con Fetch API

ARQUITECTURA:
- MVC adaptado (Modelo-Vista-Controlador)
- POO con herencia y abstracción
- Separación de responsabilidades
- DRY (Don't Repeat Yourself)


================================================================================
9. NOTAS TÉCNICAS
================================================================================

───────────────────────────────────────────────────────────────────────────────
DECISIONES DE DISEÑO
───────────────────────────────────────────────────────────────────────────────

✓ Notación de ajedrez estándar (A1-H8)
✓ Sistema de coordenadas interno [fila, columna] (0-7)
✓ Serialización de objetos para historial y guardado
✓ Clonación profunda para simular movimientos sin alterar estado
✓ Validación en dos fases: cliente (UX) y servidor (seguridad)

✓ Historial persistente y serializado para guardado/carga
✓ Sincronización de relojes cada 5 segundos (balance precisión/carga)
✓ Pausa automática al abrir modales para evitar pérdidas de tiempo
✓ Promoción mediante modal con elección de pieza
✓ Enroque con confirmación del jugador para permitir diferir la decisión


───────────────────────────────────────────────────────────────────────────────
SEGURIDAD
───────────────────────────────────────────────────────────────────────────────

✓ Validación de subida de imágenes (tipo, tamaño)
✓ Sanitización de nombres de archivo
✓ htmlspecialchars() en todos los inputs de usuario
✓ Validación de existencia de archivos antes de cargar
✓ Sesiones PHP para mantener estado del servidor


───────────────────────────────────────────────────────────────────────────────
OPTIMIZACIONES
───────────────────────────────────────────────────────────────────────────────

✓ Caché de movimientos posibles en cliente
✓ AJAX solo para actualizaciones críticas (relojes)
✓ Lazy loading conceptual (solo carga partida cuando necesario)
✓ CSS minificado en producción
✓ Imágenes optimizadas (PNG transparente, tamaño reducido)


───────────────────────────────────────────────────────────────────────────────
LIMITACIONES CONOCIDAS
───────────────────────────────────────────────────────────────────────────────

⚠ Sin validación de repetición de posiciones (tablas por repetición)
⚠ Mejoras de UX pendientes: animaciones avanzadas, sonidos, temas


───────────────────────────────────────────────────────────────────────────────
POSIBLES MEJORAS FUTURAS
───────────────────────────────────────────────────────────────────────────────

🔮 Animaciones de movimiento y capturas + sonidos
🔮 Resaltado desde historial al pasar el cursor
🔮 Modo multijugador online (WebSockets)
🔮 AI para jugar contra la computadora
🔮 Análisis de partida post-juego
🔮 Exportar partidas en formato PGN
🔮 Temas de tablero personalizables

================================================================================
10. MAPA DE REQUISITOS VS FUNCIONALIDADES
================================================================================

REQUISITOS DEL ENUNCIADO (DWES U5) Y COBERTURA:

- Arquitectura OOP/MVC: Cumplido
   • Clases de piezas, jugadores y partida en modelo/
   • Separación de vistas y controladores en src/

- Gestión de sesiones: Cumplido
   • Estado completo en $_SESSION (partida, tiempos, pausa, config)

- Interactividad con AJAX: Cumplido
   • Sincronización de relojes vía endpoint update_clocks

- Persistencia (JSON): Cumplido
   • Guardar/Cargar/Eliminar partidas en data/partidas/

- Sistema de tiempo: Cumplido
   • Cuenta atrás por turno y fin por tiempo
   • Incremento Fischer por jugada configurable

- Historial y Deshacer: Cumplido
   • Historial limitado y botón de deshacer operativo

- Configuración y UI: Cumplido
   • Ajustes visuales y avatares personalizados

- Modales y confirmaciones: Cumplido
   • Guardar, cargar, nueva partida y revancha con confirmación

PENDIENTES DE MEJORA (NO CRÍTICOS):
- Validación adicional de archivos: endurecer tamaño/mime y manejo de nombres
- UX: Sonidos, temas de tablero y animaciones


================================================================================
11. GUÍA COMPLETA DEL ENROQUE
================================================================================

───────────────────────────────────────────────────────────────────────────────
ENROQUE CORTO (O-O)
───────────────────────────────────────────────────────────────────────────────

El enroque corto es el movimiento especial entre el rey y la torre del flanco
de rey (lado derecho del tablero).

CONDICIONES PARA ENROQUE CORTO:
✓ Rey no ha movido en toda la partida
✓ Torre del flanco de rey (H1/H8) no ha movido en toda la partida
✓ No hay piezas entre el rey y la torre
✓ El rey no está en jaque
✓ El rey no pasa por una casilla atacada (incluida la de destino)

MOVIMIENTO:
- Rey se mueve desde E1 a G1 (blancas) o E8 a G8 (negras)
- Torre se mueve desde H1 a F1 (blancas) o H8 a F8 (negras)
- Ambas piezas se mueven simultáneamente

NOTACIÓN EN HISTORIAL: O-O

SECUENCIA DE PRUEBA (DESDE INICIO):
   1. Blancas:  E2 → E4
   2. Negras:   E7 → E5
   3. Blancas:  G1 → F3
   4. Negras:   B8 → C6
   5. Blancas:  F1 → C4
   6. Negras:   D7 → D6
   7. Blancas:  E1 → G1 (Modal enroque) → Confirma
      → Rey a G1, Torre a F1
      → Historial: O-O


───────────────────────────────────────────────────────────────────────────────
ENROQUE LARGO (O-O-O)
───────────────────────────────────────────────────────────────────────────────

El enroque largo es el movimiento especial entre el rey y la torre del flanco
de dama (lado izquierdo del tablero).

CONDICIONES PARA ENROQUE LARGO:
✓ Rey no ha movido en toda la partida
✓ Torre del flanco de dama (A1/A8) no ha movido en toda la partida
✓ No hay piezas entre el rey y la torre
✓ El rey no está en jaque
✓ El rey no pasa por una casilla atacada (incluida la de destino)

MOVIMIENTO:
- Rey se mueve desde E1 a C1 (blancas) o E8 a C8 (negras)
- Torre se mueve desde A1 a D1 (blancas) o A8 a D8 (negras)
- Ambas piezas se mueven simultáneamente

NOTACIÓN EN HISTORIAL: O-O-O

SECUENCIA DE PRUEBA (DESDE INICIO):
   1. Blancas:  E2 → E4
   2. Negras:   E7 → E5
   3. Blancas:  G1 → F3
   4. Negras:   B8 → C6
   5. Blancas:  B1 → C3
   6. Negras:   F8 → B4
   7. Blancas:  D1 → D2 (Mueve la dama)
   8. Negras:   E8 → C8 (Modal enroque largo) → Confirma
      → Rey a C8, Torre a D8
      → Historial: O-O-O


───────────────────────────────────────────────────────────────────────────────
DIFERENCIAS RESUMIDAS
───────────────────────────────────────────────────────────────────────────────

                    ENROQUE CORTO (O-O)      ENROQUE LARGO (O-O-O)
───────────────────────────────────────────────────────────────────────────────
Torre:              H1 (blancas) / H8        A1 (blancas) / A8 (negras)
                    (negras)

Rey destino:        G1 (blancas) / G8        C1 (blancas) / C8 (negras)
                    (negras)

Torre destino:      F1 (blancas) / F8        D1 (blancas) / D8 (negras)
                    (negras)

Casillas libres:    F1, G1 (blancas)         B1, C1, D1 (blancas)
requeridas:         F8, G8 (negras)          B8, C8, D8 (negras)

Notación:           O-O                      O-O-O

Lado:               Flanco de rey (derecha)  Flanco de dama (izquierda)

Distancia:          Rey 2 casillas derecha   Rey 2 casillas izquierda


================================================================================
CRÉDITOS Y CONTACTO
================================================================================

Proyecto desarrollado como parte de la Tarea DWES05
Grado Superior DAW 2025-26
Desarrollo Web en Entorno Servidor

Fecha: Enero 2026
Versión: 2.0

================================================================================
                            FIN DEL DOCUMENTO
================================================================================