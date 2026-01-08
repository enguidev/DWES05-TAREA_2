Engui_Garcia_Carlos_DWES05-TAREA

Tarea basada en lo más fiel posible al juego del ajedrez.
Está desarrollado en su gran mayoría en PHP (con programación Orientada a Objetos),
HTML, CSS y un archivo de JavaScript (script.js) para las funcionalidades extras a 
lo que pide el enunciado de la tarea que no he podido hacer correctamente con PHP.

Es un motor de ajedrez lo más completo posible.

-----------------------------------------------
Pantalla inicial "Configuración de partida":
-----------------------------------------------
  -En la sección "¿Deseas continuar con una partida anterior?"
    -Si hay una/s partida/s guardada/s, primero te da la opción de poder reanudarlas:
      -Si no las hemos renombrado anteriormente al guardar, su nombre/s por defecto serán 
       Nombre_jugador_blancas vs Nombre_jugador_negras - dd/mm/YYYY hh:mm

  -En la sección "Configuración de los jugadores" podremos:
      -Configurar el nombre da cada jugador (en el caso de no poner un nombre, por defecto 
       será jugador 1 y jugador 2 para las blancas y las negras respectivamente).

      -Podremos asignar un avatar a cada jugador siendo las posibilidades:
        -Sin avatar (lo que saldrá una esfera de su color).
        -Cualquier ficha del tablero de su color (torre, caballo, alfil, dama, rey o peón).
        -Un "relieve/busto de perfil de usuario" del color de las fichas con la que juega cada uno.
        -Una imagen personalizada (formatos PNG, JPG o GIF y de máximo 5MB) pudiendo seleccionarla
         desde el explorador de archivos su sistema operativo (seleccionando la opción "subir imagen 
         personalizada" del desplegable y clicando posteriormente en el botón "Elegir imagen"). Se puede 
         ver una previsualización de dicha imagen antes de comenzar la partida y posteriormente se 
         visualizará en los relojes de cada jugador durante la partida.
    
  -En la sección "Configuración del tiempo" podremos:
      -Seleccionar los distintos tiempos iniciales por jugador que nos ofrece:
        -Partida ultra-rápida de 1 minuto (Bullet en términos de ajedrez).
        -Partida rápida de 3 o 5 minutos (Blitz en términos de ajedrez).
        -Partida clásica de 30 min (que es la que está por defecto si no 
         seleccionamos ninguna) o de 60 minutos. Cuando el tiempo sea menos de 1 minuto,
         el reloj parpadeará como alerta de tiempo crítico.

      -Seleccionar un incremento de 1, 2, 3, 5 o 10 segundos extra al reloj del jugador después 
       de cada movimiento (regla de Bobby Fischer, campeón mundial de ajedrez) para que no se 
       acabe la partida sólo por falta de tiempo en posiciones complicadas.

  -En la sección "Configuración de interfaz" podremos:
      -Mostrar u ocultar las coordenadas (A-H, 1-8).
      -Mostrar piezas capturadas.

  - Pulsando el botón "Iniciar Partida Nueva" iniciaremos el juego.


-----------------------------------------------
Pantalla de partida/juego "Partida de Ajedrez":
-----------------------------------------------
  -Empezando de arriba hacia abajo tenemos:
    -Icono representativo de un peón con el título Partida de Ajedrez, botones de ajustes con:
      -Las opciones de interfaz para mostrar u ocultar tanto las coordenadas del tablero (A-H, 1-8)
       como el panel de piezas capturadas (estas 2 cosas se aplicarán en tiempo real)
      -Información del tiempo:
        -Tiempo inicial
        -Incremento Fischer
        -Informa que estos 2 ultimos no se pueden cambiar durante la partida (sólo se podrá en la 
         configuración al realizar al comienzo de una nueva partida)
        -Botón de guardar los cambios (si hemos hecho alguno, si no se mantendrá deshabilitado)
        -Botón de Cancelar para salir del modal
        -La partida se mantendrá pausada mientras tomemos decisiones y no cerremos el modal
        -Configuración persistente entre sesiones
    Botón de pause/play donde podremos pausar o reanudar la partida (cuando la partida esté pausada, 
    se habilitará el botón de "Guardar partida") 

    -Contenedor de información e iteración con el/los usuarios/jugadores:
      - Turno actual
      - Jaque / Jaque Mate
      - Errores de movimiento
      - Promoción de peón
      - Fin de partida


    -Contadores de tiempo restante de cada jugador, sus avatares (si los tuvieran), nombres y puntuación 
     En principio empiezan por cero puntos y van sumando conforme vayan capturando piezas del contrario:
      - Dama = 9 pts
      - Torre = 5 pts
      - Alfil = 3 pts
      - Caballo = 3 pts
      - Peón = 1 pt
      - Rey = 0 pts
      

    -Tablero de juego (8x8) con patrón ajedrezado, con las fichas de ambos jugadores, marco de coordenadas 
     (A-H, 1-8) opcionales, indicadores visuales de movimientos posibles:
       -Resaltado amarillo de casilla seleccionada
       -Círculos verdes para movimientos válidos
       -Borde rojo pulsante para capturas
     y paneles laterales de fichas capturadas de cada jugador

    -Fila de botones con las siguientes funciones:
      -Botón de deshacer movimiento/s (hasta 10 movimientos)
      -Botón de revancha para volver a hacer una partida con la misma 
       configuración de jugadores, tiempo y vista
      -Botón para guardar una partida y así poder reanudarla posteriormente cuando se desee:
        -Se puede editar el nombre de guardado que viene por defecto
        -Se almacena en formato JSON con el estado completo de piezas, tiempo, turno e historial
        -Sólo estará disponible cuando pongamos la partida en pausa
      -Botón de nueva partida para comenzar una nueva partida (con ventana modal de conformación por si hemos 
       clicado sin querer dicho botón)
      
    -Desplegable de historial de movimientos en formato algebraico de cada jugador

    -Desplegable de reglas y controles del juego

  -Se puede meter en la configuración en tiempo de juego pulsando el engranaje de la aparte superior 
   del tablero
  -Se puede pausar y reanudar la partida con el botón de pause/play
  -Validación de reglas
  -Detección de jaque, jaque mate y tablas
  -Cuando llega un peón a la parte contraria, el usuario/jugador puede elegir por 
   cual ficha promocionar (una Dama, una Torre, un Alfil o un Caballo).
  -Hay un reloj de tiempo por jugador el cual se puede Configurar
  -Guardar partidas
  -Deshacer movimientos
  -Detección de piezas bloqueando caminos
  -Validación de capturas (no puedes capturar tus propias piezas)
  -Control de turnos alternados
  -Detección de movimientos ilegales
  -Cuando se acabe el tiempo de alguno de los jugadores, se acabará la partida y se 
   informará de quien ha perdido y quien ha ganado
  -Se podrá realizar otra partida con la misma configuración de jugadores, tiempo, etc 
   pulsando el botón "Revancha" disponible en la parte inferior del tablero de juego

  ───────────────────────────────────────────────────────────────────────────────
B. REGLAS AVANZADAS
───────────────────────────────────────────────────────────────────────────────

✓ JAQUE: Detecta cuando el rey está amenazado
✓ JAQUE MATE: Detecta cuando no hay movimientos legales para salir del jaque
✓ TABLAS (EMPATE):
  • Stalemate: No hay movimientos legales pero no hay jaque
  • Material insuficiente: Solo quedan reyes
  • Rey + Alfil vs Rey
  • Rey + Caballo vs Rey

✓ PROMOCIÓN DE PEÓN:
   • Al llegar al extremo opuesto se abre un modal
   • Elección de pieza: Dama, Torre, Alfil o Caballo
   • La partida se pausa hasta confirmar la promoción

✓ ENROQUE:
   • Implementado con confirmación del jugador vía modal
   • Para iniciar: mueve el rey 2 casillas (E→G para corto, E→C para largo)
   • Si las condiciones se cumplen, aparece un modal preguntando si deseas ejecutar el enroque
   • Puedes confirmar o cancelar (si cancelas, el rey no se mueve y conservas la opción)
   • Validación completa: piezas sin mover, casillas libres y sin jaque intermedio

✓ CAPTURA AL PASO:
   • Implementada: disponible inmediatamente tras avance doble del peón rival
   • Detección por último movimiento y posición adyacente

✓ PREVENCIÓN DE MOVIMIENTOS ILEGALES:
  • No puedes moverte si dejas a tu rey en jaque
  • Validación en tiempo real

  -Estructura del proyecto



-----------------------------------------------
Jugando una partida:
-----------------------------------------------
PASO 1: Seleccionar pieza
   - Haz clic en una pieza de tu color
   - Verás círculos verdes en movimientos válidos
   - Bordes rojos pulsantes indican capturas posibles

PASO 2: Mover pieza (se podrá deshacer posteriormente hasta los 10 últimos movimientos 
clicando el botón "Deshacer"")
   - Haz clic en una casilla marcada en verde
   - La pieza se moverá automáticamente
   - El turno pasará al otro jugador

DESELECCIONAR:
   - Haz clic en otra pieza tuya
   - O haz clic en una casilla vacía sin marca

CAPTURAS:
   - Haz clic en una casilla con borde rojo
   - La pieza enemiga será capturada
   - Aparecerá en tu panel lateral de capturas

PROMOCIÓN:
   - Si tu peón llega al extremo opuesto
   - Se abre un modal para elegir pieza: Dama, Torre, Alfil o Caballo
   - La partida se pausa mientras tengamos abierto el modal

ENROQUE:
   - Para intentar enroque: 
     1-. Haz clic en el REY (se resaltará en amarillo)
     2-. Haz clic en la casilla donde quieres mover el rey (G1/G8 para enroque corto,
         C1/C8 para enroque largo)
     3-. Haz clic en la TORRE correspondiente (H1/H8 para enroque corto, A1/A8 para 
         enroque largo)
   - Si es válido, aparece un modal de confirmación preguntando si deseas hacer el enroque
   - Puedes CONFIRMAR para ejecutarlo (rey y torre se mueven automáticamente a sus posiciones 
     finales) o CANCELAR para posponer el enroque y hacer otro movimiento

-----------------------------------------------
Historial de movimientos:
-----------------------------------------------
  1-. Bajo el tablero, haz clic en el encabezado “Historial de movimientos”.
  2-. Se desplegará un panel con las jugadas en notación algebraica.
    - Ejemplo: 1. e4 e5, 2. Cf3 Cc6, 3. Ab5 O-O
  3-. El historial se guarda junto con la partida y se recupera al cargar.

-----------------------------------------------
GLOSARIO DE NOTACIÓN
-----------------------------------------------

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


Aún se podría mejorar más:
  -Partida sin tiempo
  -Más información al usuario.
  -Devolver tiempo perdido a los usuarios al deshacer movimientos
  -Mejoras de UX:
    -Animaciones.
    -Más información al usuario (como por ejemplo al entrar en ajustes durante la partida, 
     informar de más cosas como el tiempo qu ele queda a cada jugador, fichas capturadas y 
     puntuación de cada jugador, más opciones en ese modal que sólo están en la pantalla de 
     partida, etc)
    -Sonidos (al mover las fichas, acabar el juego, aviso acústico de alguna información, 
     terminación de partida, etc) 
    -Temas (Oscuro/Light o personalización más personalizada y completa)
    -Videos explicativos (de ejemplos de uso)
    -Pequeño tour de para que sirve cada elemento del juego
    -Resaltado del cursor 
  -Validación de tablas
  -Modo multijugador online con WebSockets
  -Guardar puntuaciones con nombres al terminar
  -Que se pudiera jugar contra la computadora, pero eso ya es una funcionalidad muy avanzada
