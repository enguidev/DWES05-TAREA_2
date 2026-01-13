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


// Para aplicar configuración por defecto si no existe en session
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

  // Si se pidió iniciar nueva partida desde pantalla principal, marcamos que se mostró
  if (isset($_POST['iniciar_nueva_partida'])) {
    $_SESSION['pantalla_principal_mostrada'] = true;
  }

  // Si se pidió cargar desde pantalla principal, marcamos que se mostró
  if (isset($_POST['abrir_modal_cargar_inicial'])) {
    $_SESSION['pantalla_principal_mostrada'] = true;
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

  return $estado; // Retornamos el estado preparado para la vista
}


// Para guardar los ajustes que el usuario cambió en el modal de configuración
function procesarGuardarConfiguracion()
{
  // Guardamos si mostrar o no las coordenadas del tablero
  $_SESSION['config']['mostrar_coordenadas'] = isset($_POST['mostrar_coordenadas']);

  // Guardamos si mostrar o no las piezas capturadas
  $_SESSION['config']['mostrar_capturas'] = isset($_POST['mostrar_capturas']);
}


// Crea una nueva partida con los jugadores y configuración que el usuario ha elegido
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

    // Si se eligió un avatar personalizado, gestionamos la subida del archivo
    if ($_POST["avatar_blancas"] === "personalizado") $avatarBlancas = manejarSubidaAvatar("avatar_personalizado_blancas", "blancas");

    // Si se eligió un avatar predefinido distinto al por defecto, lo asignamos
    elseif ($_POST["avatar_blancas"] !== "predeterminado") $avatarBlancas = $_POST["avatar_blancas"];
  }

  // Gestionamos el avatar de las negras

  // Si se eligió un avatar personalizado o uno predefinido distinto al por defecto
  if (!empty($_POST["avatar_negras"])) {

    // Si se eligió un avatar personalizado, gestionamos la subida del archivo
    if ($_POST["avatar_negras"] === "personalizado") $avatarNegras = manejarSubidaAvatar("avatar_personalizado_negras", "negras");

    // Si se eligió un avatar predefinido distinto al por defecto, lo asignamos
    elseif ($_POST["avatar_negras"] !== "predeterminado") $avatarNegras = $_POST["avatar_negras"];
  }

  // Guardamos la configuración elegida
  $_SESSION['config'] = [

    'tiempo_inicial' => (int)$_POST['tiempo_inicial'], // Tiempo inicial para cada jugador

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

  $_SESSION['reloj_activo'] = 'blancas'; // Las blancas empiezan jugando

  $_SESSION['ultimo_tick'] = time(); // Registramos la hora actual para contar el tiempo

  $_SESSION['nombres_configurados'] = true; // Marcamos que ya se configuró la partida

  $_SESSION['pausa'] = false; // La partida no está pausada al inicio

  // Guardamos los avatares elegidos de ambos jugadores

  // Avatar de las blancas
  $_SESSION['avatar_blancas'] = $avatarBlancas;

  // Avatar de las negras
  $_SESSION['avatar_negras'] = $avatarNegras;
}


// Para pausa o reanuda la partida según su estado actual
function procesarTogglePausa()
{
  // Si existe el indicador de pausa
  if (isset($_SESSION['pausa'])) {

    // Invertimos el estado de pausa
    $_SESSION['pausa'] = !$_SESSION['pausa'];

    // Si reanudamos la partida, reseteamos el contador de tiempo
    if (!$_SESSION['pausa']) $_SESSION['ultimo_tick'] = time();
  }
}


