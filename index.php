<?php

// iniciamos la sesión
session_start();

// Archivos necesarios
require_once 'modelo/Partida.php';
require_once 'src/funciones_auxiliares.php';
require_once 'src/vistas.php';
require_once 'src/controladores.php';

// Si es una petición AJAX para actualizar relojes
if (isset($_GET['ajax']) && $_GET['ajax'] === 'update_clocks') {

  // Invocamos la función procesarAjaxUpdateClocks (del archivo logica.php)
  procesarAjaxUpdateClocks();
}

// Procesar configuración (solo opciones visuales)
if (isset($_POST['guardar_configuracion'])) {
  procesarGuardarConfiguracion();
}

// Array asociativo con configuración por defecto
$configDefecto = [
  'tiempo_inicial' => 600,
  'incremento' => 0,
  'mostrar_coordenadas' => true,
  'mostrar_capturas' => true
];

if (!isset($_SESSION['config'])) {
  $_SESSION['config'] = $configDefecto;
}

// Iniciar partida con nombres y configuración
if (isset($_POST['iniciar_partida'])) {
  iniciarPartida();
}

// Pausar/Reanudar partida
if (isset($_POST['toggle_pausa'])) {
  procesarTogglePausa();
}

// Reiniciar partida
if (isset($_POST['reiniciar'])) {
  reiniciarPartida();
}

// Mostrar modal para guardar partida
$mostrarModalGuardar = false;
$nombrePartidaSugerido = '';

if (isset($_POST['abrir_modal_guardar']) && isset($_SESSION['partida'])) {
  $partida = unserialize($_SESSION['partida']);
  $jugadores = $partida->getJugadores();
  $nombrePartidaSugerido = $jugadores['blancas']->getNombre() . ' vs ' . $jugadores['negras']->getNombre() . ' - ' . date('d/m/Y H:i');
  $mostrarModalGuardar = true;
}

// Confirmar guardado con nombre
if (isset($_POST['confirmar_guardar']) && isset($_POST['nombre_partida']) && isset($_SESSION['partida'])) {
  $partida = unserialize($_SESSION['partida']);
  guardarPartida($partida, $_POST['nombre_partida']);
  $mostrarModalGuardar = false;
}

// Mostrar modal para cargar partida
$mostrarModalCargar = false;
$partidasGuardadas = [];

if (isset($_POST['abrir_modal_cargar'])) {
  $partidasGuardadas = listarPartidas();
  $mostrarModalCargar = true;
}

// Cargar partida específica
if (isset($_POST['cargar_partida']) && isset($_POST['archivo_partida'])) {
  $partidaCargada = cargarPartida($_POST['archivo_partida']);
  if ($partidaCargada) {
    $partida = $partidaCargada;
    $_SESSION['partida'] = serialize($partida);
  }
}

// Eliminar partida
if (isset($_POST['eliminar_partida']) && isset($_POST['archivo_partida'])) {
  eliminarPartida($_POST['archivo_partida']);
  $partidasGuardadas = listarPartidas();
  $mostrarModalCargar = true;
}

