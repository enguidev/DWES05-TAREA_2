<?php

/**
 * Funciones de controladores para la aplicación de ajedrez
 */

/**
 * Procesa la petición AJAX para actualizar relojes
 */
function procesarAjaxUpdateClocks()
{
  header('Content-Type: application/json');

  // Si no hay sesión de partida activa, devolver estado vacío
  if (!isset($_SESSION['tiempo_blancas']) || !isset($_SESSION['tiempo_negras']) || !isset($_SESSION['reloj_activo'])) {
    echo json_encode([
      'tiempo_blancas' => 0,
      'tiempo_negras' => 0,
      'reloj_activo' => 'blancas',
      'pausa' => false,
      'sin_partida' => true
    ]);
    session_write_close();
    exit;
  }

  $ahora = time();

  // Actualizar tiempo solo si la partida no está en pausa y no ha terminado por tiempo
  if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) {
    // Si ya se detectó tiempo agotado, no actualizar más
    if (!isset($_SESSION['partida_terminada_por_tiempo'])) {
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
  }

  // Detectar si el tiempo se agotó y terminar la partida (solo una vez)
  if (!isset($_SESSION['partida_terminada_por_tiempo'])) {
    if ($_SESSION['tiempo_blancas'] <= 0) {
      $_SESSION['partida_terminada_por_tiempo'] = 'negras';
      $_SESSION['tiempo_blancas'] = 0; // Asegurar que esté en 0
    } elseif ($_SESSION['tiempo_negras'] <= 0) {
      $_SESSION['partida_terminada_por_tiempo'] = 'blancas';
      $_SESSION['tiempo_negras'] = 0; // Asegurar que esté en 0
    }
  }

  // Verificar si la partida está terminada
  $partidaObj = isset($_SESSION['partida']) ? unserialize($_SESSION['partida']) : null;
  $partidaTerminada = ($partidaObj && $partidaObj->estaTerminada()) ? true : false;

  echo json_encode([
    'tiempo_blancas' => $_SESSION['tiempo_blancas'],
    'tiempo_negras' => $_SESSION['tiempo_negras'],
    'reloj_activo' => $_SESSION['reloj_activo'],
    'pausa' => isset($_SESSION['pausa']) ? $_SESSION['pausa'] : false,
    'sin_partida' => false,
    'partida_terminada' => $partidaTerminada
  ]);
  session_write_close();
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

  // Manejar avatares
  $avatarBlancas = null;
  $avatarNegras = null;

  if (!empty($_POST['avatar_blancas'])) {
    if ($_POST['avatar_blancas'] === 'custom') {
      $avatarBlancas = manejarSubidaAvatar('avatar_custom_blancas', 'blancas');
    } elseif ($_POST['avatar_blancas'] !== 'default') {
      $avatarBlancas = $_POST['avatar_blancas'];
    }
  }

  if (!empty($_POST['avatar_negras'])) {
    if ($_POST['avatar_negras'] === 'custom') {
      $avatarNegras = manejarSubidaAvatar('avatar_custom_negras', 'negras');
    } elseif ($_POST['avatar_negras'] !== 'default') {
      $avatarNegras = $_POST['avatar_negras'];
    }
  }

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
  unset($_SESSION['partida_terminada_por_tiempo']); // Limpiar mensaje de tiempo agotado
  unset($_SESSION['avatar_blancas']);
  unset($_SESSION['avatar_negras']);
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

/**
 * Revancha: nueva partida manteniendo jugadores, avatares y configuración
 */
function revanchaPartida()
{
  if (!isset($_SESSION['partida'])) {
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
  }

  $partidaActual = unserialize($_SESSION['partida']);
  $jugadores = $partidaActual->getJugadores();

  // Obtener nombres de jugadores
  $nombreBlancas = $jugadores['blancas']->getNombre();
  $nombreNegras = $jugadores['negras']->getNombre();

  // Crear nueva partida con los mismos nombres
  $_SESSION['partida'] = serialize(new Partida($nombreBlancas, $nombreNegras));
  $_SESSION['casilla_seleccionada'] = null;

  // Resetear tiempos al valor inicial configurado
  if (isset($_SESSION['config']['tiempo_inicial'])) {
    $_SESSION['tiempo_blancas'] = $_SESSION['config']['tiempo_inicial'];
    $_SESSION['tiempo_negras'] = $_SESSION['config']['tiempo_inicial'];
  }

  $_SESSION['reloj_activo'] = 'blancas';
  $_SESSION['ultimo_tick'] = time();
  $_SESSION['pausa'] = false;
  unset($_SESSION['partida_terminada_por_tiempo']);

  // Mantener avatares y configuración visual (ya están en sesión)

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

          // Promoción elegible (PHP puro): si la pieza en destino es peón y puede promoverse
          $piezaEnDestino = obtenerPiezaEnCasilla($destino, $partida);
          if ($piezaEnDestino instanceof Peon && $piezaEnDestino->puedePromoverse()) {
            // Guardar contexto de promoción en sesión
            $_SESSION['promocion_en_curso'] = [
              'color' => $turnoAnterior,
              'posicion' => $destino
            ];
            // Pausar partida para elegir pieza
            $_SESSION['pausa'] = true;
          }
        }

        $_SESSION['casilla_seleccionada'] = null;
        $_SESSION['partida'] = serialize($partida);
      }
    }
  }
}