// Para borrar toda la partida y volver a la pantalla de inicio
function reiniciarPartida()
{
  // Borramos toda la información de la partida

  unset($_SESSION['partida']); // Borramos la partida

  unset($_SESSION['casilla_seleccionada']); // Borramos la casilla seleccionada

  unset($_SESSION['tiempo_blancas']); // Borramos el tiempo de las blancas

  unset($_SESSION['tiempo_negras']); // Borramos el tiempo de las negras

  unset($_SESSION['reloj_activo']); // Borramos el reloj activo

  unset($_SESSION['ultimo_tick']); // Borramos el último tick

  unset($_SESSION['nombres_configurados']); // Borramos el indicador de nombres configurados

  unset($_SESSION['pantalla_principal_mostrada']); // Borramos el indicador de pantalla principal

  unset($_SESSION['pausa']); // Borramos el estado de pausa

  unset($_SESSION['partida_terminada_por_tiempo']); // Borramos el indicador de tiempo agotado

  unset($_SESSION['avatar_blancas']); // Borramos el avatar de las blancas

  unset($_SESSION['avatar_negras']); // Borramos el avatar de las negras

  // Recargamos la página para que vuelva a la pantalla de inicio
  header("Location: " . $_SERVER['PHP_SELF']);

  exit; // Terminamos la ejecución del script
}

// Para crear una nueva partida manteniendo a los mismos jugadores, avatares y configuración
function revanchaPartida()
{
  // Si no hay partida anterior
  if (!isset($_SESSION['partida'])) {

    header("Location: " . $_SERVER['PHP_SELF']); // Recargamos la página para que vuelva a la pantalla de inicio

    exit; // Terminamos la ejecución del script
  }

  // Recuperamos la partida anterior
  $partidaActual = unserialize($_SESSION['partida']);

  // Obtenemos los jugadores de la partida actual
  $jugadores = $partidaActual->getJugadores();

  // Guardamos los nombres de ambos jugadores
  $nombreBlancas = $jugadores['blancas']->getNombre();

  $nombreNegras = $jugadores['negras']->getNombre();

  // Creamos una partida nueva con los mismos nombres
  $_SESSION['partida'] = serialize(new Partida($nombreBlancas, $nombreNegras));

  // Limpiamos la casilla seleccionada
  $_SESSION['casilla_seleccionada'] = null;

  // Si hay configuración de tiempo inicial
  if (isset($_SESSION['config']['tiempo_inicial'])) {

    // Reseteamos los tiempos de ambos jugadores
    $_SESSION['tiempo_blancas'] = $_SESSION['config']['tiempo_inicial'];

    $_SESSION['tiempo_negras'] = $_SESSION['config']['tiempo_inicial'];
  }

  $_SESSION['reloj_activo'] = 'blancas'; // Iniciamos el tiempo con las blancas

  $_SESSION['ultimo_tick'] = time(); // Obtenemos la hora actual para contar el tiempo

  $_SESSION['pausa'] = false; // La partida no está en pausa al inicio

  unset($_SESSION['partida_terminada_por_tiempo']); // Limpiamos el indicador de tiempo agotado

  header("Location: " . $_SERVER['PHP_SELF']); // Recargamos la página

  exit; // Terminamos la ejecución del script
}


