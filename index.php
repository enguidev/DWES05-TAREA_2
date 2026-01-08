<?php
// Iniciamos la session 
session_start();

// Archivos necesarios
require_once 'modelo/Partida.php';
require_once 'src/funciones_auxiliares.php';
require_once 'src/vistas.php';
require_once 'src/controladores.php';

// Manejo de solicitudes AJAX (esto lo he hecho con JS para poder hacerlo en tiempo real, en php sólo al recargar la página
if (isset($_GET['ajax']) && $_GET['ajax'] === 'actualizar_relojes') {
  // Manejamos la actualización de los relojes en tiempo real
  procesarAjaxActualizarRelojes();
}

// Cuando el usuario abre la configuración, pausamos la partida
if (isset($_POST['pausar_desde_configuracion'])) {
  // Si no estaba pausada, la pausamos ahora
  if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) {
    $_SESSION['pausa'] = true;
  }
  // Guardamos la hora actual para controlar el tiempo de pausa
  if (isset($_SESSION['ultimo_tick'])) {
    $_SESSION['ultimo_tick'] = time();
  }
  // Devolvemos una respuesta JSON para confirmar que se pausó
  header('Content-Type: application/json');
  echo json_encode(['ok' => true, 'pausa' => $_SESSION['pausa']]);
  exit;
}

// Cuando el usuario cierra la configuración, la partida se reanuda
// Pero solo si no está guardando configuración al mismo tiempo
if (isset($_POST['reanudar_desde_configuracion']) && !isset($_POST['guardar_configuracion'])) {
  // Detenemos la pausa
  $_SESSION['pausa'] = false;
  // Reseteamos el tiempo para que no cuente los segundos que estuvo pausada
  $_SESSION['ultimo_tick'] = time();
  // Confirmamos que se reanudó
  header('Content-Type: application/json');
  echo json_encode(['ok' => true, 'pausa' => $_SESSION['pausa']]);
  exit;
}

// Si el usuario guarda la configuración, procesamos los cambios
if (isset($_POST['guardar_configuracion'])) {
  // Guardamos los ajustes que cambió
  procesarGuardarConfiguracion();
  // Si además cerró la configuración, reanudamos el juego
  if (isset($_POST['reanudar_desde_configuracion'])) {
    $_SESSION['pausa'] = false;
    $_SESSION['ultimo_tick'] = time();
  }
}

// Estas son las opciones predeterminadas para cualquier partida nueva
$configDefecto = [
  'tiempo_inicial' => 600, // 10 minutos de tiempo inicial
  'incremento' => 0, // Sin tiempo extra por jugada
  'mostrar_coordenadas' => true, // Mostrar letras y números del tablero
  'mostrar_capturas' => true // Mostrar las piezas que se comieron
];

// Si no hay configuración guardada, usamos la predeterminada
if (!isset($_SESSION['config'])) {
  $_SESSION['config'] = $configDefecto;
}

// Si hay partida en curso pero no tenemos registro de pausa, la iniciamos sin pausar
if (isset($_SESSION['partida']) && !isset($_SESSION['pausa'])) {
  $_SESSION['pausa'] = false;
}

// Cuando el usuario envía el formulario para empezar una partida nueva
if (isset($_POST['iniciar_partida'])) {
  // Llamamos a la función que crea la partida con los nombres configurados
  iniciarPartida();
}

// Si desde la pantalla inicial el usuario quiere cargar una partida guardada
if (isset($_POST['cargar_partida_inicial']) && isset($_POST['archivo_partida'])) {
  // Cargamos esa partida
  $partidaCargada = cargarPartida($_POST['archivo_partida']);
  // Si se cargó correctamente, la ponemos en la sesión
  if ($partidaCargada) {
    $_SESSION['partida'] = serialize($partidaCargada);
    $_SESSION['nombres_configurados'] = true;
  }
}

// Si el usuario quiere borrar una partida guardada desde la pantalla inicial
if (isset($_POST['eliminar_partida_inicial']) && isset($_POST['archivo_partida'])) {
  // Borramos esa partida
  eliminarPartida($_POST['archivo_partida']);
}

// Si el usuario hace clic en pausar o reanudar, lo procesamos
if (isset($_POST['alternar_pausa'])) {
  // Cambiamos el estado de pausa
  procesarTogglePausa();
}

