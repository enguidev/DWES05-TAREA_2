==================================================================
TAREA DWES05 - SIMULADOR DE PARTIDAS DE AJEDREZ
==================================================================

ESTRUCTURA DE ARCHIVOS:
-----------------------

Tu proyecto debe tener la siguiente estructura:

DWES05/
├── index.php           (Interfaz principal)
├── css/
│   └── style.css
├── modelo/
│   ├── Pieza.php       (Clase base)
│   ├── Torre.php
│   ├── Caballo.php
│   ├── Alfil.php
│   ├── Dama.php
│   ├── Rey.php
│   ├── Peon.php
│   ├── Jugador.php
│   └── Partida.php
└── imagenes/
    ├── fichas_blancas/
    │   ├── torre_blanca.png
    │   ├── caballo_blanca.png
    │   ├── alfil_blanca.png
    │   ├── dama_blanca.png
    │   ├── rey_blanca.png
    │   └── peon_blanca.png
    └── fichas_negras/
        ├── torre_negra.png
        ├── caballo_negra.png
        ├── alfil_negra.png
        ├── dama_negra.png
        ├── rey_negra.png
        └── peon_negra.png


SOBRE LAS IMÁGENES:
-------------------

IMPORTANTE: Los nombres de archivos deben usar género MASCULINO:
- torre_blanco.png (no torre_blanca.png)
- caballo_blanco.png (no caballo_blanca.png)
- alfil_blanco.png (no alfil_blanca.png)
- dama_blanco.png (no dama_blanca.png)
- rey_blanco.png (no rey_blanca.png)
- peon_blanco.png (no peon_blanca.png)

Y para las negras:
- torre_negro.png
- caballo_negro.png
- alfil_negro.png
- dama_negro.png
- rey_negro.png
- peon_negro.png

Todas dentro de:
- imagenes/fichas_blancas/
- imagenes/fichas_negras/

Aunque haya 2 torres, 2 caballos, 2 alfiles y 8 peones de cada color,
el código usa la misma imagen para todas las piezas del mismo tipo.


CÓMO FUNCIONA EL JUEGO:
-----------------------

1. Al iniciar, se crea una partida con todas las piezas en sus posiciones iniciales
2. El turno es de las blancas
3. Para mover una pieza:
   a) Haz clic en una pieza del color del turno actual
   b) La casilla se iluminará en amarillo
   c) Aparecerán círculos verdes en las casillas donde puede moverse
   d) Haz clic en una de esas casillas para mover

4. CAPTURAS:
   - Si hay una pieza enemiga en el destino, se capturará automáticamente
   - Las piezas capturadas aparecen en el panel lateral
   - Si capturas el rey enemigo, ganas la partida

5. MARCADOR:
   - Se muestra la suma de puntos de las piezas activas de cada jugador
   - Torre = 5, Dama = 9, Alfil = 3, Caballo = 3, Peón = 1, Rey = 0
   - El jugador en turno tiene un borde dorado en su marcador

6. VALIDACIONES IMPLEMENTADAS:
   ✓ Movimientos según las reglas de cada pieza
   ✓ No puedes mover piezas del rival
   ✓ No puedes mover si hay piezas bloqueando (excepto caballo)
   ✓ No puedes capturar tus propias piezas
   ✓ El peón captura en diagonal
   ✓ El peón puede avanzar 2 casillas en su primer movimiento
   ✓ Fin de partida cuando se captura el rey
   ✓ Visualización de piezas capturadas
   ✓ Indicadores visuales de movimientos posibles y capturas


FUNCIONALIDADES NO IMPLEMENTADAS:
----------------------------------
(Como indica el enunciado)

- Enroque
- Captura al paso
- Coronación de peón
- Jaque (advertencia de que el rey está en peligro)
- Jaque mate (el juego termina al capturar el rey directamente)


CARACTERÍSTICAS DEL CÓDIGO:
---------------------------

1. POO completa con herencia
2. Cada pieza en su propia clase
3. Métodos movimiento() y simulaMovimiento() en todas las piezas
4. Clase Jugador con 16 piezas
5. Clase Partida que gestiona el juego completo
6. Sistema de turnos con indicador visual
7. Validación completa de movimientos
8. Sistema de capturas con panel de piezas capturadas
9. Marcador automático
10. Interfaz visual moderna con efectos y animaciones
11. CSS separado en archivo externo (css/style.css)
12. Código limpio con comentarios explicativos


MEJORAS VISUALES IMPLEMENTADAS:
--------------------------------

✨ Tablero con coordenadas (A-H, 1-8)
✨ Indicadores visuales de turno activo
✨ Panel de piezas capturadas
✨ Círculos verdes para movimientos posibles
✨ Borde rojo pulsante para capturas
✨ Casilla seleccionada resaltada en amarillo
✨ Efectos hover en las piezas
✨ Diseño responsive para móviles
✨ Gradiente de fondo moderno
✨ Animaciones suaves
✨ Instrucciones claras de juego


NOTAS IMPORTANTES:
------------------

- El juego usa sesiones PHP para mantener el estado de la partida
- Todas las clases están en archivos separados (buena práctica POO)
- Los comentarios explican cada método
- El código sigue la estructura del enunciado exactamente
- El CSS está separado en un archivo externo


EVALUACIÓN (10 puntos):
-----------------------

✓ Métodos movimiento() de las subclases de Pieza (2 puntos)
✓ Métodos simulaMovimiento() (2 puntos)
✓ Método jugada() de la clase Partida (2 puntos)
✓ Métodos muestraTablero() y marcador() (2 puntos)
✓ Programa de prueba con interfaz (2 puntos)


PARA PROBAR:
------------

1. Coloca todos los archivos en tu servidor web (XAMPP, WAMP, etc.)
2. Asegúrate de que las imágenes están en las carpetas correctas
3. Verifica que los nombres de las imágenes sean correctos (género masculino)
4. Abre index.php en tu navegador
5. ¡Juega al ajedrez!


¡IMPORTANTE! NOMENCLATURA DEL ARCHIVO:
--------------------------------------

Al comprimir tu trabajo, nómbralo como:
Apellido1_Apellido2_Nombre_DWES05-TAREA.zip

==================================================================