// Para procesar una jugada realizada por el usuario
function procesarJugada($partida)
{
  // Procesar jugada (solo si no está en pausa)
  if (isset($_POST['seleccionar_casilla']) && (!isset($_SESSION['pausa']) || !$_SESSION['pausa'])) {

    $casilla = $_POST['seleccionar_casilla']; // Casilla que se ha clickeado

    // Si no hay casilla seleccionada, intentamos seleccionar la pieza en la casilla clickeada
    if ($_SESSION['casilla_seleccionada'] === null) {

      $piezaClickeada = obtenerPiezaEnCasilla($casilla, $partida); // Pieza en la casilla clickeada

      // Si hay una pieza y es del color del turno actual, la seleccionamos
      if ($piezaClickeada && $piezaClickeada->getColor() === $partida->getTurno()) $_SESSION['casilla_seleccionada'] = $casilla;
      else $_SESSION['casilla_seleccionada'] = null;
    } else {

      // Ya hay una casilla seleccionada, procesamos el movimiento o re-seleccionamos
      $origen = $_SESSION['casilla_seleccionada']; // Obtenemos la casilla de origen

      // Si se hace clic en otra pieza del mismo color, cambiamos la selección
      $piezaDestino = obtenerPiezaEnCasilla($casilla, $partida);
      if ($piezaDestino && $piezaDestino->getColor() === $partida->getTurno()) {

        $_SESSION['casilla_seleccionada'] = $casilla;
      } else {

        $destino = $casilla; // Casilla de destino (la que se clickeó)

        $exito = $partida->jugada($origen, $destino); // Intentamos realizar la jugada

        // Si la jugada fue exitosa
        if ($exito) {

          $ahora = time(); // Actualizar el tiempo antes de cambiar de turno

          $tiempoTranscurrido = $ahora - $_SESSION['ultimo_tick'];  // Tiempo que tomó la jugada

          $turnoAnterior = $_SESSION['reloj_activo']; // Guardamos el turno anterior antes de cambiarlo

          // Si fue el turno de las blancas, restamos su tiempo
          if ($turnoAnterior === 'blancas') $_SESSION['tiempo_blancas'] = max(0, $_SESSION['tiempo_blancas'] - $tiempoTranscurrido);

          // Si fue el turno de las negras, restamos su tiempo
          else $_SESSION['tiempo_negras'] = max(0, $_SESSION['tiempo_negras'] - $tiempoTranscurrido);

          // Incremento Fischer (después de restar el tiempo transcurrido)
          // Si han configurado un tiempo de incremento
          if ($_SESSION['config']['incremento'] > 0) {

            // Si fue el turno de las blancas, le sumamos el incremento
            if ($turnoAnterior === 'blancas') $_SESSION['tiempo_blancas'] += $_SESSION['config']['incremento'];

            // Si fue el turno de las negras, le sumamos el incremento
            else $_SESSION['tiempo_negras'] += $_SESSION['config']['incremento'];
          }

          // cambiamos el reloj activo al otro jugador
          $_SESSION['reloj_activo'] = ($turnoAnterior === 'blancas') ? 'negras' : 'blancas';

          $_SESSION['ultimo_tick'] = time(); // Actualizamos el ultimo tick

          // Promoción elegible de peón

          $piezaEnDestino = obtenerPiezaEnCasilla($destino, $partida); // Obtenemos la pieza en la casilla de destino

          // Si la pieza en la casilla de destino es un peón
          if ($piezaEnDestino instanceof Peon && $piezaEnDestino->puedePromoverse()) {

            // Guardamos la información de la promoción en curso
            $_SESSION['promocion_en_curso'] = [

              'color' => $turnoAnterior, // Color del peón que promueve

              'posicion' => $destino // Posición del peón que promueve
            ];

            $_SESSION['pausa'] = true; // Pausamos la partida para elegir pieza

            $_SESSION['casilla_seleccionada'] = null; // Limpiamos la casilla seleccionada

            $_SESSION['partida'] = serialize($partida); // Guardamos la partida en session

            header("Location: " . $_SERVER['PHP_SELF']); // Recargamos la página para que vuelva a la pantalla de inicio

            exit; // Terminamos la ejecución del script
          }
        }

        $_SESSION['casilla_seleccionada'] = null; // Limpiamos la casilla seleccionada

        $_SESSION['partida'] = serialize($partida); // Guardamos la partida en session
      }
    }
  }
}


// Para deshacer la última jugada realizada
function deshacerJugada($partida)
{
  $partida->deshacerJugada(); // Deshacemos la última jugada

  $_SESSION['casilla_seleccionada'] = null; // Limpiamos la casilla seleccionada

  $_SESSION['partida'] = serialize($partida); // Guardamos la partida en session
}


