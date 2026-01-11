<?php

/*
Para actualizar los relojes de la partida en tiempo real
Recibe peticiones AJAX desde JavaScript para mantener los tiempos sincronizados
*/
function procesarAjaxActualizarRelojes()
{
  // Le decimos al navegador que vamos a devolver JSON (no HTML)
  header('Content-Type: application/json');

  // Si no hay variables de sesión de tiempo, significa que no hay partida iniciada. En ese caso, devuelve valores vacíos y termina
  if (!isset($_SESSION['tiempo_blancas']) || !isset($_SESSION['tiempo_negras']) || !isset($_SESSION['reloj_activo'])) {

    // Convertimos un array a texto JSON y lo imprime.
    echo json_encode([
      'tiempo_blancas' => 0,
      'tiempo_negras' => 0,
      'reloj_activo' => 'blancas',
      'pausa' => false,
      'sin_partida' => true
    ]);

    /* Guarda todos los cambios de sesión en el servidor 
    Importante ya que en PHP, las sesiones bloquean el acceso a otros scripts y el navegador estaría haciendo múltiples peticiones 
    AJAX rápidas y se quedaría esperando
    */
    session_write_close();

    // Termina la ejecución del script evitando se envíe JSOn duplicado y cierra la conexión con el navegador
    exit;
  }

  // Obtenemos la hora actual (segundos desde 1970)
  $ahora = time();

  // Solo restamos tiempo si la partida no está pausada y no se acabó el tiempo
  if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) {

    // Si el tiempo no se agotó, seguimos contando
    if (!isset($_SESSION['partida_terminada_por_tiempo'])) {

      // Si ya tuvimos un registro anterior, calculamos el tiempo que ha pasado desde entonces
      if (isset($_SESSION['ultimo_tick'])) {

        // Restamos la hora actual a la última vez que actualizamos
        $tiempoTranscurrido = $ahora - $_SESSION['ultimo_tick'];

        // Si pasó algún segundo, lo restamos del reloj del jugador actual
        if ($tiempoTranscurrido > 0) {

          // Si es turno de blancas, restamos de su tiempo
          if ($_SESSION['reloj_activo'] === 'blancas') {

            $_SESSION['tiempo_blancas'] = max(0, $_SESSION['tiempo_blancas'] - $tiempoTranscurrido);

            // Si no, restamos de las negras
          } else {

            $_SESSION['tiempo_negras'] = max(0, $_SESSION['tiempo_negras'] - $tiempoTranscurrido);
          }

          // Actualizamos la última vez que contamos tiempo
          $_SESSION['ultimo_tick'] = $ahora;
        }

        // Si no había registro anterior...
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

      // Si las negras se acabaron el tiempo, ganan las blancas
    } elseif ($_SESSION['tiempo_negras'] <= 0) {

      // Si las negras se acabaron el tiempo, ganan las blancas
      $_SESSION['partida_terminada_por_tiempo'] = 'blancas';

      // Aseguramos que el tiempo sea exactamente 0
      $_SESSION['tiempo_negras'] = 0;
    }
  }

  /* Verificamos si la partida está terminada
     Si existe una partida convertimos el objeto guardado en sesion a un objeto de PHP
     So no existe, la variable $partidaObj le asignamos  null
  */
  $partidaObj = isset($_SESSION['partida']) ? unserialize($_SESSION['partida']) : null;

  // Asignamos a variable booleana ($partidaTerminada si la partida está terminada o no
  $partidaTerminada = ($partidaObj && $partidaObj->estaTerminada()) ? true : false;

  // Imprimimos los tiempos actuales en formato JSON
  echo json_encode([
    'tiempo_blancas' => $_SESSION['tiempo_blancas'],
    'tiempo_negras' => $_SESSION['tiempo_negras'],
    'reloj_activo' => $_SESSION['reloj_activo'],
    'pausa' => isset($_SESSION['pausa']) ? $_SESSION['pausa'] : false,
    'sin_partida' => false,
    'partida_terminada' => $partidaTerminada
  ]);
  // Guarda los cambios de sesión y cierra la conexión
  session_write_close();

  // Terminamos la ejecución del script
  exit;
}


/**
 * Para aplicar configuración por defecto si no existe en session
 */
