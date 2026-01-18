<?php
// Mostrar errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciamos la session 
session_start();

// Archivos necesarios
require_once 'modelo/Partida.php';
require_once 'src/helpers/funciones_auxiliares.php';
require_once 'src/vistas.php';
require_once 'src/controladores/controladores.php';

// VERIFICAR REINICIO DE PARTIDA - PRIMERO QUE NADA
// Si el usuario confirma volver al inicio, destruimos la sesión ANTES de cargar nada
if (isset($_POST['confirmar_reiniciar'])) {
  // Eliminamos el archivo de partida guardada
  $rutaPartidaGuardada = __DIR__ . '/data/partida_guardada.json';
  if (file_exists($rutaPartidaGuardada)) {
    unlink($rutaPartidaGuardada);
  }

  // Borramos toda la sesión completamente
  $_SESSION = array();
  session_destroy();

  // Redirigimos al index sin sesión
  header('Location: index.php');
  exit();
}

// CARGA AUTOMÁTICA DE PARTIDA GUARDADA
// Si no hay partida en sesión pero hay un archivo guardado, intentamos cargarla
error_log("DEBUG index.php: Verificando cargar partida automática");
error_log("DEBUG: isset(\$_SESSION['partida']) = " . (isset($_SESSION['partida']) ? 'true' : 'false'));

if (!isset($_SESSION['partida'])) {
  $rutaPartidaGuardada = __DIR__ . '/data/partida_guardada.json';
  error_log("DEBUG: Ruta partida guardada: " . $rutaPartidaGuardada);
  error_log("DEBUG: Archivo existe: " . (file_exists($rutaPartidaGuardada) ? 'true' : 'false'));

  if (file_exists($rutaPartidaGuardada)) {
    error_log("DEBUG: Llamando a cargarPartida()");
    $resultado = cargarPartida(); // Carga la partida guardada y restaura la sesión
    error_log("DEBUG: Resultado de cargarPartida(): " . ($resultado ? 'true (Partida)' : 'false'));
    error_log("DEBUG: isset(\$_SESSION['partida']) después de cargarPartida(): " . (isset($_SESSION['partida']) ? 'true' : 'false'));

    if (isset($_SESSION['partida'])) {
      $_SESSION['pantalla_principal_mostrada'] = true; // Mostramos el tablero
      error_log("DEBUG: pantalla_principal_mostrada = true");
    }
  }
}

// Configuración por defecto y estado inicial (ANTES de procesar AJAX)
aplicarConfigPredeterminada(); // Inicializa `$_SESSION['config']` y `$_SESSION['pausa]`

// DEBUG: verificar si se cargó la partida
echo "<!-- DEBUG: partida en sesión: " . (isset($_SESSION['partida']) ? 'SÍ' : 'NO') . " -->";
echo "<!-- DEBUG: nombres_configurados: " . (isset($_SESSION['nombres_configurados']) ? 'SÍ' : 'NO') . " -->";
echo "<!-- DEBUG: pantalla_principal_mostrada: " . (isset($_SESSION['pantalla_principal_mostrada']) ? 'SÍ' : 'NO') . " -->";

// AJAX de relojes en tiempo real
if (isset($_GET['ajax']) && $_GET['ajax'] === 'actualizar_relojes') {
  procesarAjaxActualizarRelojes();
}
// AJAX: estado de reproducción
if (isset($_GET['ajax']) && $_GET['ajax'] === 'reproduccion_estado') {
  procesarAjaxReproduccionEstado();
}
// AJAX: avanzar un paso de reproducción
if (isset($_GET['ajax']) && $_GET['ajax'] === 'reproduccion_paso') {
  procesarAjaxReproduccionPaso();
}

// Resolver acciones y preparar estado para la vista
$estado = resolverAcciones();

// DEBUG TEMPORAL
if (isset($_POST['seleccionar_casilla'])) {
  echo "<!-- DEBUG: POST recibido con seleccionar_casilla = " . htmlspecialchars($_POST['seleccionar_casilla']) . " -->";
  echo "<!-- DEBUG: casillaSeleccionada en estado = " . htmlspecialchars($estado['casillaSeleccionada'] ?? 'null') . " -->";
  if (isset($_SESSION['partida'])) {
    $debugPartida = unserialize($_SESSION['partida']);
    echo "<!-- DEBUG: Mensaje de partida = " . htmlspecialchars($debugPartida->getMensaje()) . " -->";
  }
}