// Para guardar la partida
function guardarPartida($partida, $nombrePartida = null)
{
  // si no se proporcionó un nombre de partida
  if (!$nombrePartida) {

    $jugadores = $partida->getJugadores(); // Obtenemos los jugadores

    // Preparamos un nombre por defecto
    $nombrePartida = $jugadores['blancas']->getNombre() . ' vs ' . $jugadores['negras']->getNombre();
  }

  $timestamp = time(); // Marca de tiempo actual

  // Nombre del archivo seguro
  // Lo que no sea letras minusculas, mayusculas, números, guiones o guiones bajos lo reemplazamos por guion bajo
  $nombreArchivo = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombrePartida) . '_' . $timestamp;

  // Copiar avatares personalizados si existen

  $avatarBlancasGuardado = null; // Nombre del avatar guardado para las blancas
  $avatarNegrasGuardado = null; // Nombre del avatar guardado para las negras

  // Si hay un avatar personalizado para las blancas
  if (isset($_SESSION['avatar_blancas']) && strpos($_SESSION['avatar_blancas'], 'avatar_blancas_') !== false) {

    $rutaOrigen = __DIR__ . '/../public/' . $_SESSION['avatar_blancas']; // Ruta del avatar personalizado

    // Si el archivo existe, lo copiamos a la carpeta de avatares guardados
    if (file_exists($rutaOrigen)) {

      // Gestionamos el nombre completo, la extensión y la ruta de destino

      $extension = pathinfo($rutaOrigen, PATHINFO_EXTENSION); // Obtenemos la extensión del archivo

      $nombreAvatar = 'avatar_blancas_' . $timestamp . '.' . $extension; // Nuevo nombre del avatar

      // Ruta de destino
      $rutaDestino = __DIR__ . '/../data/partidas/avatares/' . $nombreAvatar; // Ruta de destino

      copy($rutaOrigen, $rutaDestino); // Copiamos el archivo

      $avatarBlancasGuardado = $nombreAvatar; // Guardamos el nombre del avatar
    }
  }

  // Si hay un avatar personalizado para las negras
  if (isset($_SESSION['avatar_negras']) && strpos($_SESSION['avatar_negras'], 'avatar_negras_') !== false) {

    $rutaOrigen = __DIR__ . '/../public/' . $_SESSION['avatar_negras']; // Ruta del avatar personalizado

    // Si el archivo existe, lo copiamos a la carpeta de avatares guardados
    if (file_exists($rutaOrigen)) {

      $extension = pathinfo($rutaOrigen, PATHINFO_EXTENSION); // Obtenemos la extensión del archivo

      $nombreAvatar = 'avatar_negras_' . $timestamp . '.' . $extension; // Nuevo nombre del avatar

      $rutaDestino = __DIR__ . '/../data/partidas/avatares/' . $nombreAvatar; // Ruta de destino

      copy($rutaOrigen, $rutaDestino); // Copiamos el archivo

      $avatarNegrasGuardado = $nombreAvatar; // Guardamos el nombre del avatar
    }
  }

  // Datos a guardar en el archivo JSON
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

  // Guardamos el archivo JSON en la carpeta de partidas
  file_put_contents(__DIR__ . '/../data/partidas/' . $nombreArchivo . '.json', json_encode($data, JSON_PRETTY_PRINT));

  return $nombreArchivo; // Retornamos el nombre del archivo guardado
}