function aplicarConfigPredeterminada()
{
  // Configuración por defecto
  $configDefecto = [
    "tiempo_inicial" => 600, // 10 minutos
    "incremento" => 0, // Sin incremento
    "mostrar_coordenadas" => true, // Mostramos coordenadas del tablero
    "mostrar_capturas" => true // Mostramos piezas capturadas
  ];

  // Si la configuración no existe en la session, la creamos con los valores por defecto ($configDefecto )
  if (!isset($_SESSION["config"])) $_SESSION["config"] = $configDefecto;

  // Si hay una partida en curso pero no existe el indicador de pausa
  if (isset($_SESSION["partida"]) && !isset($_SESSION["pausa"])) {

    $_SESSION["pausa"] = false; // lo inicializamos en false
  }
}

/*
 Condiciones de todas las acciones de la solicitud y prepara el estado para la vista
 Retorna un array asociativo con los datos necesarios para renderizar
*/
function resolverAcciones()
{

  // Estado inicial por defecto
  $estado = [
    'mostrarModalReiniciar' => false,
    'mostrarModalRevancha' => false,
    'mostrarModalGuardar' => false,
    'nombrePartidaSugerido' => '',
    'mostrarModalPromocion' => false,
    'mostrarModalEnroque' => false,
    'mostrarModalCargar' => false,
    'partidasGuardadas' => [],
    'partida' => null,
    'jugadores' => null,
    'marcador' => null,
    'mensaje' => null,
    'turno' => null,
    'casillaSeleccionada' => null,
    'piezasCapturadas' => ['blancas' => [], 'negras' => []],
    'partidasGuardadasInicio' => []
  ];

  // Pausar desde configuración (respuesta JSON)
  if (isset($_POST['pausar_desde_configuracion'])) {

    // Si la partida no estaba pausada, la pausamos
    if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) $_SESSION['pausa'] = true;


    // Si ya estaba pausada, no hacemos nada, reseteamos el contador de tiempo
    if (isset($_SESSION['ultimo_tick']))  $_SESSION['ultimo_tick'] = time();

    // Enviamos la respuesta
    header('Content-Type: application/json');

    // Devolvemos el estado de pausa
    echo json_encode(['ok' => true, 'pausa' => $_SESSION['pausa']]);

    // Terminamos la ejecución del script
    exit;
  }

  // Reanudar desde configuración (respuesta JSON)
  if (isset($_POST['reanudar_desde_configuracion']) && !isset($_POST['guardar_configuracion'])) {

    $_SESSION['pausa'] = false; // Quitamos la partida de pausa

    $_SESSION['ultimo_tick'] = time(); // Reseteamos el contador de tiempo

    header('Content-Type: application/json'); // Indicamos que devolvemos JSON

    // Devolvemos el estado de pausa
    echo json_encode(['ok' => true, 'pausa' => $_SESSION['pausa']]);

    // Terminamos la ejecución del script
    exit;
  }

  // Guardamos los cambios de configuración
  if (isset($_POST['guardar_configuracion'])) {

    procesarGuardarConfiguracion(); // Guardamos los cambios de configuración

    // Si se pidió reanudar...
    if (isset($_POST['reanudar_desde_configuracion'])) {

      $_SESSION['pausa'] = false; // Quitamos la partida de pausa

      $_SESSION['ultimo_tick'] = time(); // Reseteamos el contador de tiempo
    }
  }

  // Si se pidió iniciar una nueva partida, creamos una nueva partida
  if (isset($_POST['iniciar_partida'])) iniciarPartida();

  // Cargar/eliminar desde pantalla inicial
  // Si se pidió cargar una partida desde la pantalla inicial
  if (isset($_POST['cargar_partida_inicial']) && isset($_POST['archivo_partida'])) {

    $partidaCargada = cargarPartida($_POST['archivo_partida']); // Cargamos la partida

    // Si se cargó correctamente
    if ($partidaCargada) {

      $_SESSION['partida'] = serialize($partidaCargada); // Guardamos la partida en sesión

      $_SESSION['nombres_configurados'] = true; // Marcamos que ya se configuraron los nombres
    }
  }

  // Si se pidió eliminar una partida desde la pantalla inicial, la eliminamos
  if (isset($_POST['eliminar_partida_inicial']) && isset($_POST['archivo_partida'])) eliminarPartida($_POST['archivo_partida']);


  // Pausa/reanudar manual
  // si se pidió pausar, alternamos la pausa
  if (isset($_POST['alternar_pausa'])) procesarTogglePausa();

  // Modales de reinicio y revancha
  // Si se pidió abrir el modal de reinicio
  if (isset($_POST['abrir_modal_reiniciar'])) {

    // Si la partida no estaba pausada, la pausamos
    if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) $_SESSION['pausa'] = true;

    $estado['mostrarModalReiniciar'] = true; // Mostramos el modal de reinicio
  }

  // Si se pidió abrir el modal de revancha
  if (isset($_POST['abrir_modal_revancha'])) {

    // Si la partida no estaba pausada, la pausamos
    if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) $_SESSION['pausa'] = true; // Pausamos la partida si no lo estaba

    // Mostramos el modal de revancha
    $estado['mostrarModalRevancha'] = true; // Mostramos el modal de revancha
  }

  // Cancelar cualquier modal
  // Si se pidió cancelar cualquier modal (guardar, cargar, reiniciar, revancha, promoción, enroque)
  if (isset($_POST['cancelar_modal'])) {

    // Si la partida estaba pausada, la reanudamos
    if (isset($_SESSION['pausa']) && $_SESSION['pausa']) $_SESSION['pausa'] = false;
  }

  // Si se confirmó el reinicio de la partida, la reiniciamos
  if (isset($_POST['confirmar_reiniciar'])) reiniciarPartida();

  // Si se confirmó la revancha, iniciamos una nueva partida con los mismos jugadores
  if (isset($_POST['confirmar_revancha'])) revanchaPartida();


  // Modal Guardar
  // Si se pidió abrir el modal de guardar partida
  if (isset($_POST['abrir_modal_guardar']) && isset($_SESSION['partida'])) {

    $partidaTmp = unserialize($_SESSION['partida']); // Obtenemos la partida desde sesión

    $jugadoresTmp = $partidaTmp->getJugadores(); // Obtenemos los jugadores

    // Preparamos un nombre sugerido para la partida
    $estado['nombrePartidaSugerido'] = $jugadoresTmp['blancas']->getNombre() . ' vs ' . $jugadoresTmp['negras']->getNombre() . ' - ' . date('d/m/Y H:i');

    $estado['mostrarModalGuardar'] = true; // Mostramos el modal de guardar
  }

  // Si se confirmó guardar la partida
  if (isset($_POST['confirmar_guardar']) && isset($_POST['nombre_partida']) && isset($_SESSION['partida'])) {

    $partidaTmp = unserialize($_SESSION['partida']); // Obtenemos la partida desde sesión

    guardarPartida($partidaTmp, $_POST['nombre_partida']); // Guardamos la partida

    $estado['mostrarModalGuardar'] = false; // Ocultamos el modal de guardar
  }

  // Promoción de peón
  // si se pidió abrir el modal de promoción, mostramos el modal
  if (isset($_SESSION['promocion_en_curso'])) $estado['mostrarModalPromocion'] = true;

  // Si se confirmó la promoción del peón
  if (isset($_POST['confirmar_promocion']) && isset($_POST['tipo_promocion'])) {

    procesarConfirmarPromocion(); // Procesamos la promoción

    $estado['mostrarModalPromocion'] = false; // Ocultamos el modal de promoción
  }

  // Enroque
  // Si hay un enroque pendiente, mostramos el modal
  if (isset($_SESSION['enroque_pendiente'])) {

    // Si la partida no estaba pausada, la pausamos
    if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) $_SESSION['pausa'] = true;

    $estado['mostrarModalEnroque'] = true; // Mostramos el modal de enroque
  }

  // Si se confirmó el enroque
  if (isset($_POST['confirmar_enroque'])) {

    procesarConfirmarEnroque(); // Procesamos el enroque

    $estado['mostrarModalEnroque'] = false; // Ocultamos el modal de enroque
  }

  // Si se canceló el enroque
  if (isset($_POST['cancelar_enroque'])) {

    procesarCancelarEnroque(); // Procesamos la cancelación del enroque

    $estado['mostrarModalEnroque'] = false; // Ocultamos el modal de enroque
  }

  // Modal Cargar
  // Si se pidió abrir el modal de cargar partida
  if (isset($_POST['abrir_modal_cargar'])) {

    // Si la partida no estaba pausada, la pausamos
    if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) $_SESSION['pausa'] = true;

    $estado['partidasGuardadas'] = listarPartidas(); // Listamos las partidas guardadas

    $estado['mostrarModalCargar'] = true; // Mostramos el modal de cargar
  }

  // Si se pidió cargar una partida
  if (isset($_POST['cargar_partida']) && isset($_POST['archivo_partida'])) {

    // Cargamos la partida
    $partidaCargada = cargarPartida($_POST['archivo_partida']);

    // Si se cargó correctamente
    if ($partidaCargada) {

      $estado['partida'] = $partidaCargada; // Asignamos la partida al estado

      $_SESSION['partida'] = serialize($estado['partida']); // Guardamos la partida en sesión
    }
  }

  // Si se pidió eliminar una partida
  if (isset($_POST['eliminar_partida']) && isset($_POST['archivo_partida'])) {

    eliminarPartida($_POST['archivo_partida']); // Eliminamos la partida

    $estado['partidasGuardadas'] = listarPartidas(); // Actualizamos la lista de partidas guardadas

    $estado['mostrarModalCargar'] = true; // Mostramos el modal de cargar
  }

  // Procesamiento del juego si hay partida
  // Si hay una partida en sesión, la procesamos
  if (isset($_SESSION['partida'])) {

    // Convertimos la partida guardada en sesión a un objeto de PHP
    $estado['partida'] = unserialize($_SESSION['partida']);

    procesarJugada($estado['partida']); // Procesamos la jugada si se pidió

    $_SESSION['partida'] = serialize($estado['partida']); // Guardamos la partida actualizada en sesión

    // Si se pidió deshacer la última jugada, la deshacemos
    if (isset($_POST['deshacer'])) deshacerJugada($estado['partida']);

    // Si se pidió guardar la partida, la guardamos
    if (isset($_POST['guardar'])) guardarPartida($estado['partida']);

    // Si se pidió cargar la partida
    if (isset($_POST['cargar'])) {

      $partidaCargada = cargarPartida(); // Cargamos la partida

      // Si se cargó correctamente
      if ($partidaCargada) $estado['partida'] = $partidaCargada;
    }

    // Si la partida terminó por agotamiento de tiempo, actualizamos el mensaje y terminamos la partida
    if (isset($_SESSION['partida_terminada_por_tiempo'])) {

      $ganador = $_SESSION['partida_terminada_por_tiempo']; // Obtenemos el ganador

      $jugadoresLocal = $estado['partida']->getJugadores(); // Obtenemos los jugadores

      // Si ganaron las blancas, actualizamos el mensaje de victoria (htmlspecialchars para evitar inyección XSS,)
      if ($ganador === 'blancas') $estado['partida']->setMensaje('⏰ ¡Tiempo agotado para las negras! ' . $jugadoresLocal['blancas']->getNombre() . ' ha ganado.');

      // Si ganaron las negras, actualizamos el mensaje de victoria
      else $estado['partida']->setMensaje('⏰ ¡Tiempo agotado para las blancas! ' . $jugadoresLocal['negras']->getNombre() . ' ha ganado.');

      $estado['partida']->terminar(); // Terminamos la partida

      $_SESSION['partida'] = serialize($estado['partida']); // Guardamos la partida actualizada en sesión

      unset($_SESSION['partida_terminada_por_tiempo']); // Quitamos el indicador de tiempo agotado
    }

    $estado['casillaSeleccionada'] = $_SESSION['casilla_seleccionada']; // Obtenemos la casilla seleccionada

    $estado['marcador'] = $estado['partida']->marcador(); // Obtenemos el marcador

    $estado['mensaje'] = $estado['partida']->getMensaje(); // Obtenemos el mensaje de la partida

    $estado['turno'] = $estado['partida']->getTurno(); // Obtenemos el turno actual

    $estado['jugadores'] = $estado['partida']->getJugadores(); // Obtenemos los jugadores

    // Obtenemos las piezas capturadas de ambos jugadores

    // Recorremos las piezas de las blancas
    foreach ($estado['jugadores']['blancas']->getPiezas() as $pieza) {

      // Si la pieza estuvo capturada, la agregamos a la lista
      if ($pieza->estCapturada()) $estado['piezasCapturadas']['blancas'][] = $pieza;
    }

    // Recorremos las piezas de las negras
    foreach ($estado['jugadores']['negras']->getPiezas() as $pieza) {

      // Si la pieza estuvo capturada, la agregamos a la lista
      if ($pieza->estCapturada()) $estado['piezasCapturadas']['negras'][] = $pieza;
    }
  }

  // Partidas guardadas para la pantalla inicial
  $estado['partidasGuardadasInicio'] = listarPartidas();

  // Retornamos el estado preparado para la vista
  return $estado;
}


 // Para guardar los ajustes que el usuario cambió en el modal de configuración
