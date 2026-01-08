
6. Guía de Uso
7. Arquitectura del Código
8. Tecnologías Utilizadas
9. Notas Técnicas




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

Tecnología: el historial se genera y persiste en servidor (PHP) mediante
`Partida::registrarMovimientoEnNotacion()` y `getHistorialMovimientos()` en
[modelo/Partida.php](modelo/Partida.php). El desplegable del panel se gestiona
con una pequeña función de cliente en
[public/script.js](public/script.js) (`toggleHistorial()`), sin lógica de juego.






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