// Solo si hay partida
if (isset($_SESSION['partida'])) {
  $partida = unserialize($_SESSION['partida']);

  // Procesamos jugada
  procesarJugada($partida);

  // Deshacer
  if (isset($_POST['deshacer'])) {

    // Invocamos a la función para deshacer la jugada
    deshacerJugada($partida);
  }

  // Guardar
  if (isset($_POST['guardar'])) {

    // Invocamos a la función para guardar la partida
    guardarPartida($partida);
  }

  // Cargar
  if (isset($_POST['cargar'])) {

    // Invocamos a la función para cargar la partida
    $partidaCargada = cargarPartida();

    // Si se ha cargado correctamente, la usamos
    if ($partidaCargada) {
      $partida = $partidaCargada;
    }
  }

  // Detectar si la partida terminó por tiempo agotado
  if (isset($_SESSION['partida_terminada_por_tiempo'])) {
    $ganador = $_SESSION['partida_terminada_por_tiempo'];
    $jugadores = $partida->getJugadores();
    if ($ganador === 'blancas') {
      $partida->setMensaje('⏰ ¡Tiempo agotado para las negras! ' . htmlspecialchars($jugadores['blancas']->getNombre()) . ' ha ganado.');
    } else {
      $partida->setMensaje('⏰ ¡Tiempo agotado para las blancas! ' . htmlspecialchars($jugadores['negras']->getNombre()) . ' ha ganado.');
    }
    $partida->terminar();
    $_SESSION['partida'] = serialize($partida);
    unset($_SESSION['partida_terminada_por_tiempo']);
  }

  // Actualizamos la session
  $casillaSeleccionada = $_SESSION['casilla_seleccionada'];
  // Guardamos la partida en la sesión
  $marcador = $partida->marcador();
  // Mensaje actual
  $mensaje = $partida->getMensaje();
  // Turno actual
  $turno = $partida->getTurno();
  // Jugadores
  $jugadores = $partida->getJugadores();

  // Array de piezas capturadas
  $piezasCapturadas = ['blancas' => [], 'negras' => []];

  // Recorremos las piezas de ambos jugadores

  // Piezas para las blancas
  foreach ($jugadores['blancas']->getPiezas() as $pieza) {
    // Si la pieza está capturada, la añadimos al array de piezas capturadas
    if ($pieza->estCapturada()) $piezasCapturadas['blancas'][] = $pieza;
  }

  // Piezas para las negras
  foreach ($jugadores['negras']->getPiezas() as $pieza) {
    // Si la pieza está capturada, la añadimos al array de piezas capturadas
    if ($pieza->estCapturada()) $piezasCapturadas['negras'][] = $pieza;
  }
}
?>
<!-- HTML -->
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Partida de Ajedrez</title>
  <link rel="stylesheet" href="public/css/style.css">
  <script src="public/script.js" defer></script>
</head>

<body>
  <!-- Si no se han configurado los nombres de los jugadores... -->
  <?php if (!isset($_SESSION['nombres_configurados'])): ?>
    <!-- ...mostramos el formulario -->
    <?php renderConfigForm(); ?>
    <!-- Si se ha configurado los nombres de los jugadores... -->
  <?php else: ?>
    <!-- ...mostramos la partida -->
    <div class="container">
      <!-- Modal de configuración de jugadores -->
      <?php renderModalConfig(); ?>

      <!-- Modales de guardar/cargar -->
      <?php if ($mostrarModalGuardar): ?>
        <?php renderModalGuardarPartida($nombrePartidaSugerido); ?>
      <?php endif; ?>

      <?php if ($mostrarModalCargar): ?>
        <?php renderModalCargarPartida($partidasGuardadas); ?>
      <?php endif; ?>

      <!-- Cabecera del juego -->
      <?php renderGameHeader(); ?>

      <!-- Mensaje de estado -->
      <div class="mensaje <?php echo $partida->estaTerminada() ? 'terminada' : ''; ?>">
        <?php
        // Si la partida está en pausa...
        if (isset($_SESSION['pausa']) && $_SESSION['pausa']) {

          // ...mostramos mensaje de partida en pausa
          echo "⏸️ PARTIDA EN PAUSA";

          // Si la partida ha terminado...
        } elseif ($partida->estaTerminada()) {

          // ...mostramos el mensaje de final de partida
          echo htmlspecialchars($mensaje);

          // Si hay un mensaje normal...
        } else {

          // ...mostramos el mensaje normal
          echo htmlspecialchars($mensaje);
        }
        ?>
      </div>
      <!-- Temporizadores -->
      <?php renderRelojes($jugadores, $marcador); ?>

      <!-- Tablero -->
      <?php renderTablero($partida, $casillaSeleccionada, $turno, $piezasCapturadas); ?>
    </div>
  <?php endif; ?>
</body>

</html>