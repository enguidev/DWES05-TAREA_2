<?php
// Iniciamos la session 
session_start();

// Archivos necesarios
require_once 'modelo/Partida.php';
require_once 'src/funciones_auxiliares.php';
require_once 'src/vistas.php';
require_once 'src/controladores.php';

// AJAX de relojes en tiempo real
if (isset($_GET['ajax']) && $_GET['ajax'] === 'actualizar_relojes') {
  procesarAjaxActualizarRelojes();
}

// Configuración por defecto y estado inicial
aplicarConfigPredeterminada();

// Resolver acciones y preparar estado para la vista
$estado = resolverAcciones();

// Extraer variables de estado para la vista
$mostrarModalReiniciar = $estado['mostrarModalReiniciar'];
$mostrarModalRevancha = $estado['mostrarModalRevancha'];
$mostrarModalGuardar = $estado['mostrarModalGuardar'];
$nombrePartidaSugerido = $estado['nombrePartidaSugerido'];
$mostrarModalPromocion = $estado['mostrarModalPromocion'];
$mostrarModalEnroque = $estado['mostrarModalEnroque'];
$mostrarModalCargar = $estado['mostrarModalCargar'];
$partidasGuardadas = $estado['partidasGuardadas'];
$partida = $estado['partida'];
$jugadores = $estado['jugadores'];
$marcador = $estado['marcador'];
$mensaje = $estado['mensaje'];
$turno = $estado['turno'];
$casillaSeleccionada = $estado['casillaSeleccionada'];
$piezasCapturadas = $estado['piezasCapturadas'];
$partidasGuardadasInicio = $estado['partidasGuardadasInicio'];
?>
<!-- HTML -->
<!DOCTYPE html>
<html lang="es">

<head>
  <!-- Configuramos el idioma como español -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Partida de Ajedrez</title>
  <!-- Importamos los estilos CSS -->
  <link rel="stylesheet" href="public/css/style.css">
  <!-- Importamos el script de JavaScript para las interacciones -->
  <script src="public/script.js" defer></script>
</head>

<body>
  <!-- Si todavía no se han puesto nombres a los jugadores... -->
  <?php if (!isset($_SESSION['nombres_configurados'])): ?>
    <!-- ...mostramos el formulario de inicio -->
    <?php renderConfigForm($partidasGuardadasInicio); ?>

    <!-- Modal para cargar una partida anterior desde la pantalla de inicio -->
    <?php if (!empty($partidasGuardadasInicio)): ?>
      <?php renderModalCargarInicial($partidasGuardadasInicio); ?>
    <?php endif; ?>
    <!-- Si ya se han configurado los nombres... -->
  <?php else: ?>
    <!-- ...mostramos el tablero y la partida -->
    <div class="container">
      <!-- Modal para cambiar los ajustes del juego -->
      <?php renderModalConfig(); ?>

      <!-- Modales de guardar, cargar y opciones de partida -->
      <?php if ($mostrarModalGuardar): ?>
        <?php renderModalGuardarPartida($nombrePartidaSugerido); ?>
      <?php endif; ?>

      <?php if ($mostrarModalCargar): ?>
        <?php renderModalCargarPartida($partidasGuardadas); ?>
      <?php endif; ?>

      <?php if ($mostrarModalReiniciar): ?>
        <?php renderModalConfirmarReiniciar(); ?>
      <?php endif; ?>

      <?php if ($mostrarModalRevancha): ?>
        <?php renderModalConfirmarRevancha(); ?>
      <?php endif; ?>

      <!-- Modal cuando un peón llega al final y se puede promocionar -->
      <?php if ($mostrarModalPromocion): ?>
        <?php renderModalPromocion(); ?>
      <?php endif; ?>

      <!-- Modal para confirmar el enroque (movimiento especial del rey) -->
      <?php if ($mostrarModalEnroque): ?>
        <?php renderModalEnroque(); ?>
      <?php endif; ?>

      <!-- Cabecera con los nombres de los jugadores y opciones -->
      <?php renderGameHeader($partida); ?>

      <!-- Mostrar el estado del juego (pausado, en curso, terminado) -->
      <div class="mensaje <?php
                          // Si está pausada, le ponemos una clase especial
                          if (isset($_SESSION['pausa']) && $_SESSION['pausa']) {
                            echo 'pausa';
                          } elseif ($partida->estaTerminada()) {
                            // Si terminó, le ponemos otra clase especial
                            echo 'terminada';
                          }
                          ?>">
        <?php
        // Si la partida está en pausa...
        if (isset($_SESSION['pausa']) && $_SESSION['pausa']) {
          // ...mostramos que está pausada
          echo "⏸️ PARTIDA EN PAUSA";

          // Si la partida terminó...
        } elseif ($partida->estaTerminada()) {
          // ...mostramos el mensaje de quién ganó
          echo htmlspecialchars($mensaje);

          // Si está jugándose...
        } else {
          // ...mostramos los mensajes normales (jaque, etc.)
          echo htmlspecialchars($mensaje);
        }
        ?>
      </div>

      <!-- Los relojes de cada jugador -->
      <?php renderRelojes($jugadores, $marcador); ?>

      <!-- El tablero de ajedrez con todas las piezas -->
      <?php renderTablero($partida, $casillaSeleccionada, $turno, $piezasCapturadas); ?>
    </div>
  <?php endif; ?>
</body>

</html>