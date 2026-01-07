<?php
// Funciones de controladores para la aplicación de ajedrez

/**
 * Actualiza los relojes de la partida en tiempo real
 * Recibe peticiones AJAX desde JavaScript para mantener los tiempos sincronizados
 */
function procesarAjaxActualizarRelojes()
{
  // Le decimos al navegador que vamos a devolver JSON
  header('Content-Type: application/json');

  // Si no hay una partida activa, devolvemos valores vacíos
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

  // Solo restamos tiempo si la partida no está pausada y no se acabó el tiempo
  if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) {
    // Si el tiempo no se agotó, seguimos contando
    if (!isset($_SESSION['partida_terminada_por_tiempo'])) {
      // Si ya tuvimos un registro anterior, calculamos el tiempo que ha pasado
      if (isset($_SESSION['ultimo_tick'])) {
        // Restamos la hora actual a la última vez que actualizamos
        $tiempoTranscurrido = $ahora - $_SESSION['ultimo_tick'];

        // Si pasó algún segundo, lo restamos del reloj del jugador actual
        if ($tiempoTranscurrido > 0) {
          // Si es turno de blancas, restamos de su tiempo
          if ($_SESSION['reloj_activo'] === 'blancas') {
            $_SESSION['tiempo_blancas'] = max(0, $_SESSION['tiempo_blancas'] - $tiempoTranscurrido);
          } else {
            // Si no, restamos de las negras
            $_SESSION['tiempo_negras'] = max(0, $_SESSION['tiempo_negras'] - $tiempoTranscurrido);
          }
          // Actualizamos la última vez que contamos tiempo
          $_SESSION['ultimo_tick'] = $ahora;
        }
      } else {
        // En la primera ejecución, solo registramos la hora
        $_SESSION['ultimo_tick'] = $ahora;
      }
    }
  }

  // Verificamos si algún jugador se quedó sin tiempo (solo una vez)
  if (!isset($_SESSION['partida_terminada_por_tiempo'])) {
    // Si las blancas se acabaron el tiempo, ganan las negras
    if ($_SESSION['tiempo_blancas'] <= 0) {
      $_SESSION['partida_terminada_por_tiempo'] = 'negras';
      // Aseguramos que el tiempo sea exactamente 0
      $_SESSION['tiempo_blancas'] = 0;
    } elseif ($_SESSION['tiempo_negras'] <= 0) {
      // Si las negras se acabaron el tiempo, ganan las blancas
      $_SESSION['partida_terminada_por_tiempo'] = 'blancas';
      // Aseguramos que el tiempo sea exactamente 0
      $_SESSION['tiempo_negras'] = 0;
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
 * Guarda los ajustes que el usuario cambió en el modal de configuración
 */
function procesarGuardarConfiguracion()
{
  // Guardamos si mostrar o no las coordenadas del tablero
  $_SESSION['config']['mostrar_coordenadas'] = isset($_POST['mostrar_coordenadas']);
  // Guardamos si mostrar o no las piezas capturadas
  $_SESSION['config']['mostrar_capturas'] = isset($_POST['mostrar_capturas']);
}

/**
 * Crea una nueva partida con los jugadores y configuración elegida
 */
function iniciarPartida()
{
  // Obtenemos los nombres de los jugadores, eliminando espacios al principio y final
  $nombreBlancas = !empty($_POST['nombre_blancas']) ? htmlspecialchars(trim($_POST['nombre_blancas'])) : "Jugador 1";
  $nombreNegras = !empty($_POST['nombre_negras']) ? htmlspecialchars(trim($_POST['nombre_negras'])) : "Jugador 2";

  // Procesamos los avatares que eligieron los jugadores
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

  // Guardamos la configuración elegida por el usuario
  $_SESSION['config'] = [
    'tiempo_inicial' => (int)$_POST['tiempo_inicial'], // Tiempo base para cada jugador
    'incremento' => (int)$_POST['incremento'], // Tiempo extra por movimiento
    'mostrar_coordenadas' => isset($_POST['mostrar_coordenadas']), // Mostrar letras y números
    'mostrar_capturas' => isset($_POST['mostrar_capturas']) // Mostrar piezas capturadas
  ];

  // Creamos una nueva partida con los nombres de los jugadores
  $_SESSION['partida'] = serialize(new Partida($nombreBlancas, $nombreNegras));
  // Al inicio, no hay casilla seleccionada
  $_SESSION['casilla_seleccionada'] = null;
  // Inicializamos los tiempos de ambos jugadores
  $_SESSION['tiempo_blancas'] = $_SESSION['config']['tiempo_inicial'];
  $_SESSION['tiempo_negras'] = $_SESSION['config']['tiempo_inicial'];
  // Las blancas siempre comienzan
  $_SESSION['reloj_activo'] = 'blancas';
  // Registramos la hora actual para contar el tiempo
  $_SESSION['ultimo_tick'] = time();
  // Marcamos que ya se configuró la partida
  $_SESSION['nombres_configurados'] = true;
  // La partida no está pausada al inicio
  $_SESSION['pausa'] = false;
  // Guardamos los avatares elegidos
  $_SESSION['avatar_blancas'] = $avatarBlancas;
  $_SESSION['avatar_negras'] = $avatarNegras;
}

/**
 * Pausa o reanuda la partida según su estado actual
 */
function procesarTogglePausa()
{
  // Si existe el indicador de pausa, lo invertimos
  if (isset($_SESSION['pausa'])) {
    $_SESSION['pausa'] = !$_SESSION['pausa'];

    // Si reanudamos la partida, reseteamos el contador de tiempo
    if (!$_SESSION['pausa']) {
      $_SESSION['ultimo_tick'] = time();
    }
  }
}

/**
 * Borra toda la partida y vuelve a la pantalla de inicio
 */
function reiniciarPartida()
{
  // Borramos toda la información de la partida
  unset($_SESSION['partida']);
  unset($_SESSION['casilla_seleccionada']);
  unset($_SESSION['tiempo_blancas']);
  unset($_SESSION['tiempo_negras']);
  unset($_SESSION['reloj_activo']);
  unset($_SESSION['ultimo_tick']);
  unset($_SESSION['nombres_configurados']);
  unset($_SESSION['pausa']);
  unset($_SESSION['partida_terminada_por_tiempo']); // También el indicador de tiempo agotado
  unset($_SESSION['avatar_blancas']);
  unset($_SESSION['avatar_negras']);
  // Recargamos la página para que vuelva a la pantalla de inicio
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

/**
 * Crea una nueva partida pero mantiene a los mismos jugadores, avatares y configuración
 */
function revanchaPartida()
{
  // Si no hay partida anterior, volvemos al inicio
  if (!isset($_SESSION['partida'])) {
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
  }

  // Obtenemos la partida anterior
  $partidaActual = unserialize($_SESSION['partida']);
  $jugadores = $partidaActual->getJugadores();

  // Guardamos los nombres de los jugadores
  $nombreBlancas = $jugadores['blancas']->getNombre();
  $nombreNegras = $jugadores['negras']->getNombre();

  // Creamos una partida nueva con los mismos nombres
  $_SESSION['partida'] = serialize(new Partida($nombreBlancas, $nombreNegras));
  // Limpiamos la casilla seleccionada
  $_SESSION['casilla_seleccionada'] = null;

  // Reseteamos los tiempos al valor inicial
  if (isset($_SESSION['config']['tiempo_inicial'])) {
    $_SESSION['tiempo_blancas'] = $_SESSION['config']['tiempo_inicial'];
    $_SESSION['tiempo_negras'] = $_SESSION['config']['tiempo_inicial'];
  }

  // Iniciamos con las blancas
  $_SESSION['reloj_activo'] = 'blancas';
  // Reseteamos el reloj
  $_SESSION['ultimo_tick'] = time();
  // La partida empieza sin pausar
  $_SESSION['pausa'] = false;
  // Limpiamos el indicador de tiempo agotado
  unset($_SESSION['partida_terminada_por_tiempo']);

  // Los avatares y configuración ya están guardados, los mantenemos

  // Recargamos la página
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
            // Guardar partida antes de redirigir
            $_SESSION['casilla_seleccionada'] = null;
            $_SESSION['partida'] = serialize($partida);
            // Redirigir para mostrar el modal
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
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
  // Reanudar la partida después de la promoción
  $_SESSION['pausa'] = false;
  $_SESSION['ultimo_tick'] = time();
}

/**
 * Procesa la confirmación de enroque
 */
function procesarConfirmarEnroque()
{
  if (!isset($_SESSION['enroque_pendiente']) || !isset($_SESSION['partida'])) {
    return;
  }

  $origen = isset($_POST['origen_enroque']) ? $_POST['origen_enroque'] : null;
  $destino = isset($_POST['destino_enroque']) ? $_POST['destino_enroque'] : null;
  $tipo = isset($_POST['tipo_enroque']) ? $_POST['tipo_enroque'] : null;

  if (!$origen || !$destino || !$tipo || !in_array($tipo, ['corto', 'largo'])) {
    return;
  }

  $partida = unserialize($_SESSION['partida']);

  // Ejecutar el enroque
  if ($partida->ejecutarEnroque($origen, $destino, $tipo)) {
    $_SESSION['partida'] = serialize($partida);
  }

  // Limpiar enroque pendiente y reanudar la partida
  unset($_SESSION['enroque_pendiente']);
  $_SESSION['pausa'] = false;
  $_SESSION['ultimo_tick'] = time();
}

/**
 * Procesa la cancelación de enroque
 */
function procesarCancelarEnroque()
{
  if (isset($_SESSION['enroque_pendiente'])) {
    // Simplemente limpiamos la sesión y restauramos mensaje
    unset($_SESSION['enroque_pendiente']);
    $_SESSION['pausa'] = false;
    $_SESSION['ultimo_tick'] = time();

    if (isset($_SESSION['partida'])) {
      $partida = unserialize($_SESSION['partida']);
      $partida->setMensaje("Turno de {$partida->getJugadores()[$partida->getTurno()]->getNombre()}");
      $_SESSION['partida'] = serialize($partida);
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