// Variable para saber si mostrar el modal de reinicio
$mostrarModalReiniciar = false;
// Si el usuario abre el modal para reiniciar
if (isset($_POST['abrir_modal_reiniciar'])) {
  // Pausamos la partida mientras decide
  if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) {
    $_SESSION['pausa'] = true;
  }
  // Mostramos el modal
  $mostrarModalReiniciar = true;
}

// Variable para saber si mostrar el modal de revancha
$mostrarModalRevancha = false;
// Si el usuario abre el modal para una revancha
if (isset($_POST['abrir_modal_revancha'])) {
  // Pausamos la partida mientras decide
  if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) {
    $_SESSION['pausa'] = true;
  }
  // Mostramos el modal
  $mostrarModalRevancha = true;
}

// Si el usuario cancela un modal, reanudamos la partida
if (isset($_POST['cancelar_modal'])) {
  // Si la partida estaba pausada, la reanudamos
  if (isset($_SESSION['pausa']) && $_SESSION['pausa']) {
    $_SESSION['pausa'] = false;
  }
}

// Si el usuario confirma que quiere reiniciar
if (isset($_POST['confirmar_reiniciar'])) {
  // Reiniciamos la partida (comienza desde el principio)
  reiniciarPartida();
}

// Si el usuario confirma que quiere jugar otra partida
if (isset($_POST['confirmar_revancha'])) {
  // Iniciamos una nueva partida con los mismos jugadores
  revanchaPartida();
}

// Variables para el modal de guardar partida
$mostrarModalGuardar = false;
$nombrePartidaSugerido = '';

// Si el usuario abre el modal para guardar
if (isset($_POST['abrir_modal_guardar']) && isset($_SESSION['partida'])) {
  // Obtenemos la partida de la sesión
  $partida = unserialize($_SESSION['partida']);
  // Obtenemos los nombres de los jugadores
  $jugadores = $partida->getJugadores();
  // Generamos un nombre sugerido con los jugadores y la fecha actual
  $nombrePartidaSugerido = $jugadores['blancas']->getNombre() . ' vs ' . $jugadores['negras']->getNombre() . ' - ' . date('d/m/Y H:i');
  // Mostramos el modal
  $mostrarModalGuardar = true;
}

// Si el usuario confirma que quiere guardar la partida
if (isset($_POST['confirmar_guardar']) && isset($_POST['nombre_partida']) && isset($_SESSION['partida'])) {
  // Obtenemos la partida
  $partida = unserialize($_SESSION['partida']);
  // La guardamos con el nombre que eligió
  guardarPartida($partida, $_POST['nombre_partida']);
  // Ocultamos el modal
  $mostrarModalGuardar = false;
}

// Variable para saber si mostrar el modal de promoción
$mostrarModalPromocion = false;
// Si hay una promoción pendiente (peón llegó al final)
if (isset($_SESSION['promocion_en_curso'])) {
  // Mostramos el modal para que el usuario elija qué pieza
  $mostrarModalPromocion = true;
}

// Si el usuario elige qué pieza quiere en la promoción
if (isset($_POST['confirmar_promocion']) && isset($_POST['tipo_promocion'])) {
  // Procesamos la promoción
  procesarConfirmarPromocion();
  // Ocultamos el modal
  $mostrarModalPromocion = false;
}

// Variable para saber si mostrar el modal de enroque
$mostrarModalEnroque = false;
// Si hay un enroque pendiente (movimiento especial del rey)
if (isset($_SESSION['enroque_pendiente'])) {
  // Pausamos la partida para que el usuario decida
  if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) {
    $_SESSION['pausa'] = true;
  }
  // Mostramos el modal
  $mostrarModalEnroque = true;
}

// Si el usuario confirma que quiere hacer enroque
if (isset($_POST['confirmar_enroque'])) {
  // Procesamos el enroque
  procesarConfirmarEnroque();
  // Ocultamos el modal
  $mostrarModalEnroque = false;
}

// Si el usuario cancela el enroque
if (isset($_POST['cancelar_enroque'])) {
  // Procesamos la cancelación del enroque
  procesarCancelarEnroque();
  // Ocultamos el modal
  $mostrarModalEnroque = false;
}

// Variables para el modal de cargar partida
$mostrarModalCargar = false;
$partidasGuardadas = [];

// Si el usuario abre el modal para cargar una partida
if (isset($_POST['abrir_modal_cargar'])) {
  // Pausamos la partida mientras navega
  if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) {
    $_SESSION['pausa'] = true;
  }
  // Obtenemos la lista de partidas guardadas
  $partidasGuardadas = listarPartidas();
  // Mostramos el modal
  $mostrarModalCargar = true;
}