// Para obtener la lista de partidas guardadas ordenadas por fecha descendente
function listarPartidas()
{
  // Directorio donde se guardan las partidas
  $directorio = __DIR__ . '/../data/partidas';

  // Si el directorio no existe, retornamos un array vacio
  if (!is_dir($directorio)) return [];

  // Obtenemos todos los archivos JSON en el directorio
  $archivos = glob($directorio . '/*.json');

  // Si no hay archivos, retornamos un array vacio
  if (!$archivos) return [];

  // Creamos un array para almacenar las partidas
  $partidas = [];

  // Recorremos los archivos y extraemos la información relevante
  foreach ($archivos as $rutaArchivo) {

    // Si no es un archivo, continuamos con el siguiente archivo
    if (!is_file($rutaArchivo)) continue;

    // Leemos el contenido del archivo JSON
    $contenido = json_decode(file_get_contents($rutaArchivo), true);

    // Si el contenido no es un array, continuamos con el siguiente archivo
    if (!is_array($contenido)) continue;

    // Obtenemos el timestamp del archivo o de la información guardada
    // Si no existe en el contenido, usamos la fecha de modificación del archivo para obtener el timestamp
    $timestamp = isset($contenido['timestamp']) ? (int)$contenido['timestamp'] : filemtime($rutaArchivo);

    // Agregamos la información de la partida al array
    $partidas[] = [

      // Si no existe el nombre, usamos el nombre del archivo
      'nombre' => isset($contenido['nombre']) ? $contenido['nombre'] : basename($rutaArchivo, '.json'),

      // Si no existe la fecha, usamos la fecha de modificación del archivo
      'fecha' => isset($contenido['fecha']) ? $contenido['fecha'] : date('Y-m-d H:i:s', $timestamp),

      // Nombre del archivo sin la ruta, solo el nombre (basename($rutaArchivo))
      'archivo' => basename($rutaArchivo),

      // Timestamp para ordenar
      'timestamp' => $timestamp
    ];
  }

  // Ordenamos (usort()) el array de la más reciente a la más antigua 
  usort($partidas, function ($a, $b) {

    /* (<=>) Operador spaceship compara dos valores ($a y $b) y devuelve 
      -0 si son iguales
      -1 si el primero es menor
      1 si el primero es mayor
      compara $b con $a para orden descendente (al revés (orden ascendente) sería $a <=> $b)
    */
    return $b['timestamp'] <=> $a['timestamp']; // Orden descendente por timestamp
  });

  return $partidas; // Retornamos el array de partidas ordenadas por fecha
}


// Para cargar una partida guardada desde JSON y restaura la sesión
function cargarPartida($archivo = null)
{
  // Ruta del archivo a cargar

  // Si se proporciona un archivo, usamos la ruta /../data/partidas/ + el nombre del archivo
  // Si no se proporciona un archivo, cargamos el archivo de partida guardada por defecto
  // _DIR_ (constante de PHP) es la ruta del directorio actual (src)
  // basename() obtiene el nombre del archivo (partida_guardada.json) sin la ruta 
  $rutaArchivo = $archivo
    ? __DIR__ . '/../data/partidas/' . basename($archivo)
    : __DIR__ . '/../data/partida_guardada.json';

  if (!file_exists($rutaArchivo)) return false; // Si el archivo no existe, retornamos false

  // Leemos el contenido del archivo
  // json_decode() convierte el JSON a un array/objeto
  // file_get_contents() lee el contenido del archivo como una cadena (string)
  // $rutaArchivo es la ruta completa del archivo
  // true en json_decode() para obtener un array asociativo en lugar de un objeto
  $contenido = json_decode(file_get_contents($rutaArchivo), true);

  // Si el contenido no es un array o no tiene la clave 'partida', retornamos false
  if (!is_array($contenido) || !isset($contenido['partida'])) return false;

  // Deserializamos (objetos de la clase Partida) la partida
  $partida = unserialize($contenido['partida']);

  // Restauramos la sesión con los datos de la partida cargada
  $_SESSION['partida'] = $contenido['partida'];

  // Si hay historial de movimientos, lo restauramos
  $_SESSION['casilla_seleccionada'] = isset($contenido['casilla_seleccionada']) ? $contenido['casilla_seleccionada'] : null;

  // Si hay tiempos guardados para las blancas, los restauramos
  $_SESSION['tiempo_blancas'] = isset($contenido['tiempo_blancas']) ? (int)$contenido['tiempo_blancas'] : 0;

  // Si hay tiempos guardados para las negras, los restauramos
  $_SESSION['tiempo_negras'] = isset($contenido['tiempo_negras']) ? (int)$contenido['tiempo_negras'] : 0;

  // Si hay reloj activo guardado, lo restauramos
  $_SESSION['reloj_activo'] = isset($contenido['reloj_activo']) ? $contenido['reloj_activo'] : 'blancas';

  // Si hay configuración guardada, la restauramos
  $_SESSION['config'] = isset($contenido['config']) && is_array($contenido['config']) ? $contenido['config'] : [];

  // Si hay estado de pausa guardado, lo restauramos
  $_SESSION['pausa'] = isset($contenido['pausa']) ? (bool)$contenido['pausa'] : false;

  // Asignamos true a nombres_configurados para indicar que ya hay una partida cargada
  $_SESSION['nombres_configurados'] = true;

  // Actualizamos el último tick al momento de cargar la partida
  $_SESSION['ultimo_tick'] = time();

  // Restauramos avatares, priorizando los que se guardaron en disco
  $_SESSION['avatar_blancas'] = restaurarAvatarGuardado($contenido, 'blancas');

  // Restauramos avatares, priorizando los que se guardaron en disco
  $_SESSION['avatar_negras'] = restaurarAvatarGuardado($contenido, 'negras');

  return $partida; // Retornamos la partida cargada
}


