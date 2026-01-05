<?php

/**
 * Funciones de controladores para la aplicación de ajedrez
 */

/**
 * Procesa la petición AJAX para actualizar relojes
 */
function procesarAjaxUpdateClocks()
{
  if (isset($_SESSION['tiempo_blancas']) && isset($_SESSION['tiempo_negras']) && isset($_SESSION['reloj_activo'])) {
    $ahora = time();

    // Solo actualizar si no está en pausa
    if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) {
      if (isset($_SESSION['ultimo_tick'])) {
        $tiempoTranscurrido = $ahora - $_SESSION['ultimo_tick'];

        if ($tiempoTranscurrido > 0) {
          if ($_SESSION['reloj_activo'] === 'blancas') {
            $_SESSION['tiempo_blancas'] = max(0, $_SESSION['tiempo_blancas'] - $tiempoTranscurrido);
          } else {
            $_SESSION['tiempo_negras'] = max(0, $_SESSION['tiempo_negras'] - $tiempoTranscurrido);
          }
          $_SESSION['ultimo_tick'] = $ahora;
        }
      } else {
        $_SESSION['ultimo_tick'] = $ahora;
      }
    }

    header('Content-Type: application/json');
    echo json_encode([
      'tiempo_blancas' => $_SESSION['tiempo_blancas'],
      'tiempo_negras' => $_SESSION['tiempo_negras'],
      'reloj_activo' => $_SESSION['reloj_activo'],
      'pausa' => isset($_SESSION['pausa']) ? $_SESSION['pausa'] : false
    ]);
    session_write_close();
  }
  exit;
}

/**
 * Procesa la configuración guardada
 */
function procesarGuardarConfiguracion()
{
  // Solo permitir cambiar opciones visuales, no el tiempo
  $_SESSION['config']['mostrar_coordenadas'] = isset($_POST['mostrar_coordenadas']);
  $_SESSION['config']['mostrar_capturas'] = isset($_POST['mostrar_capturas']);
}

/**
 * Inicia una nueva partida
 */
function iniciarPartida()
{
  $nombreBlancas = !empty($_POST['nombre_blancas']) ? htmlspecialchars(trim($_POST['nombre_blancas'])) : "Jugador 1";
  $nombreNegras = !empty($_POST['nombre_negras']) ? htmlspecialchars(trim($_POST['nombre_negras'])) : "Jugador 2";

  $avatarBlancas = !empty($_POST['avatar_blancas']) && $_POST['avatar_blancas'] !== 'default' ? $_POST['avatar_blancas'] : null;
  $avatarNegras = !empty($_POST['avatar_negras']) && $_POST['avatar_negras'] !== 'default' ? $_POST['avatar_negras'] : null;

  // Guardar configuración elegida
  $_SESSION['config'] = [
    'tiempo_inicial' => (int)$_POST['tiempo_inicial'],
    'incremento' => (int)$_POST['incremento'],
    'mostrar_coordenadas' => isset($_POST['mostrar_coordenadas']),
    'mostrar_capturas' => isset($_POST['mostrar_capturas'])
  ];

  $_SESSION['partida'] = serialize(new Partida($nombreBlancas, $nombreNegras));
  $_SESSION['casilla_seleccionada'] = null;
  $_SESSION['tiempo_blancas'] = $_SESSION['config']['tiempo_inicial'];
  $_SESSION['tiempo_negras'] = $_SESSION['config']['tiempo_inicial'];
  $_SESSION['reloj_activo'] = 'blancas';
  $_SESSION['ultimo_tick'] = time();
  $_SESSION['nombres_configurados'] = true;
  $_SESSION['pausa'] = false;
  $_SESSION['avatar_blancas'] = $avatarBlancas;
  $_SESSION['avatar_negras'] = $avatarNegras;
}

/**
 * Procesa la pausa/reanudación
 */
function procesarTogglePausa()
{
  if (isset($_SESSION['pausa'])) {
    $_SESSION['pausa'] = !$_SESSION['pausa'];

    // Si se reanuda, resetear ultimo_tick
    if (!$_SESSION['pausa']) {
      $_SESSION['ultimo_tick'] = time();
    }
  }
}

/**
 * Reinicia la partida
 */