// Extraemos variables de estado para la vista
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
  <!-- Importamos los estilos CSS en orden de especificidad -->
  <link rel="stylesheet" href="public/css/base.css">
  <link rel="stylesheet" href="public/css/layout.css">
  <link rel="stylesheet" href="public/css/botones.css">
  <link rel="stylesheet" href="public/css/inicio.css">
  <link rel="stylesheet" href="public/css/style.css">
  <link rel="stylesheet" href="public/css/config.css">
  <link rel="stylesheet" href="public/css/modal.css">
  <link rel="stylesheet" href="public/css/tablero.css">
  <link rel="stylesheet" href="public/css/relojes.css">
  <link rel="stylesheet" href="public/css/historial.css">
  <!-- Importamos el script de JavaScript para las interacciones -->
  <script src="public/script.js" defer></script>
</head>

<body>
  <!-- Si no se ha decidido si mostrar pantalla principal o configuración... -->
  <?php if (!isset($_SESSION['pantalla_principal_mostrada'])): ?>
    <!-- ...mostramos la pantalla principal -->
    <?php mostrarPantallaPrincipal($partidasGuardadasInicio); ?>

    <!-- Botón para abrir el modal de cargar partida guardada -->
    <?php if (!empty($partidasGuardadasInicio)): ?>
      <form method="post" style="text-align:center; margin-bottom: 20px;">
        <button type="submit" name="abrir_modal_cargar" class="btn-cargar-modal">📁 Cargar Partida Guardada</button>
      </form>
    <?php endif; ?>
    <?php
    // Mostrar el modal solo si el usuario pulsa el botón
    if (!empty($partidasGuardadasInicio) && isset($_POST['abrir_modal_cargar'])) {
      mostrarModalCargarInicial($partidasGuardadasInicio);
    }
    ?>
    <!-- Si ya se han puesto nombres a los jugadores... -->
  <?php elseif (isset($_SESSION['nombres_configurados'])): ?>
    <!-- ...mostramos el tablero y la partida -->
    <div class="container">
      <!-- Modal para cambiar los ajustes del juego -->
      <?php mostrarModalConfig(); ?>

      <!-- Modales de guardar, cargar y opciones de partida -->
      <?php if ($mostrarModalGuardar): ?>
        <?php mostrarModalGuardarPartida($nombrePartidaSugerido); ?>
      <?php endif; ?>

      <?php if ($mostrarModalCargar): ?>
        <?php mostrarModalCargarPartida($partidasGuardadas); ?>
      <?php endif; ?>

      <?php if ($mostrarModalReiniciar): ?>
        <?php mostrarModalConfirmarReiniciar(); ?>
      <?php endif; ?>

      <?php if ($mostrarModalRevancha): ?>
        <?php mostrarModalConfirmarRevancha(); ?>
      <?php endif; ?>

      <!-- Modal cuando un peón llega al final y se puede promocionar -->
      <?php if ($mostrarModalPromocion): ?>
        <?php mostrarModalPromocion(); ?>
      <?php endif; ?>

      <!-- Modal para confirmar el enroque (movimiento especial del rey) -->
      <?php if ($mostrarModalEnroque): ?>
        <?php mostrarModalEnroque(); ?>
      <?php endif; ?>

      <!-- Cabecera con los nombres de los jugadores y opciones -->
      <?php mostrarCabeceraJuego($partida); ?>

      <!-- Mostrar el estado del juego (pausado, en curso, terminado) -->
      <div class="mensaje <?php
                          // Si está pausada, le ponemos una clase especial
                          if (isset($_SESSION['pausa']) && $_SESSION['pausa']) {
                            echo 'pausa';
                          } elseif ($partida && $partida->estaTerminada()) {
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
        } elseif ($partida && $partida->estaTerminada()) {
          // ...mostramos el mensaje de quién ganó
          echo htmlspecialchars($mensaje ?? '');

          // Si está jugándose...
        } else {
          // ...mostramos los mensajes normales (jaque, etc.)
          echo htmlspecialchars($mensaje ?? '');
        }
        ?>
      </div>

      <!-- Los relojes de cada jugador -->
      <?php mostrarRelojes($jugadores, $marcador); ?>

      <!-- El tablero de ajedrez con todas las piezas -->
      <?php mostrarTablero($partida, $casillaSeleccionada, $turno, $piezasCapturadas); ?>
    </div>
    <!-- Si la pantalla principal ya se mostró pero no hay nombres configurados, mostrar formulario -->
  <?php else: ?>
    <!-- ...mostramos el formulario de configuración -->
    <?php mostrarFormularioConfig($partidasGuardadasInicio); ?>

    <!-- Modal para cargar una partida anterior desde la pantalla de configuración -->
    <?php if (!empty($partidasGuardadasInicio)): ?>
      <?php mostrarModalCargarInicial($partidasGuardadasInicio); ?>
    <?php endif; ?>
  <?php endif; ?>
</body>

</html>