// Para eliminar una partida guardada y sus avatares asociados
function eliminarPartida($archivo)
{
  // Ruta del archivo a eliminar 
  $rutaArchivo = __DIR__ . '/../data/partidas/' . basename($archivo);

  // Si el archivo no existe, retornamos false
  if (!file_exists($rutaArchivo)) return false;

  // Leemos el contenido del archivo
  $contenido = json_decode(file_get_contents($rutaArchivo), true);

  // Eliminamos los avatares asociados si existen
  foreach (['blancas', 'negras'] as $color) {

    // Campo del avatar guardado
    $campoAvatar = 'avatar_' . $color . '_guardado';

    // Si existe el campo y tiene un valor
    if (isset($contenido[$campoAvatar]) && $contenido[$campoAvatar]) {

      // Ruta completa del avatar
      $rutaAvatar = __DIR__ . '/../data/partidas/avatares/' . basename($contenido[$campoAvatar]);

      // Si el archivo del avatar existe en la ruta $rutaAvatar, lo eliminamos
      if (file_exists($rutaAvatar)) unlink($rutaAvatar);
    }
  }

  // Eliminamos el archivo de la partida
  unlink($rutaArchivo);

  return true; // Retornamos true (para indicar que se eliminó correctamente)
}


// Para restaurar el avatar guardado si existe, o devolver el avatar original
function restaurarAvatarGuardado($contenido, $color)
{
  // Campo del avatar guardado
  $campoGuardado = 'avatar_' . $color . '_guardado';

  // Campo del avatar original en la partida
  $campoOriginal = 'avatar_' . $color;

  // Directorio de avatares publicos
  $directorioPublico = __DIR__ . '/../public/imagenes/avatares/';

  // Si existe un avatar guardado, lo copiamos al directorio público y devolvemos su ruta
  if (isset($contenido[$campoGuardado]) && $contenido[$campoGuardado]) {

    // Obtenemos el nombre del archivo 
    $archivo = basename($contenido[$campoGuardado]);

    // Ruta del archivo de origen
    $origen = __DIR__ . '/../data/partidas/avatares/' . $archivo;

    // Si el archivo de origen existe
    if (file_exists($origen)) {

      // Si el directorio público no existe, lo creamos
      /*mkdir($directorioPublico, 0755, true):
        - 0755 son los permisos del directorio (lectura, escritura y ejecución para el propietario; lectura y ejecución para grupo y otros)
        - true indica que se deben crear directorios padres si no existen
      */
      if (!is_dir($directorioPublico)) mkdir($directorioPublico, 0755, true);

      // Limpiamos avatares previos del mismo color
      /* glob devuelve un array con los nombres de los archivos que coinciden con la expresión regular
        - is_file verifica que el elemento es un archivo (y no un directorio)
        - unlink elimina el archivo
      */
      foreach (glob($directorioPublico . 'avatar_' . $color . '_*') as $ruta) if (is_file($ruta)) unlink($ruta);

      // Copiamos el archivo al directorio público
      /* copy copia el archivo de origen al destino
        - $origen: ruta del archivo de origen
        - $directorioPublico . $archivo: ruta del archivo de destino
      */
      copy($origen, $directorioPublico . $archivo);

      // Retornamos la ruta relativa del avatar copiado
      return 'public/imagenes/avatares/' . $archivo;
    }
  }
  // Si no hay avatar guardado, devolvemos el avatar original (si existe)
  // $contenido[$campoOriginal] es el valor que estaba guardado en el JSON bajo la clave 'avatar_blancas' o 'avatar_negras'
  return isset($contenido[$campoOriginal]) ? $contenido[$campoOriginal] : null;
}