// Si el usuario elige una partida guardada para cargar
if (isset($_POST['cargar_partida']) && isset($_POST['archivo_partida'])) {
  // Cargamos esa partida
  $partidaCargada = cargarPartida($_POST['archivo_partida']);
  // Si se cargó correctamente, la usamos
  if ($partidaCargada) {
    $partida = $partidaCargada;
    $_SESSION['partida'] = serialize($partida);
  }
}

// Si el usuario quiere borrar una partida guardada
if (isset($_POST['eliminar_partida']) && isset($_POST['archivo_partida'])) {
  // Borramos esa partida
  eliminarPartida($_POST['archivo_partida']);
  // Actualizamos la lista de partidas
  $partidasGuardadas = listarPartidas();
  // Seguimos mostrando el modal
  $mostrarModalCargar = true;
}

// Solo procesamos el juego si hay una partida activa
if (isset($_SESSION['partida'])) {
  // Obtenemos la partida de la sesión
  $partida = unserialize($_SESSION['partida']);

  // Procesamos el movimiento que hizo el usuario
  procesarJugada($partida);

  // Guardamos la partida actualizada en la sesión
  $_SESSION['partida'] = serialize($partida);

  // Si el usuario quiso deshacer el último movimiento
  if (isset($_POST['deshacer'])) {
    // Deshacemos ese movimiento
    deshacerJugada($partida);
  }

  // Si el usuario quiso guardar la partida
  if (isset($_POST['guardar'])) {
    // Guardamos la partida
    guardarPartida($partida);
  }

  // Si el usuario quiso cargar una partida guardada
  if (isset($_POST['cargar'])) {
    // Cargamos la partida
    $partidaCargada = cargarPartida();

    // Si se cargó correctamente, la usamos
    if ($partidaCargada) {
      $partida = $partidaCargada;
    }
  }

  // Si la partida terminó porque se acabó el tiempo
  if (isset($_SESSION['partida_terminada_por_tiempo'])) {
    // Obtenemos al ganador
    $ganador = $_SESSION['partida_terminada_por_tiempo'];
    $jugadores = $partida->getJugadores();
    // Mostramos un mensaje diferente según quién ganó
    if ($ganador === 'blancas') {
      $partida->setMensaje('⏰ ¡Tiempo agotado para las negras! ' . htmlspecialchars($jugadores['blancas']->getNombre()) . ' ha ganado.');
    } else {
      $partida->setMensaje('⏰ ¡Tiempo agotado para las blancas! ' . htmlspecialchars($jugadores['negras']->getNombre()) . ' ha ganado.');
    }
    // Finalizamos la partida
    $partida->terminar();
    // Guardamos la partida en la sesión
    $_SESSION['partida'] = serialize($partida);
    // Limpiamos el indicador de tiempo agotado
    unset($_SESSION['partida_terminada_por_tiempo']);
  }

  // Obtenemos la casilla seleccionada (si la hay)
  $casillaSeleccionada = $_SESSION['casilla_seleccionada'];
  // Obtenemos el resultado actual (qué jugador va ganando)
  $marcador = $partida->marcador();
  // Obtenemos el mensaje actual del juego
  $mensaje = $partida->getMensaje();
  // Obtenemos a quién le toca jugar
  $turno = $partida->getTurno();
  // Obtenemos los dos jugadores
  $jugadores = $partida->getJugadores();

  // Creamos un array para guardar las piezas que se comieron
  $piezasCapturadas = ['blancas' => [], 'negras' => []];

  // Recorremos todas las piezas blancas para ver cuáles se comieron
  foreach ($jugadores['blancas']->getPiezas() as $pieza) {
    // Si la pieza está capturada, la añadimos al listado
    if ($pieza->estCapturada()) $piezasCapturadas['blancas'][] = $pieza;
  }

  // Recorremos todas las piezas negras para ver cuáles se comieron
  foreach ($jugadores['negras']->getPiezas() as $pieza) {
    // Si la pieza está capturada, la añadimos al listado
    if ($pieza->estCapturada()) $piezasCapturadas['negras'][] = $pieza;
  }
}

// Obtenemos la lista de partidas guardadas para mostrar en la pantalla inicial
$partidasGuardadasInicio = listarPartidas();
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