function procesarGuardarConfiguracion()
{
  // Guardamos si mostrar o no las coordenadas del tablero
  $_SESSION['config']['mostrar_coordenadas'] = isset($_POST['mostrar_coordenadas']);

  // Guardamos si mostrar o no las piezas capturadas
  $_SESSION['config']['mostrar_capturas'] = isset($_POST['mostrar_capturas']);
}


//  Crea una nueva partida con los jugadores y configuración que el usuario ha elegido
function iniciarPartida()
{
  // Obtenemos los nombres de los jugadores, eliminando espacios al principio y final
  // Si no se proporcionan nombres, usamos "Jugador 1" y "Jugador 2" por defecto
  // htmlspecialchars para evitar inyección XSS
  // trim para eliminar espacios en blanco al inicio y final
  $nombreBlancas = !empty($_POST['nombre_blancas']) ? htmlspecialchars(trim($_POST['nombre_blancas'])) : "Jugador 1";
  $nombreNegras = !empty($_POST['nombre_negras']) ? htmlspecialchars(trim($_POST['nombre_negras'])) : "Jugador 2";

  // Gestionamos los avatares que se eligieron

  // Inicializamos las variables de avatar en null
  $avatarBlancas = null;
  $avatarNegras = null;

  // Gestionamos el avatar de las blancas
  // Si se eligió un avatar personalizado o uno predefinido distinto al por defecto
  if (!empty($_POST["avatar_blancas"])) {

    // Si se eligió un avatar personalizado
    if ($_POST["avatar_blancas"] === "personalizado") {
      $avatarBlancas = manejarSubidaAvatar("avatar_personalizado_blancas", "blancas");
    } elseif ($_POST["avatar_blancas"] !== "predeterminado") {
      $avatarBlancas = $_POST["avatar_blancas"];
    }
  }

  if (!empty($_POST["avatar_negras"])) {
    if ($_POST["avatar_negras"] === "personalizado") {
      $avatarNegras = manejarSubidaAvatar("avatar_personalizado_negras", "negras");
    } elseif ($_POST["avatar_negras"] !== "predeterminado") {
      $avatarNegras = $_POST["avatar_negras"];
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
/**
 * Maneja la subida de un archivo de avatar personalizado
 * @param string $inputName Nombre del campo de archivo
 * @param string $color Color del jugador ('blancas' o 'negras')
 * @return string|null Ruta relativa del archivo subido o null si no se subió
 */
function manejarSubidaAvatar($inputName, $color)
{
  if (!isset($_FILES[$inputName]) || $_FILES[$inputName]["error"] !== UPLOAD_ERR_OK) {
    return null;
  }

  $file = $_FILES[$inputName];
  $allowedTypes = ["image/jpeg", "image/png", "image/gif"];
  $maxSize = 5 * 1024 * 1024; // 5MB

  // Validar tipo de archivo
  // Revisar MIME real con finfo para evitar suplantación
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $realType = $finfo->file($file["tmp_name"]);
  if (!in_array($realType, $allowedTypes)) {
    return null;
  }

  // Validar tamaño
  if ($file["size"] > $maxSize) {
    return null;
  }

  // Crear directorio si no existe
  $uploadDir = __DIR__ . "/../public/imagenes/avatares/";
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }

  // Eliminar avatares personalizados previos del mismo color
  $pattern = $uploadDir . "avatar_" . $color . "_*";
  foreach (glob($pattern) as $oldFile) {
    if (is_file($oldFile)) {
      unlink($oldFile);
    }
  }

  // Generar nombre único
  $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
  $fileName = "avatar_" . $color . "_" . time() . "_" . uniqid() . "." . $extension;
  $filePath = $uploadDir . $fileName;

  // Mover archivo
  if (move_uploaded_file($file["tmp_name"], $filePath)) {
    return "public/imagenes/avatares/" . $fileName;
  }

  return null;
}