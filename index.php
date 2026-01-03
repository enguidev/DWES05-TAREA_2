<?php

// iniciamos la sesión
session_start();

// Archivos necesarios
require_once 'modelo/Partida.php';
require_once 'funciones_auxiliares.php';
require_once 'logica.php';

// Si es una petición AJAX para actualizar relojes
if (isset($_GET['ajax']) && $_GET['ajax'] === 'update_clocks') {

  // Invocamos la función procesarAjaxUpdateClocks (del archivo logica.php)
  procesarAjaxUpdateClocks();
}

// Procesar configuración (solo opciones visuales)
if (isset($_POST['guardar_configuracion'])) {
  procesarGuardarConfiguracion();
}
$configDefecto = [
  'tiempo_inicial' => 600,
  'incremento' => 0,
  'mostrar_coordenadas' => true,
  'mostrar_capturas' => true
];

if (!isset($_SESSION['config'])) {
  $_SESSION['config'] = $configDefecto;
}

// Iniciar partida con nombres Y configuración
if (isset($_POST['iniciar_partida'])) {
  iniciarPartida();
}

// Pausar/Reanudar
if (isset($_POST['toggle_pausa'])) {
  procesarTogglePausa();
}

// Reiniciar
if (isset($_POST['reiniciar'])) {
  reiniciarPartida();
}

// Solo si hay partida
if (isset($_SESSION['partida'])) {
  $partida = unserialize($_SESSION['partida']);

  procesarJugada($partida);

  // Deshacer
  if (isset($_POST['deshacer'])) {
    deshacerJugada($partida);
  }

  // Guardar
  if (isset($_POST['guardar'])) {
    guardarPartida($partida);
  }

  // Cargar
  if (isset($_POST['cargar'])) {
    $partidaCargada = cargarPartida();
    if ($partidaCargada) {
      $partida = $partidaCargada;
    }
  }

  $casillaSeleccionada = $_SESSION['casilla_seleccionada'];
  $marcador = $partida->marcador();
  $mensaje = $partida->getMensaje();
  $turno = $partida->getTurno();
  $jugadores = $partida->getJugadores();

  $piezasCapturadas = ['blancas' => [], 'negras' => []];

  foreach ($jugadores['blancas']->getPiezas() as $pieza) {
    if ($pieza->estCapturada()) $piezasCapturadas['blancas'][] = $pieza;
  }

  foreach ($jugadores['negras']->getPiezas() as $pieza) {
    if ($pieza->estCapturada()) $piezasCapturadas['negras'][] = $pieza;
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Partida de Ajedrez</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="script.js" defer></script>
</head>

<body>
  <?php if (!isset($_SESSION['nombres_configurados'])): ?>
    <?php renderConfigForm(); ?>
  <?php else: ?>
    <div class="container">
      <?php renderModalConfig(); ?>

      <?php renderGameHeader(); ?>

      <div class="mensaje <?php echo $partida->estaTerminada() ? 'terminada' : ''; ?>">
        <?php
        if (isset($_SESSION['pausa']) && $_SESSION['pausa']) {
          echo "⏸️ PARTIDA EN PAUSA";
        } else {
          echo htmlspecialchars($mensaje);
        }
        ?>
      </div>

      <?php renderRelojes($jugadores, $marcador); ?>

      <?php renderTablero($partida, $casillaSeleccionada, $turno, $piezasCapturadas); ?>
    </div>

    <script src="script.js" defer></script>
  <?php endif; ?>
</body>

</html>