/**
 * Confirma una promoción de peón eligiendo la pieza destino (PHP puro)
 */
function procesarConfirmarPromocion()
{
  if (!isset($_SESSION['promocion_en_curso']) || !isset($_SESSION['partida'])) {
    return;
  }

  $tipo = isset($_POST['tipo_promocion']) ? $_POST['tipo_promocion'] : null;
  $validos = ['Dama', 'Torre', 'Alfil', 'Caballo'];
  if (!$tipo || !in_array($tipo, $validos)) {
    return;
  }

  $partida = unserialize($_SESSION['partida']);
  $color = $_SESSION['promocion_en_curso']['color'];
  $pos = $_SESSION['promocion_en_curso']['posicion'];

  $jugadores = $partida->getJugadores();
  $peon = $jugadores[$color]->getPiezaEnPosicion($pos);
  if ($peon instanceof Peon && $peon->puedePromoverse()) {
    $jugadores[$color]->promoverPeon($peon, $tipo);
    // Mensaje y limpieza
    $partida->setMensaje('¡Promoción a ' . $tipo . '! Turno de ' . $jugadores[$partida->getTurno()]->getNombre() . ' (' . $partida->getTurno() . ')');
  }

  $_SESSION['partida'] = serialize($partida);
  unset($_SESSION['promocion_en_curso']);
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
 * Guarda la partida con nombre específico
 * @param Partida $partida La partida a guardar
 * @param string $nombrePartida Nombre descriptivo de la partida
 * @return string Nombre del archivo generado
 */
function guardarPartida($partida, $nombrePartida = null)
{
  // Generar nombre si no se proporciona
  if (!$nombrePartida) {
    $jugadores = $partida->getJugadores();
    $nombrePartida = $jugadores['blancas']->getNombre() . ' vs ' . $jugadores['negras']->getNombre();
  }

  $timestamp = time();
  $nombreArchivo = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombrePartida) . '_' . $timestamp;

  // Copiar avatares personalizados si existen
  $avatarBlancasGuardado = null;
  $avatarNegrasGuardado = null;

  if (isset($_SESSION['avatar_blancas']) && strpos($_SESSION['avatar_blancas'], 'avatar_blancas_') !== false) {
    $rutaOrigen = __DIR__ . '/../public/' . $_SESSION['avatar_blancas'];
    if (file_exists($rutaOrigen)) {
      $extension = pathinfo($rutaOrigen, PATHINFO_EXTENSION);
      $nombreAvatar = 'avatar_blancas_' . $timestamp . '.' . $extension;
      $rutaDestino = __DIR__ . '/../data/partidas/avatares/' . $nombreAvatar;
      copy($rutaOrigen, $rutaDestino);
      $avatarBlancasGuardado = $nombreAvatar;
    }
  }

  if (isset($_SESSION['avatar_negras']) && strpos($_SESSION['avatar_negras'], 'avatar_negras_') !== false) {
    $rutaOrigen = __DIR__ . '/../public/' . $_SESSION['avatar_negras'];
    if (file_exists($rutaOrigen)) {
      $extension = pathinfo($rutaOrigen, PATHINFO_EXTENSION);
      $nombreAvatar = 'avatar_negras_' . $timestamp . '.' . $extension;
      $rutaDestino = __DIR__ . '/../data/partidas/avatares/' . $nombreAvatar;
      copy($rutaOrigen, $rutaDestino);
      $avatarNegrasGuardado = $nombreAvatar;
    }
  }

  $data = [
    'nombre' => $nombrePartida,
    'fecha' => date('Y-m-d H:i:s', $timestamp),
    'timestamp' => $timestamp,
    'partida' => serialize($partida),
    'historialMovimientos' => $partida->getHistorialMovimientos(),
    'casilla_seleccionada' => $_SESSION['casilla_seleccionada'],
    'tiempo_blancas' => $_SESSION['tiempo_blancas'],
    'tiempo_negras' => $_SESSION['tiempo_negras'],
    'reloj_activo' => $_SESSION['reloj_activo'],
    'config' => $_SESSION['config'],
    'pausa' => $_SESSION['pausa'],
    'avatar_blancas' => isset($_SESSION['avatar_blancas']) ? $_SESSION['avatar_blancas'] : null,
    'avatar_negras' => isset($_SESSION['avatar_negras']) ? $_SESSION['avatar_negras'] : null,
    'avatar_blancas_guardado' => $avatarBlancasGuardado,
    'avatar_negras_guardado' => $avatarNegrasGuardado
  ];

  file_put_contents(__DIR__ . '/../data/partidas/' . $nombreArchivo . '.json', json_encode($data, JSON_PRETTY_PRINT));
  return $nombreArchivo;
}

