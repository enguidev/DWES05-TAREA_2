# Flujo de ejecución y modularización

Este documento explica, punto por punto, cómo se ejecuta la aplicación desde la entrada en el índice y cómo se ha modularizado la lógica.

## Entrada en índice
- Archivo principal: [index.php](index.php)
- Inicio de sesión: `session_start()`.
- Carga de dependencias: [modelo/Partida.php](modelo/Partida.php), [src/funciones_auxiliares.php](src/funciones_auxiliares.php), [src/vistas.php](src/vistas.php), [src/controladores.php](src/controladores.php).
- Ruta AJAX de relojes: si `GET ajax=actualizar_relojes`, se llama a `procesarAjaxActualizarRelojes()` y se responde JSON.
- Configuración por defecto: `aplicarConfigPredeterminada()` inicializa `$_SESSION['config']` y `$_SESSION['pausa]`.
- Resolución de la solicitud: `resolverAcciones()` procesa todos los `POST/GET` y devuelve un estado para la vista.

## ResolverAcciones: qué hace y en qué orden
Implementado en [src/controladores.php](src/controladores.php).

- `pausar_desde_configuracion`: pausa la partida, actualiza `ultimo_tick` y responde JSON.
- `reanudar_desde_configuracion`: reanuda la partida y responde JSON (si no se guarda configuración simultáneamente).
- `guardar_configuracion`: `procesarGuardarConfiguracion()` guarda opciones de interfaz; si se marca reanudar, actualiza tiempo.
- `iniciar_partida`: `iniciarPartida()` crea `Partida`, jugadores, tiempos, turno, avatares y flags de sesión.
- `cargar_partida_inicial`: `cargarPartida(nombre)` restaura estado y activa `nombres_configurados`.
- `eliminar_partida_inicial`: `eliminarPartida(nombre)` elimina JSON y avatares asociados.
- `alternar_pausa`: `procesarTogglePausa()` invierte `$_SESSION['pausa']` y resetea `ultimo_tick` al reanudar.
- Modales:
  - `abrir_modal_reiniciar`: pausa y marca `mostrarModalReiniciar`.
  - `abrir_modal_revancha`: pausa y marca `mostrarModalRevancha`.
  - `cancelar_modal`: reanuda si estaba en pausa.
  - `confirmar_reiniciar`: `reiniciarPartida()` limpia sesión y redirige.
  - `confirmar_revancha`: `revanchaPartida()` reinicia con mismos jugadores/config y redirige.
- Guardar partida:
  - `abrir_modal_guardar`: sugiere nombre (jugadores + fecha) y marca `mostrarModalGuardar`.
  - `confirmar_guardar`: `guardarPartida(partida, nombre)` escribe JSON; cierra modal.
- Promoción:
  - Si existe `promocion_en_curso`: `mostrarModalPromocion`.
  - `confirmar_promocion`: `procesarConfirmarPromocion()` aplica promoción, reanuda y resetea tiempo.
- Enroque:
  - Si existe `enroque_pendiente`: pausa y `mostrarModalEnroque`.
  - `confirmar_enroque`: `procesarConfirmarEnroque()` ejecuta y reanuda.
  - `cancelar_enroque`: `procesarCancelarEnroque()` limpia y reanuda.
- Cargar/eliminar partida (en partida):
  - `abrir_modal_cargar`: pausa, lista con `listarPartidas()` y `mostrarModalCargar`.
  - `cargar_partida`: `cargarPartida(nombre)` y reemplaza `$_SESSION['partida']`.
  - `eliminar_partida`: `eliminarPartida(nombre)`, refresca lista y mantiene modal abierto.
- Procesamiento del juego (si hay partida):
  - `procesarJugada(partida)`: maneja selección/movimiento, actualización de tiempos (incl. incremento), cambio de turno, y detección de promoción (set `promocion_en_curso` y pausa).
  - `deshacerJugada(partida)`: revierte último movimiento y actualiza sesión.
  - `guardarPartida(partida)`: guarda con nombre por defecto si procede.
  - `cargarPartida()`: retrocompatibilidad para `data/partida_guardada.json`.
  - Tiempo agotado: si `partida_terminada_por_tiempo`, se compone mensaje con el ganador, se termina `Partida` y se limpia flag.
  - Preparación de variables de vista: `casillaSeleccionada`, `marcador`, `mensaje`, `turno`, `jugadores` y `piezasCapturadas` (iterando piezas de ambos jugadores).
- Partidas disponibles en inicio: `partidasGuardadasInicio = listarPartidas()`.

## Render de vistas
- Inicio (sin `nombres_configurados`):
  - Formulario: `renderConfigForm(partidasGuardadasInicio)` en [src/vistas.php](src/vistas.php).
  - Modal de carga inicial: `renderModalCargarInicial(partidasGuardadasInicio)` si hay partidas.
- Partida:
  - Configuración: `renderModalConfig()` incluye [src/modal_config.php](src/modal_config.php).
  - Guardar: `renderModalGuardarPartida(nombreSugerido)`.
  - Cargar: `renderModalCargarPartida(partidasGuardadas)`.
  - Reiniciar: `renderModalConfirmarReiniciar()`.
  - Revancha: `renderModalConfirmarRevancha()`.
  - Promoción: `renderModalPromocion()`.
  - Enroque: `renderModalEnroque()`.
  - Cabecera: `renderGameHeader(partida)`.
  - Relojes: `renderRelojes(jugadores, marcador)`.
  - Tablero: `renderTablero(partida, casillaSeleccionada, turno, piezasCapturadas)`.

## Resumen de modularización
- Lógica de comprobaciones trasladada de [index.php](index.php) a `resolverAcciones()` en [src/controladores.php](src/controladores.php).
- Defaults y saneado de estado a `aplicarConfigPredeterminada()` en [src/controladores.php](src/controladores.php).
- `index.php` queda como orquestador: sesión + includes + ruta AJAX + aplicar defaults + resolver acciones + render de vistas.

## Puntos clave de tiempo y pausa
- Relojes en tiempo real: `procesarAjaxActualizarRelojes()` usa `ultimo_tick` y `reloj_activo` para restar tiempo; detecta tiempo agotado y marca `partida_terminada_por_tiempo`.
- Pausas: acciones de configuración, modales de reinicio/revancha/cargar, promoción y enroque establecen `$_SESSION['pausa']` para evitar movimientos y congelar relojes.
- Reanudaciones: al cerrar modales o confirmar operaciones se reanuda y se resetea `ultimo_tick`.
