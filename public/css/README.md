# Estructura CSS Modular

## Archivos CSS del Proyecto

### 1. **base.css** - Estilos Base

- Reset CSS universal
- Estilos generales del body
- Horizontal rules (hr)
- Responsive móvil general

### 2. **layout.css** - Layout y Estructura

- `.container` - Contenedor principal
- `.header-juego` - Header del juego
- `.header-buttons` - Botones del header
- `h1` - Título principal
- Media queries para responsive

### 3. **botones.css** - Estilos de Botones

- `.btn-configuracion` - Botón de configuración
- `.btn-pausa` - Botón de pausa
- `.botones-control` - Contenedor de botones
- `.btn-reiniciar` - Botón reiniciar
- `.btn-revancha` - Botón revancha
- `.btn-deshacer` - Botón deshacer
- `.btn-guardar` - Botón guardar
- `.btn-cargar` - Botón cargar
- `.btn-*-header` - Botones especiales de header

### 4. **inicio.css** - Pantalla Arcade (Inicio)

- `.pantalla-arcade` - Contenedor pantalla completa
- `.titulo-arcade` - Título con caballos
- `.icono-titulo` - Caballos animados
- `.titulo-contenedor` - Contenedor del título
- `.subtitulo-arcade` - Subtítulo
- `@keyframes float-chess` - Animación caballos
- `@keyframes elegant-glow` - Animación brillo título
- `.gif-arcade-fondo` - GIF de fondo
- `.overlay-botones` - Overlay oscuro
- `.botones-arcade` - Contenedor botones
- `.btn-arcade` - Botones arcade base
- `.btn-nueva-arcade` - Botón nueva partida
- `.btn-cargar-arcade` - Botón cargar partida
- Media queries arcade

### 5. **style.css** - Mensajes y Estilos Generales

- `.mensaje` - Mensajes generales
- `.mensaje.terminada` - Mensaje de partida terminada
- `.mensaje.pausa` - Mensaje de pausa
- Otros estilos específicos

### 6. **config.css** - Formulario de Configuración

- Estilos del formulario de jugadores
- Selección de avatares
- Configuración de tiempo

### 7. **modal.css** - Estilos de Modales

- `.modal-overlay` - Overlay del modal
- `.modal-content` - Contenido del modal
- Botones dentro de modales
- Animaciones

### 8. **tablero.css** - Tablero de Ajedrez

- `.tablero-contenedor` - Contenedor del tablero
- `.casilla` - Casillas del tablero
- `.btn-pieza-casilla` - Botones de piezas
- Animaciones de movimiento

### 9. **relojes.css** - Relojes de Tiempo

- `.relojes-container` - Contenedor de relojes
- `.reloj` - Estilos individuales del reloj
- `.reloj-tiempo` - Tiempo mostrado
- `.reloj-puntos` - Puntuación

### 10. **historial.css** - Historial de Movimientos

- `.historial-movimientos` - Contenedor historial
- `.historial-grid` - Grid de movimientos
- `.movimiento-item` - Elemento de movimiento

## Orden de Carga (en index.php)

```html
<link rel="stylesheet" href="public/css/base.css" />
<!-- Base y reset -->
<link rel="stylesheet" href="public/css/layout.css" />
<!-- Layout -->
<link rel="stylesheet" href="public/css/botones.css" />
<!-- Botones -->
<link rel="stylesheet" href="public/css/inicio.css" />
<!-- Pantalla inicial -->
<link rel="stylesheet" href="public/css/style.css" />
<!-- Mensajes generales -->
<link rel="stylesheet" href="public/css/config.css" />
<!-- Formulario config -->
<link rel="stylesheet" href="public/css/modal.css" />
<!-- Modales -->
<link rel="stylesheet" href="public/css/tablero.css" />
<!-- Tablero -->
<link rel="stylesheet" href="public/css/relojes.css" />
<!-- Relojes -->
<link rel="stylesheet" href="public/css/historial.css" />
<!-- Historial -->
```

## Ventajas de esta Estructura

✅ **Modularidad**: Cada componente en su propio archivo  
✅ **Mantenimiento**: Fácil encontrar y modificar estilos específicos  
✅ **Reutilización**: Estilos compartidos en un único lugar  
✅ **Escalabilidad**: Fácil agregar nuevos componentes  
✅ **Claridad**: Cada archivo tiene responsabilidad única

## Notas

- **base.css** debe cargar primero (reset)
- **layout.css** después (estructura general)
- **botones.css** y **inicio.css** son independientes
- **style.css** contiene estilos generales que aplican a varias pantallas
- Los módulos específicos (config, modal, tablero, relojes, historial) cargan al final