/**
 * Lista todas las partidas guardadas
 * @return array Array de partidas con metadatos
 */
function listarPartidas()
{
  $partidas = [];
  $archivos = glob(__DIR__ . '/../data/partidas/*.json');

  foreach ($archivos as $archivo) {
    $data = json_decode(file_get_contents($archivo), true);
    if ($data) {
      $partidas[] = [
        'archivo' => basename($archivo, '.json'),
        'nombre' => $data['nombre'] ?? 'Sin nombre',
        'fecha' => $data['fecha'] ?? 'Fecha desconocida',
        'timestamp' => $data['timestamp'] ?? 0
      ];
    }
  }

  // Ordenar por timestamp descendente (más recientes primero)
  usort($partidas, function ($a, $b) {
    return $b['timestamp'] - $a['timestamp'];
  });

  return $partidas;
}

/**
 * Carga una partida específica
 * @param string $nombreArchivo Nombre del archivo sin extensión
 * @return Partida|null La partida cargada o null si no existe
 */
function cargarPartida($nombreArchivo = null)
{
  // Si no se especifica, buscar partida_guardada.json (retrocompatibilidad)
  if ($nombreArchivo === null && file_exists('data/partida_guardada.json')) {
    $data = json_decode(file_get_contents('data/partida_guardada.json'), true);
  } else if ($nombreArchivo !== null) {
    $rutaArchivo = __DIR__ . '/../data/partidas/' . $nombreArchivo . '.json';
    if (!file_exists($rutaArchivo)) {
      return null;
    }
    $data = json_decode(file_get_contents($rutaArchivo), true);
  } else {
    return null;
  }

  if (!$data) {
    return null;
  }

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

  // Restaurar avatares
  if (isset($data['avatar_blancas_guardado']) && $data['avatar_blancas_guardado']) {
    $_SESSION['avatar_blancas'] = 'data/partidas/avatares/' . $data['avatar_blancas_guardado'];
  } else if (isset($data['avatar_blancas'])) {
    $_SESSION['avatar_blancas'] = $data['avatar_blancas'];
  }

  if (isset($data['avatar_negras_guardado']) && $data['avatar_negras_guardado']) {
    $_SESSION['avatar_negras'] = 'data/partidas/avatares/' . $data['avatar_negras_guardado'];
  } else if (isset($data['avatar_negras'])) {
    $_SESSION['avatar_negras'] = $data['avatar_negras'];
  }

  // Restaurar historial de movimientos
  $partidaObj = unserialize($_SESSION['partida']);
  if (isset($data['historialMovimientos']) && is_array($data['historialMovimientos'])) {
    $partidaObj->setHistorialMovimientos($data['historialMovimientos']);
    $_SESSION['partida'] = serialize($partidaObj);
  }

  return $partidaObj;
}