// Para confirmar una promoción de peón eligiendo la pieza destino
function procesarConfirmarPromocion()
{
  // Si no hay promoción en curso o no hay partida, salimos
  if (!isset($_SESSION['promocion_en_curso']) || !isset($_SESSION['partida'])) return;

  // Obtenemos el tipo de pieza elegida para la promoción
  $tipo = isset($_POST['tipo_promocion']) ? $_POST['tipo_promocion'] : null;

  $validos = ['Dama', 'Torre', 'Alfil', 'Caballo']; // Array de tipos válidos

  // Si el tipo no es válido, salimos
  if (!$tipo || !in_array($tipo, $validos)) return;

  $partida = unserialize($_SESSION['partida']); // Obtenemos la partida desde sesión

  $color = $_SESSION['promocion_en_curso']['color']; // Color del peón que promueve

  $pos = $_SESSION['promocion_en_curso']['posicion']; // Posición del peón que promueve

  $jugadores = $partida->getJugadores(); // Obtenemos los jugadores de la partida

  $peon = $jugadores[$color]->getPiezaEnPosicion($pos); // Obtenemos el peón que promueve

  // Si el peón existe y puede promoverse, realizamos la promoción
  if ($peon instanceof Peon && $peon->puedePromoverse()) {

    // Promovemos el peón al tipo elegido
    $jugadores[$color]->promoverPeon($peon, $tipo);

    // Actualizamos el mensaje de la partida
    $partida->setMensaje('¡Promoción a ' . $tipo . '! Turno de ' . $jugadores[$partida->getTurno()]->getNombre() . ' (' . $partida->getTurno() . ')');
  }

  $_SESSION['partida'] = serialize($partida); // Guardamos la partida en sesión

  unset($_SESSION['promocion_en_curso']); // Limpiamos la promoción en curso

  $_SESSION['pausa'] = false; // Reanudamos la partida

  $_SESSION['ultimo_tick'] = time(); // Actualizamos el último tick
}


// Para gestionar la confirmación del enroque
function procesarConfirmarEnroque()
{
  // Si no hay un enroque pendiente o no hay partida, salimos de la función
  if (!isset($_SESSION['enroque_pendiente']) || !isset($_SESSION['partida'])) return;

  // Obtenemos los datos del formulario

  // Si no existe alguno de los campos, lo asignamos a null
  $origen = isset($_POST['origen_enroque']) ? $_POST['origen_enroque'] : null;
  $destino = isset($_POST['destino_enroque']) ? $_POST['destino_enroque'] : null;
  $tipo = isset($_POST['tipo_enroque']) ? $_POST['tipo_enroque'] : null;

  // Si falta algún dato o el tipo no es válido, salimos de la función
  if (!$origen || !$destino || !$tipo || !in_array($tipo, ['corto', 'largo'])) return;

  // Recuperamos la partida desde la sesión
  $partida = unserialize($_SESSION['partida']);

  // Ejecutar el enroque
  // Si el enroque se ejecuta correctamente, guardamos la partida en sesión
  if ($partida->ejecutarEnroque($origen, $destino, $tipo)) $_SESSION['partida'] = serialize($partida);

  // Limpiar enroque pendiente y reanudar la partida

  unset($_SESSION['enroque_pendiente']); // Limpiamos el enroque pendiente

  $_SESSION['pausa'] = false; // Reanudamos la partida

  $_SESSION['ultimo_tick'] = time(); // Actualizamos el último tick
}