function reiniciarPartida()
{
  unset($_SESSION['partida']);
  unset($_SESSION['casilla_seleccionada']);
  unset($_SESSION['tiempo_blancas']);
  unset($_SESSION['tiempo_negras']);
  unset($_SESSION['reloj_activo']);
  unset($_SESSION['ultimo_tick']);
  unset($_SESSION['nombres_configurados']);
  unset($_SESSION['pausa']);
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

/**
 * Procesa una jugada
 */
function procesarJugada($partida)
{
  // Procesar jugada (solo si no está en pausa)
  if (isset($_POST['seleccionar_casilla']) && (!isset($_SESSION['pausa']) || !$_SESSION['pausa'])) {
    $casilla = $_POST['seleccionar_casilla'];

    if ($_SESSION['casilla_seleccionada'] === null) {
      $piezaSeleccionada = obtenerPiezaEnCasilla($casilla, $partida);
      if ($piezaSeleccionada && $piezaSeleccionada->getColor() === $partida->getTurno()) {
        $_SESSION['casilla_seleccionada'] = $casilla;
      }
    } else {
      $piezaClickeada = obtenerPiezaEnCasilla($casilla, $partida);
      if ($piezaClickeada && $piezaClickeada->getColor() === $partida->getTurno()) {
        $_SESSION['casilla_seleccionada'] = $casilla;
      } else {
        $origen = $_SESSION['casilla_seleccionada'];
        $destino = $casilla;

        $exito = $partida->jugada($origen, $destino);

        if ($exito) {
          // Actualizar el tiempo antes de cambiar de turno
          $ahora = time();
          $tiempoTranscurrido = $ahora - $_SESSION['ultimo_tick'];

          $turnoAnterior = $_SESSION['reloj_activo'];

          // Restar el tiempo transcurrido al jugador que acaba de mover
          if ($turnoAnterior === 'blancas') {
            $_SESSION['tiempo_blancas'] = max(0, $_SESSION['tiempo_blancas'] - $tiempoTranscurrido);
          } else {
            $_SESSION['tiempo_negras'] = max(0, $_SESSION['tiempo_negras'] - $tiempoTranscurrido);
          }

          // Incremento Fischer (después de restar el tiempo transcurrido)
          if ($_SESSION['config']['incremento'] > 0) {
            if ($turnoAnterior === 'blancas') {
              $_SESSION['tiempo_blancas'] += $_SESSION['config']['incremento'];
            } else {
              $_SESSION['tiempo_negras'] += $_SESSION['config']['incremento'];
            }
          }

          // Cambiar de turno y resetear el tick
          $_SESSION['reloj_activo'] = ($turnoAnterior === 'blancas') ? 'negras' : 'blancas';
          $_SESSION['ultimo_tick'] = time();
        }

        $_SESSION['casilla_seleccionada'] = null;
        $_SESSION['partida'] = serialize($partida);
      }
    }
  }
}

/**
 * Deshace una jugada
 */
function deshacerJugada($partida)
{
  $partida->deshacerJugada();
  $_SESSION['casilla_seleccionada'] = null;
  $_SESSION['partida'] = serialize($partida);
}

/**
 * Guarda la partida
 */
function guardarPartida($partida)
{
  $data = [
    'partida' => serialize($partida),
    'casilla_seleccionada' => $_SESSION['casilla_seleccionada'],
    'tiempo_blancas' => $_SESSION['tiempo_blancas'],
    'tiempo_negras' => $_SESSION['tiempo_negras'],
    'reloj_activo' => $_SESSION['reloj_activo'],
    'config' => $_SESSION['config'],
    'pausa' => $_SESSION['pausa']
  ];
  file_put_contents('data/partida_guardada.json', json_encode($data));
}

/**
 * Carga la partida
 */
function cargarPartida()
{
  if (file_exists('data/partida_guardada.json')) {
    $data = json_decode(file_get_contents('data/partida_guardada.json'), true);
    $_SESSION['partida'] = $data['partida'];
    $_SESSION['casilla_seleccionada'] = $data['casilla_seleccionada'];
    $_SESSION['tiempo_blancas'] = $data['tiempo_blancas'];
    $_SESSION['tiempo_negras'] = $data['tiempo_negras'];
    $_SESSION['reloj_activo'] = $data['reloj_activo'];
    $_SESSION['ultimo_tick'] = time();
    if (isset($data['config'])) {
      $_SESSION['config'] = $data['config'];
    }
    if (isset($data['pausa'])) {
      $_SESSION['pausa'] = $data['pausa'];
    } else {
      $_SESSION['pausa'] = false;
    }
    return unserialize($_SESSION['partida']);
  }
  return null;
}