/**
 * Elimina una partida guardada y sus avatares asociados
 * @param string $nombreArchivo Nombre del archivo sin extensión
 * @return bool True si se eliminó correctamente
 */
function eliminarPartida($nombreArchivo)
{
  $rutaArchivo = __DIR__ . '/../data/partidas/' . $nombreArchivo . '.json';

  if (!file_exists($rutaArchivo)) {
    return false;
  }

  // Cargar datos para obtener avatares
  $data = json_decode(file_get_contents($rutaArchivo), true);

  // Eliminar avatares guardados
  if (isset($data['avatar_blancas_guardado']) && $data['avatar_blancas_guardado']) {
    $rutaAvatar = __DIR__ . '/../data/partidas/avatares/' . $data['avatar_blancas_guardado'];
    if (file_exists($rutaAvatar)) {
      unlink($rutaAvatar);
    }
  }

  if (isset($data['avatar_negras_guardado']) && $data['avatar_negras_guardado']) {
    $rutaAvatar = __DIR__ . '/../data/partidas/avatares/' . $data['avatar_negras_guardado'];
    if (file_exists($rutaAvatar)) {
      unlink($rutaAvatar);
    }
  }

  // Eliminar archivo de partida
  unlink($rutaArchivo);
  return true;
}

/**
 * Maneja la subida de un archivo de avatar personalizado
 * @param string $inputName Nombre del campo de archivo
 * @param string $color Color del jugador ('blancas' o 'negras')
 * @return string|null Ruta relativa del archivo subido o null si no se subió
 */
function manejarSubidaAvatar($inputName, $color)
{
  if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
    return null;
  }

  $file = $_FILES[$inputName];
  $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
  $maxSize = 5 * 1024 * 1024; // 5MB

  // Validar tipo de archivo
  // Revisar MIME real con finfo para evitar suplantación
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $realType = $finfo->file($file['tmp_name']);
  if (!in_array($realType, $allowedTypes)) {
    return null;
  }

  // Validar tamaño
  if ($file['size'] > $maxSize) {
    return null;
  }

  // Crear directorio si no existe
  $uploadDir = __DIR__ . '/../public/imagenes/avatares/';
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }

  // Eliminar avatares personalizados previos del mismo color
  $pattern = $uploadDir . 'avatar_' . $color . '_*';
  foreach (glob($pattern) as $oldFile) {
    if (is_file($oldFile)) {
      unlink($oldFile);
    }
  }

  // Generar nombre único
  $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
  $fileName = 'avatar_' . $color . '_' . time() . '_' . uniqid() . '.' . $extension;
  $filePath = $uploadDir . $fileName;

  // Mover archivo
  if (move_uploaded_file($file['tmp_name'], $filePath)) {
    return 'public/imagenes/avatares/' . $fileName;
  }

  return null;
}