// Para gestionar la cancelación del enroque
function procesarCancelarEnroque()
{
  // Si hay un enroque pendiente
  if (isset($_SESSION['enroque_pendiente'])) {

    // Simplemente limpiamos la sesión y restauramos mensaje

    unset($_SESSION['enroque_pendiente']); // Limpiamos el enroque pendiente

    $_SESSION['pausa'] = false; // Reanudamos la partida

    $_SESSION['ultimo_tick'] = time(); // Actualizamos el último tick

    // Si hay una partida, restauramos el mensaje del turno actual
    if (isset($_SESSION['partida'])) {

      $partida = unserialize($_SESSION['partida']); // Obtenemos la partida desde sesión

      // Restauramos el mensaje del turno actual
      $partida->setMensaje("Turno de {$partida->getJugadores()[$partida->getTurno()]->getNombre()}");

      $_SESSION['partida'] = serialize($partida); // Guardamos la partida en sesión
    }
  }
}


// Para gestionar la subida de un archivo de avatar personalizado
function manejarSubidaAvatar($nombreCampo, $color)
{
  // Si no se subió ningún archivo o hubo un error en la subida, retornamos null
  if (!isset($_FILES[$nombreCampo]) || $_FILES[$nombreCampo]["error"] !== UPLOAD_ERR_OK) return null;

  $archivo = $_FILES[$nombreCampo]; // Archivo subido

  $tiposPermitidos = ["image/jpeg", "image/png", "image/gif"]; // Tipos MIME permitidos

  $tamañoMaximo = 5 * 1024 * 1024; // Tamaño máximo permitido (5 MB)

  // Validar tipo de archivo

  $infoArchivo = new finfo(FILEINFO_MIME_TYPE); // Objeto finfo para obtener el tipo MIME

  $tipoReal = $infoArchivo->file($archivo["tmp_name"]); // Tipo MIME real del archivo

  if (!in_array($tipoReal, $tiposPermitidos))  return null; // Si el tipo no es permitido, retornamos null

  // Validar tamaño

  // Si el tamaño del archivo excede el máximo permitido, retornamos null
  if ($archivo["size"] > $tamañoMaximo) return null;

  // Crear directorio si no existe

  $directorioSubida = __DIR__ . "/../public/imagenes/avatares/"; // Directorio de subida de avatares

  //Si el directorio no existe, lo creamos
  /* mkdir($directorioSubida, 0755, true):
    - 0755 son los permisos del directorio (lectura, escritura y ejecución para el propietario; lectura y ejecución para grupo y otros)
    - true indica que se deben crear directorios padres si no existen
  */
  if (!is_dir($directorioSubida))  mkdir($directorioSubida, 0755, true);

  // Eliminar avatares personalizados previos del mismo color

  $patron = $directorioSubida . "avatar_" . $color . "_*"; // Patrón para buscar archivos previos

  // Recorremos los archivos que coinciden con el patrón, si el archivo existe, lo eliminamos
  foreach (glob($patron) as $archivoAnterior) if (is_file($archivoAnterior)) unlink($archivoAnterior);


  // Generar nombre único

  $extension = pathinfo($archivo["name"], PATHINFO_EXTENSION); // Obtenemos la extensión del archivo original

  // Nombre único para el archivo
  /* $color es "blancas" o "negras" según el jugador
     time() devuelve el timestamp actual
     uniqid() genera un ID único basado en el tiempo actual en microsegundos
     $extension es la extensión del archivo original  
  */
  $nombreArchivo = "avatar_" . $color . "_" . time() . "_" . uniqid() . "." . $extension;

  $rutaArchivo = $directorioSubida . $nombreArchivo; // Ruta completa del archivo a guardar

  // Mover archivo

  // Si se mueve el archivo subido a la ruta destino, retornamos la ruta relativa del avatar
  if (move_uploaded_file($archivo["tmp_name"], $rutaArchivo)) return "public/imagenes/avatares/" . $nombreArchivo;

  return null; // 
}
