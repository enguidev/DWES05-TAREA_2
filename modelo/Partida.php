<?php

require_once __DIR__ . '/Jugador.php';

/*
  Clase Partida
  Controla el juego completo de ajedrez
*/
class Partida
{
  private $jugadores; // Array de 2 jugadores
  private $turno; // Jugador al que le toca mover
  private $mensaje; // Mensaje de estado de la partida
  private $partidaTerminada; // Indica si la partida ha terminado
  private $historial; // Historial de snapshots para deshacer jugadas
  private $ultimoMovimiento; // Meta del último movimiento realizado
  private $historialMovimientos; // Historial en notación algebraica

  /*
    Constructor de la Partida:
      - Crea dos jugadores con los nombres dados y colores correspondientes.
      - Establece que el turno inicial es para las blancas.
      - Inicializa el mensaje de turno y otros atributos de estado.
      - $nombreBlancas: Nombre del jugador con piezas blancas
      - $nombreNegras: Nombre del jugador con piezas negras
  */
  public function __construct($nombreBlancas = "Jugador 1", $nombreNegras = "Jugador 2")
  {
    $this->jugadores = [
      'blancas' => new Jugador($nombreBlancas, 'blancas'),
      'negras' => new Jugador($nombreNegras, 'negras')
    ];
    $this->turno = 'blancas'; // Las blancas siempre empiezan
    $this->mensaje = "Turno de " . $this->jugadores['blancas']->getNombre() . " (blancas)";
    $this->partidaTerminada = false;
    $this->historial = [];
    $this->ultimoMovimiento = null;
    $this->historialMovimientos = [];
  }

  /*
    Para realizar una jugada:
      - Verificar que la partida no haya terminado
      - Guardar captura del estado actual para poder deshacer
      - Verificar que existe una pieza en el origen
      - Verificar si hay una pieza en el destino (para saber si es captura)
      - Verificar si el movimiento es valido para la pieza
      - Realizar el movimiento
      - Cambiar el turno al otro jugador
      - Actualizar el mensaje de turno
      - Guardar el movimiento en el historial
      - $origen Posición de origen (ej: "E2")
      - $destino Posición de destino (ej: "E4")
      - @return True si la jugada fue exitosa
  */
  public function jugada($origen, $destino)
  {
    // VALIDACIONES Y EJECUCIÓN DEL MOVIMIENTO:

    // 1. Guardar estado actual del tablero (historial)
    $this->guardarHistorial();

    // 1. Verificamos que existe una pieza en el origen
    $piezaOrigen = $this->jugadores[$this->turno]->getPiezaEnPosicion($origen);

    // Si no hay pieza o no es del jugador actual, retornamos false
    if (!$piezaOrigen) {
      $this->mensaje = "No hay ninguna pieza en $origen o no es tu pieza";

      return false; // Retornamos false
    }

    // 2. Verificamos si hay una pieza en el destino (para saber si es captura)
    $piezaDestino = $this->obtenerPiezaEnPosicion($destino);

    $esCaptura = ($piezaDestino !== null); // Es captura si hay pieza en destino

    // 2.1 Detectar posibles movimientos especiales antes del cálculo
    $esEnroque = false; // Indica si es un enroque

    $tipoEnroque = null; // Tipo de enroque: 'corto' o 'largo'

    $esEnPassant = false; // Indica si es captura al paso

    $posCapturaEnPassant = null; // Posición del peón capturado al paso

    // Coordenadas para cálculos especiales
    // Convertimos las posiciones a coordenadas numéricas
    $coordsOrigen = $this->notacionACoordsLocal($origen);

    $coordsDestino = $this->notacionACoordsLocal($destino);

    // Si alguna de las conversiones falla
    if (!$coordsOrigen || !$coordsDestino) {
      $this->mensaje = "Coordenadas inválidas"; // Actualizamos el mensaje

      return false; // Retornamos false
    }

    // Obtenemos las coordenadas de las posiciones actuales y nuevas
    // Con list() asignamos los valores de los arrays a variables individuales (desestructuración de arrays)
    list($filaOrig, $colOrig) = $coordsOrigen;
    list($filaDest, $colDest) = $coordsDestino;

    // Enroque: el rey se desplaza 2 columnas en la misma fila
    // Se requiere confirmación del jugador vía modal (ventanita de confirmación)
    // Si la pieza es un rey y no ha movido aún
    if ($piezaOrigen instanceof Rey && !$piezaOrigen->haMovido()) {

      // Si se mueve 2 columnas en la misma fila
      if ($filaOrig === $filaDest && abs($colDest - $colOrig) === 2) {

        // Si es hacia la derecha, es enroque corto; si es hacia la izquierda, es enroque largo
        if ($colDest > $colOrig) {

          // Si es corto (E -> G)
          if ($this->puedeEnrocarCorto($this->turno)) {

            // En lugar de ejecutar automáticamente, pedimos confirmación
            $_SESSION['enroque_pendiente'] = [ // Guardamos en sesión el enroque pendiente

              'tipo' => 'corto', // Tipo de enroque

              'color' => $this->turno, // Color del jugador

              'origen' => $origen, // Origen de la pieza

              'destino' => $destino // Destino de la pieza
            ];

            $this->mensaje = "¿Deseas hacer enroque corto?"; // Actualizamos el mensaje

            return false; // Retornamos false para no ejecutar todavía

            // Si no puede enrocar corto
          } else {

            $this->mensaje = "Enroque corto no permitido"; // Actualizamos el mensaje

            return false; // Retornamos false
          }

          // Si es hacia la izquierda
        } else {

          // Si es largo (E -> C)
          if ($this->puedeEnrocarLargo($this->turno)) {

            // En lugar de ejecutar automáticamente, pedimos confirmación
            $_SESSION['enroque_pendiente'] = [ // Guardamos en sesión el enroque pendiente

              'tipo' => 'largo', // Tipo de enroque

              'color' => $this->turno, // Color del jugador

              'origen' => $origen, // Origen de la pieza

              'destino' => $destino // Destino de la pieza
            ];

            $this->mensaje = "¿Deseas hacer enroque largo?"; // Actualizamos el mensaje

            return false; // Retornamos false para no ejecutar todavía

            // Si no puede enrocar largo
          } else {

            $this->mensaje = "Enroque largo no permitido"; // Actualizamos el mensaje

            return false; // Retornamos false
          }
        }
      }
    }

    // Captura al paso (peón se mueve diagonal a casilla vacía en el turno inmediatamente posterior):
    // Si no es enroque, la pieza es un peón y no es captura normal
    if (!$esEnroque && $piezaOrigen instanceof Peon && !$esCaptura) {

      $direccion = ($this->turno === 'blancas') ? -1 : 1; // Dirección de avance según el color

      $difFilas = $filaDest - $filaOrig; // Diferencia de filas

      $difCols = abs($colDest - $colOrig); // Diferencia de columnas

      // Si el peón se mueve una fila en la dirección correcta y una columna (diagonal)
      if ($difFilas === $direccion && $difCols === 1) {

        // Casilla del peón que sería capturado al paso (misma fila de origen, columna destino)
        $posCapturaEnPassant = $this->coordsANotacionLocal($filaOrig, $colDest);

        // Obtenemos la pieza en esa posición
        $piezaPosibleCapturada = $this->obtenerPiezaEnPosicion($posCapturaEnPassant);

        // Validar último movimiento del contrario:
        // Si existe un último movimiento registrado
        if ($this->ultimoMovimiento && $piezaPosibleCapturada instanceof Peon) {

          $um = $this->ultimoMovimiento; // Último movimiento realizado

          // Debe ser peón del oponente que avanzó 2 casillas y acabó en la posición a capturar
          $coordenadas_del_origen_del_ultimo_movimiento = $this->notacionACoordsLocal($um['origen']); // Coordenadas del origen del último movimiento

          $coordenadas_del_destino_del_ultimo_movimiento = $this->notacionACoordsLocal($um['destino']); // Coordenadas del destino del último movimiento

          // Si el peón del oponente avanzó 2 casillas hacia la posición de captura al paso 
          if ($coordenadas_del_origen_del_ultimo_movimiento && $coordenadas_del_destino_del_ultimo_movimiento && $um['pieza'] === 'Peon' && $um['color'] !== $this->turno) {

            // Número de filas que saltó el peón 
            $salto = abs($coordenadas_del_destino_del_ultimo_movimiento[0] - $coordenadas_del_origen_del_ultimo_movimiento[0]);

            // Si el peón avanzó 2 casillas y acabó en la posicion a capturar
            if ($salto === 2 && $um['destino'] === $posCapturaEnPassant) {

              // Se trata como captura al paso por lo que asignamos true a $esCaptura
              $esEnPassant = true;

              $esCaptura = true; // Asignamos true a $esCaptura
            }
          }
        }
      }
    }

    // 3. Verificar que la pieza puede moverse a ese destino
    // Para peones, debemos indicar si es captura
    // Si la pieza es un peón, simulamos el movimiento
    if ($piezaOrigen instanceof Peon) $casillasRecorridas = $piezaOrigen->simulaMovimiento($destino, $esCaptura);
    // Si no es peón, simulamos el movimiento sin captura
    else $casillasRecorridas = $piezaOrigen->simulaMovimiento($destino);

    // Si no hay casillas recorridas, el movimiento es inválido
    if (!$esEnroque && !$esEnPassant && empty($casillasRecorridas)) {

      $this->mensaje = "Movimiento inválido para esta pieza"; // Actualizamos el mensaje

      return false; // Retornamos false
    }

    // 4. Verificar que no hay piezas en las posiciones intermedias (excepto caballo)
    $hayPiezasIntermedias = false;

    // Para todas las casillas excepto la última (destino)
    // Recorremos el array de casillas recorridas
    for ($i = 0; $i < count($casillasRecorridas) - 1; $i++) {

      $casilla = $casillasRecorridas[$i]; // Casilla intermedia

      // Si hay una pieza en esa casilla
      if ($this->obtenerPiezaEnPosicion($casilla) !== null) {

        $hayPiezasIntermedias = true; // Asignamos true a $hayPiezasIntermedias

        break; // Salimos del bucle
      }
    }

    // El caballo puede saltar, los demás no
    // Si hay piezas intermedias y no es caballo
    if ($hayPiezasIntermedias && !($piezaOrigen instanceof Caballo)) {

      $this->mensaje = "Hay piezas bloqueando el camino"; // Actualizamos el mensaje

      return false; // Retornamos false
    }

    // Evitar movimientos que dejen al propio rey en jaque
    // Simulamos el movimiento en una copia clonada de $this->jugadores
    $backup = serialize($this->jugadores);

    // Creamos un estado temporal clonando jugadores
    $tempJugadores = unserialize($backup);

    // Obtener las piezas dentro del estado temporal:
    $piezaOrigenTemp = $tempJugadores[$this->turno]->getPiezaEnPosicion($origen);
    $piezaDestinoTemp = $this->obtenerPiezaEnPosicion($destino, $tempJugadores);

    // Si es captura al paso
    if ($esEnPassant) {

      // Capturar pieza al paso en estado temporal
      $piezaAlPasoTemp = $this->obtenerPiezaEnPosicion($posCapturaEnPassant, $tempJugadores);

      // Si la pieza al paso existe, la capturamos
      if ($piezaAlPasoTemp && $piezaAlPasoTemp instanceof Peon) $piezaAlPasoTemp->capturar();

      // Si es captura normal, capturamos la pieza destino
      elseif ($piezaDestinoTemp) $piezaDestinoTemp->capturar();
    }

    // Si no es captura al paso ni enroque, movemos la pieza normalmente   
    if ($piezaOrigenTemp && !$esEnroque) {

      $piezaOrigenTemp->setPosicion($destino); // Movemos la pieza en el estado temporal

      $piezaOrigenTemp->setHaMovido(true); // Marcamos que ha movido
    }

    // Simular enroque moviendo rey y torre en temp si aplica:

    // Si es enroque
    if ($esEnroque) {

      $color = $this->turno; // Color del jugador

      $filaInicial = ($color === 'blancas') ? 1 : 8; // Fila inicial según color

      $posReyOrigen = ($color === 'blancas') ? 'E1' : 'E8'; // Posición inicial del rey

      // Posiciones según tipo de enroque
      $posReyDestino = ($color === 'blancas') ? (($tipoEnroque === 'corto') ? 'G1' : 'C1') : (($tipoEnroque === 'corto') ? 'G8' : 'C8');

      // Posiciones de la torre
      $posTorreOrigen = ($color === 'blancas') ? (($tipoEnroque === 'corto') ? 'H1' : 'A1') : (($tipoEnroque === 'corto') ? 'H8' : 'A8');

      // Posición destino de la torre
      $posTorreDestino = ($color === 'blancas') ? (($tipoEnroque === 'corto') ? 'F1' : 'D1') : (($tipoEnroque === 'corto') ? 'F8' : 'D8');

      $reyTemp = $tempJugadores[$color]->getRey(); // Obtenemos el rey temporal

      // Obtenemos la torre temporal
      $torreTemp = $tempJugadores[$color]->getPiezaEnPosicion($posTorreOrigen);

      // Si no se encuentran rey o torre
      if (!$reyTemp || !$torreTemp) {

        $this->mensaje = 'Enroque no válido'; // Actualizamos el mensaje

        return false; // Retornamos false
      }

      $reyTemp->setPosicion($posReyDestino); // Movemos el rey en el estado temporal

      $torreTemp->setPosicion($posTorreDestino); // Movemos la torre en el estado temporal
    }

    $miColor = $this->turno; // Color del jugador que mueve

    $quedaEnJaque = $this->estaEnJaque($miColor, $tempJugadores); // Verificamos si queda en jaque

    //  Si queda en jaque
    if ($quedaEnJaque) {

      $this->mensaje = "Movimiento inválido: deja en jaque a tu rey"; // Actualizamos el mensaje

      return false; // Retornamos false
    }

    // 5. Verificar la casilla de destino:
    // Si no es captura al paso ni enroque
    if (!$esEnroque && !$esEnPassant && $piezaDestino !== null) {

      // Si la pieza destino es del mismo color
      if ($piezaDestino->getColor() === $this->turno) {

        // No se puede capturar a tus propias piezas
        $this->mensaje = "No puedes capturar tus propias piezas"; // Actualizamos el mensaje

        return false; // Retornamos false

        // Si es de color contrario
      } else {

        $piezaDestino->capturar(); // Capturamos la pieza

        // Verificar si era el rey (porque sería fin de partida):
        // Si la pieza capturada es un rey
        if ($piezaDestino instanceof Rey) {

          $this->partidaTerminada = true; // Asignamos true a partidaTerminada

          // El ganador es el jugador que movió
          $nombreGanador = $this->jugadores[$this->turno]->getNombre();

          // Actualizamos el mensaje de victoria
          $this->mensaje = "¡Jaque mate! " . $nombreGanador . " ha ganado la partida";

          return true; // Retornamos true 
        }
      }
    }

    // 6. Realizar el movimiento (incluye especiales):
    // Si es enroque
    if ($esEnroque) {

      $color = $this->turno; // Color del jugador

      // Posiciones según tipo de enroque
      $posReyDestino = ($color === 'blancas') ? (($tipoEnroque === 'corto') ? 'G1' : 'C1') : (($tipoEnroque === 'corto') ? 'G8' : 'C8');

      // Posiciones de la torre
      $posTorreOrigen = ($color === 'blancas') ? (($tipoEnroque === 'corto') ? 'H1' : 'A1') : (($tipoEnroque === 'corto') ? 'H8' : 'A8');

      // Posición destino de la torre
      $posTorreDestino = ($color === 'blancas') ? (($tipoEnroque === 'corto') ? 'F1' : 'D1') : (($tipoEnroque === 'corto') ? 'F8' : 'D8');

      // Obtener la torre
      $torre = $this->jugadores[$color]->getPiezaEnPosicion($posTorreOrigen);

      // Si no se encuentra la torre
      if (!$torre || !($torre instanceof Torre)) {

        $this->mensaje = 'No se encontró la torre para enroque'; // Actualizamos el mensaje

        return false; // Retornamos false
      }

      // Mover rey y torre
      $piezaOrigen->setPosicion($posReyDestino); // Mover el rey

      $piezaOrigen->setHaMovido(true); // Marcar el rey como movido

      $torre->setPosicion($posTorreDestino); // Mover la torre

      $torre->setHaMovido(true); // Marcar la torre como movida

      // Si no es enroque
    } else {

      // Si la pieza origen es un peón
      if ($piezaOrigen instanceof Peon) {

        // Si es captura al paso
        if ($esEnPassant && $posCapturaEnPassant) {

          // Capturar la pieza al paso
          $piezaAlPaso = $this->obtenerPiezaEnPosicion($posCapturaEnPassant);

          // Si la pieza al paso existe, la capturamos
          if ($piezaAlPaso && $piezaAlPaso instanceof Peon) $piezaAlPaso->capturar();
        }

        $piezaOrigen->movimiento($destino, $esCaptura); // Movemos el peón con captura

        // Si no es peón, movemos la pieza
      } else  $piezaOrigen->movimiento($destino);
    }

    // 6.5. Verificamos promoción de peón (mediante ventana modal)

    // Registramos último movimiento (para captura al paso)
    $this->ultimoMovimiento = [ // Array con datos del último movimiento

      // Pieza, color, origen, destino:
      'pieza' => ($piezaOrigen instanceof Peon) ? 'Peon' : (($piezaOrigen instanceof Rey) ? 'Rey' : get_class($piezaOrigen)),

      'color' => $this->turno,

      'origen' => $origen,

      'destino' => $destino
    ];

    // 7. Cambiar el turno:

    // Cambiamos el turno al otro jugador/oponente
    $this->turno = ($this->turno === 'blancas') ? 'negras' : 'blancas';

    $jugadorSiguiente = $this->turno; // Jugador al que le toca mover

    // Si el siguiente jugador está en jaque
    if ($this->estaEnJaque($jugadorSiguiente)) {

      // Si además está en jaque mate
      if ($this->esJaqueMate($jugadorSiguiente)) {

        $this->partidaTerminada = true; // Asignamos true a partidaTerminada

        // El ganador es el jugador que movió (el anterior)
        $ganadorColor = ($jugadorSiguiente === 'blancas') ? 'negras' : 'blancas';

        // Obtenemos el nombre del ganador
        $nombreGanador = $this->jugadores[$ganadorColor]->getNombre();

        // Actualizamos el mensaje de victoria!!!
        $this->mensaje = "¡Jaque mate! " . $nombreGanador . " ha ganado la partida";

        // Si solo está en jaque, actualizamos el mensaje
      } else $this->mensaje = "Jaque a " . $this->jugadores[$jugadorSiguiente]->getNombre();

      // Si no está en jaque, actualizamos el mensaje normal
    } else $this->mensaje = "Turno de " . $this->jugadores[$this->turno]->getNombre() .
      " (" . $this->turno . ")";

    // Registrar movimiento en notación algebraica:
    // Parámetros: origen, destino, pieza, esCaptura, esEnroque, tipoEnroque, enJaque, jaqueMate
    $this->registrarMovimientoEnNotacion($origen, $destino, $piezaOrigen, $esCaptura, $esEnroque, $tipoEnroque, $this->estaEnJaque($jugadorSiguiente), $this->esJaqueMate($jugadorSiguiente));

    return true; // Retornamos true
  }

  // Para registrar un movimiento en notación algebraica estándar
  private function registrarMovimientoEnNotacion($origen, $destino, $pieza, $esCaptura, $esEnroque, $tipoEnroque, $enJaque, $jaqueMate)
  {
    $numero = count($this->historialMovimientos) + 1; // Número del movimiento

    //Si es enroque, generamos notación especial
    if ($esEnroque) $notacion = ($tipoEnroque === 'corto') ? 'O-O' : 'O-O-O';

    // Si no es enroque, generamos notación normal
    else $notacion = $this->generarNotacionAlgebraica($origen, $destino, $pieza, $esCaptura);


    // Añadimos el indicador de jaque/jaque mate:
    // Si es jaque mate, notación += '#', si es jaque, notación += '+'
    if ($jaqueMate) $notacion .= '#';
    elseif ($enJaque) $notacion .= '+';


    // Guardamos el movimiento en historial
    $this->historialMovimientos[] = [ // Array con datos del movimiento

      'numero' => $numero, // Número del movimiento

      'color' => $this->turno === 'blancas' ? 'negras' : 'blancas', // Color del jugador que movió

      'notacion' => $notacion, // Notación algebraica

      'origen' => $origen, // Posición de origen

      'destino' => $destino, // Posición de destino

      'captura' => $esCaptura, // Indica si fue captura

      'jaque' => $enJaque, // Indica si fue jaque

      'jaqueMate' => $jaqueMate // Indica si fue jaque mate
    ];
  }


  // Para generar notación algebraica
  private function generarNotacionAlgebraica($origen, $destino, $pieza, $esCaptura)
  {
    $notacion = ''; // Inicializamos notación vacía

    // Identificar letra de la pieza (excepto peones):

    // Si es una Torre, añadimos 'T'
    if ($pieza instanceof Torre) $notacion = 'T';

    // Si es un Caballo, añadimos 'C'
    elseif ($pieza instanceof Caballo) $notacion = 'C';

    // Si es un Alfil, añadimos 'A'
    elseif ($pieza instanceof Alfil) $notacion = 'A';

    // Si es una Dama, añadimos 'D'
    elseif ($pieza instanceof Dama) $notacion = 'D';

    // Si es un Rey, añadimos 'R'
    elseif ($pieza instanceof Rey) $notacion = 'R';

    // Si hay captura
    if ($esCaptura) {

      // Si es peon, añadimos la columna de origen en minúscula
      if ($pieza instanceof Peon) $notacion .= strtolower($origen[0]);

      $notacion .= 'x'; // Añadimos 'x' para indicar captura
    }

    // Añadimos la posición de destino en minúscula (strtolower())
    $notacion .= strtolower($destino);

    // Si la pieza es un peón
    if ($pieza instanceof Peon) {

      $coords = $this->notacionACoordsLocal($destino); // Convertimos destino a coordenadas

      // Si el peón llegó a la última fila según su color
      if ($coords && (($pieza->getColor() === 'blancas' && $coords[0] === 0) || ($pieza->getColor() === 'negras' && $coords[0] === 7))) {

        $notacion .= '=D'; // Añadimos '=D' para indicar promoción a Dama
      }
    }

    return $notacion; // Retornamos la notación algebraica generada
  }


  // Para obtener el historial de movimientos en notación algebraica
  public function getHistorialMovimientos()
  {
    return $this->historialMovimientos; // Retornamos el historial de movimientos
  }


  // Para establecer el historial de movimientos
  public function setHistorialMovimientos($historial)
  {
    $this->historialMovimientos = $historial; // Establecemos el historial de movimientos
  }


  /*
    Para obtener una pieza en una posición específica (de cualquier jugador)
    @param string $posicion Posición a buscar
    @return Pieza|null La pieza encontrada o null
  */
  private function obtenerPiezaEnPosicion($posicion, $jugadores = null)
  {
    $jugadoresUso = $jugadores ?? $this->jugadores;

    // Buscar en las piezas blancas
    $pieza = $jugadoresUso['blancas']->getPiezaEnPosicion($posicion);
    if ($pieza) return $pieza;

    // Buscar en las piezas negras
    $pieza = $jugadoresUso['negras']->getPiezaEnPosicion($posicion);
    if ($pieza) return $pieza;

    return null;
  }



  /**
   * Comprueba si el rey del color dado está en jaque
   * @param string $color "blancas" o "negras"
   * @return bool
   */
  /*
    Para comprobar si el rey del color dado esta en jaque
      -$color: Color del jugador a comprobar ('blancas' o 'negras')
      -@return True si el rey está en jaque, False en caso contrario
  */
  public function estaEnJaque($color, $jugadores = null)
  {
    $jugadoresUso = $jugadores ?? $this->jugadores; // Usamos jugadores dados o los actuales

    $rey = $jugadoresUso[$color]->getRey(); // Obtenemos el rey del color dado

    // Si no se encuentra el rey, retornamos false
    if ($rey === null) return false;

    $posicion_de_rey = $rey->getPosicion(); // Obtenemos la posición del rey

    $oponente = ($color === 'blancas') ? 'negras' : 'blancas'; // Color del oponente

    // Comprobamos todas las piezas del oponente para ver si alguna amenaza la posición del rey
    foreach ($jugadoresUso[$oponente]->getPiezas() as $pieza) {

      if ($pieza->estaCapturada()) continue; // Si la pieza está capturada, saltamos

      // Si la pieza es un peón, simulamos el movimiento con captura
      if ($pieza instanceof Peon) $casillas_recorridas = $pieza->simulaMovimiento($posicion_de_rey, true);

      // Si no es peón, simulamos el movimiento normal
      else $casillas_recorridas = $pieza->simulaMovimiento($posicion_de_rey);

      // Si no hay movimientos posibles, continuamos con la siguiente pieza
      if (empty($casillas_recorridas)) continue;

      // Comprobar bloqueo para piezas que no son caballo:
      // Si la pieza no es un caballo
      if (!($pieza instanceof Caballo)) {

        $bloqueado = false; // Asignamos false a bloqueado

        // Recorremos las casillas intermedias
        for ($i = 0; $i < count($casillas_recorridas) - 1; $i++) {

          // Si hay una pieza en la casilla intermedia
          if ($this->obtenerPiezaEnPosicion($casillas_recorridas[$i], $jugadoresUso) !== null) {

            $bloqueado = true; // Asignamos true a bloqueado

            break; // Salimos del bucle
          }
        }

        if ($bloqueado) continue; // Si está bloqueado, continuamos con la siguiente pieza
      }

      // Si llega aquí, la pieza amenaza la casilla del rey
      return true; // Retornamos true (rey en jaque)
    }

    return false; // Retornamos false (rey no en jaque)
  }

  /*
    Para comprobar si el color dado esta en jaque mate
      -$color: Color del jugador a comprobar ('blancas' o 'negras')
      -@return True si el jugador está en jaque mate, False en caso contrario
  */
  public function esJaqueMate($color)
  {
    if (!$this->estaEnJaque($color)) return false; // Si no está en jaque, no es jaque mate

    // Probar todas las jugadas posibles del color y ver si alguna quita el jaque
    $misPiezas = $this->jugadores[$color]->getPiezas(); // Obtenemos  mis piezas

    $todas = $this->obtenerTodasCasillas(); // Obtenemos todas las casillas A1..H8

    // Para cada pieza mía
    foreach ($misPiezas as $pieza) {

      if ($pieza->estaCapturada()) continue; // Si la pieza está capturada, saltamos

      // Para cada casilla del tablero
      foreach ($todas as $destino) {

        // Determinar si el destino está ocupado y por quién
        $piezaDestinoObj = $this->obtenerPiezaEnPosicion($destino);

        // Si la casilla destino está ocupada por pieza propia, saltamos
        if ($piezaDestinoObj && $piezaDestinoObj->getColor() === $color)  continue;

        // Determinar si el movimiento es posible según la pieza:
        // Si la pieza es un peón
        if ($pieza instanceof Peon) {

          // Simulamos el movimiento con captura
          $esCaptura = ($piezaDestinoObj !== null) && ($piezaDestinoObj->getColor() !== $color);

          $casillas_recorridas = $pieza->simulaMovimiento($destino, $esCaptura); // Simulamos el movimiento

          // Si la pieza no es un peón, simulamos el movimiento normal
        } else $casillas_recorridas = $pieza->simulaMovimiento($destino);


        if (empty($casillas_recorridas)) continue; // Si no hay movimientos posibles, continuamos con la siguiente casilla

        // Comprobar bloqueo para piezas que no son caballo:
        // Si la pieza no es un caballo
        if (!($pieza instanceof Caballo)) {

          $bloqueado = false; // Asignamos false a bloqueado

          // Recorremos las casillas intermedias
          for ($i = 0; $i < count($casillas_recorridas) - 1; $i++) {

            // Si hay una pieza en la casilla intermedia
            if ($this->obtenerPiezaEnPosicion($casillas_recorridas[$i]) !== null) {

              $bloqueado = true; // Asignamos true a bloqueado

              break; // Salimos del bucle
            }
          }

          if ($bloqueado) continue; // Si está bloqueado, continuamos con la siguiente casilla
        }

        // Simular el movimiento en una copia clonada del estado de jugadores
        $jugadoresUso = $this->jugadores; // Estado actual de jugadores

        $backup = serialize($jugadoresUso); // Serializamos el estado actual

        $tempJugadores = unserialize($backup); // Creamos un estado temporal clonando jugadores

        // Encontrar la pieza correspondiente en el estado temporal (buscando por posición original)
        $origenPos = $pieza->getPosicion(); // Posición original de la pieza

        $piezaTemp = $tempJugadores[$color]->getPiezaEnPosicion($origenPos); // Obtenemos la pieza temporal

        $piezaDestinoTemp = $this->obtenerPiezaEnPosicion($destino, $tempJugadores); // Obtenemos la pieza destino temporal

        // Si hay una pieza destino temporal, la capturamos
        if ($piezaDestinoTemp) $piezaDestinoTemp->capturar();

        // Si la pieza temporal existe
        if ($piezaTemp) {

          $piezaTemp->setPosicion($destino); // Movemos la pieza en el estado temporal

          // Si la pieza es un peón, marcamos que no es su primer movimiento
          if ($piezaTemp instanceof Peon) $piezaTemp->setEsPrimerMovimiento(false);
        }

        // Verificamos si queda en jaque después del movimiento simulado
        $quedaEnJaque = $this->estaEnJaque($color, $tempJugadores);

        // Si no queda en jaque, la jugada quita el jaque
        if (!$quedaEnJaque) return false;
      }
    }

    // Si llegamos aquí, ninguna jugada quita el jaque
    return true; // Retornamos true (jaque mate)
  }


  /*
    Para obtener todas las casillas del tablero A1..H8
      -@return Array con todas las casillas
  */
  private function obtenerTodasCasillas()
  {
    // Obtenemos todas las casillas
    $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    $out = []; // Array de salida

    // Recorremos todas las filas
    for ($fila = 1; $fila <= 8; $fila++) {

      // Recorremos todas las columnas
      foreach ($cols as $c) {

        $out[] = $c . $fila; // Añadimos la casilla al array de salida
      }
    }
    return $out; // Retornamos el array de casillas
  }


  /*
   Para obtener el marcador de la partida
     -@return Array con los puntos [puntosBlancas, puntosNegras]  
  */
  public function marcador()
  {
    $puntosBlancas = 0; // Inicializamos los puntos de las blancas en 0
    $puntosNegras = 0; // Inicializamos los puntos de las negras en 0

    // Sumar puntos de piezas capturadas del oponente:
    foreach ($this->jugadores['negras']->getPiezas() as $pieza) {

      // Si la pieza está capturada, sumamos su valor a los puntos de las blancas
      if ($pieza->estaCapturada()) $puntosBlancas += $pieza->getValor();
    }

    // Hacemos lo mismo para las piezas blancas
    foreach ($this->jugadores['blancas']->getPiezas() as $pieza) {

      // Si la pieza está capturada, sumamos su valor a los puntos de las negras
      if ($pieza->estaCapturada()) $puntosNegras += $pieza->getValor();
    }

    return [$puntosBlancas, $puntosNegras]; // Retornamos el array con los puntos
  }


  /*
    Para mostrar el tablero en modo texto
      -@return String con la representación del tablero
  */
  public function muestraTablero()
  {
    // Construimos la representación del tablero
    $tablero = "\n  A B C D E F G H\n"; // Encabezado de columnas

    // Recorremos las filas
    for ($fila = 8; $fila >= 1; $fila--) {

      $tablero .= $fila . " "; // Número de fila

      // Recorremos las columnas
      for ($col = 0; $col < 8; $col++) {

        $columna = chr(ord('A') + $col); // Convertimos índice a letra de columna
        $posicion = $columna . $fila; // Construimos la posición (ejemplo: 'A1')

        $pieza = $this->obtenerPiezaEnPosicion($posicion); // Obtenemos la pieza en esa posición

        // Si no hay pieza, mostramos 'x', si hay pieza, mostramos su letra
        if ($pieza === null) $tablero .= "x ";

        // Si hay pieza
        else {

          $letra = $this->obtenerLetraPieza($pieza); // Obtenemos la letra representativa de la pieza

          // Mayúsculas para blancas, minúsculas para negras
          $letra = ($pieza->getColor() === 'blancas') ? strtoupper($letra) : strtolower($letra);

          $tablero .= $letra . " "; // Añadimos la letra al tablero
        }
      }

      $tablero .= $fila . "\n"; // Número de fila al final
    }

    $tablero .= "  A B C D E F G H\n"; // Pie de columnas

    return $tablero; // Retornamos la representación del tablero
  }


  /*
  Para obtener la letra representativa de una pieza
    -$pieza: La pieza a evaluar
    -@return Letra representativa de la pieza:
      T = Torre
      C = Caballo
      A = Alfil
      D = Dama
      R = Rey
      P = Peón
  */
  private function obtenerLetraPieza($pieza)
  {
    if ($pieza instanceof Torre) return 'T'; // Si es torre, retornamos 'T'
    if ($pieza instanceof Caballo) return 'C'; // Si es caballo, retornamos 'C'
    if ($pieza instanceof Alfil) return 'A'; // Si es alfil, retornamos 'A'
    if ($pieza instanceof Dama) return 'D'; // Si es dama, retornamos 'D'
    if ($pieza instanceof Rey) return 'R'; // Si es rey, retornamos 'R'
    if ($pieza instanceof Peon) return 'P'; // Si es peón, retornamos 'P'

    return '?'; // Si no coincide con ninguna, retornamos '?'
  }


  /*
  Para obtener el mensaje actual de la partida
    -@return Mensaje de estado  
  */
  public function getMensaje()
  {
    return $this->mensaje; // Retornamos el mensaje
  }


  /*
  Para obtener el turno actual
    -@return Color del jugador actual ('blancas' o 'negras')
  */
  public function getTurno()
  {
    return $this->turno; // Retornamos el turno actual
  }


  /*
  Para verificar si la partida ha terminado
    -@return True si la partida ha terminado, False en caso contrario
  */
  public function estaTerminada()
  {
    return $this->partidaTerminada; // Retornamos el estado de la partida
  }

  /*
  Para obtener todos los jugadores
    -@return Array asociativo con los jugadores  
  */
  public function getJugadores()
  {
    return $this->jugadores; // Retornamos el array de jugadores
  }


  /*
  Para verificar si hay historial de jugadas disponible para deshacer
    -@return True si hay jugadas en el historial, False en caso contrario
  */
  public function tieneHistorial()
  {
    return !empty($this->historial); // Retornamos true si hay historial, false si está vacío
  }


  /*
  Para deshacer la última jugada realizada
    -@return True si se pudo deshacer, False si no hay jugadas para deshacer
  */
  public function deshacerJugada()
  {
    // Si no hay historial, no se puede deshacer
    if (empty($this->historial)) {

      $this->mensaje = "No hay jugadas para deshacer"; // Actualizamos el mensaje

      return false; // Retornamos false
    }

    // Restaurar la ultima captura del historial
    $snapshot = unserialize(array_pop($this->historial)); // Obtenemos la ultima captura del historial

    $this->jugadores = $snapshot['jugadores']; // Restauramos los jugadores

    $this->turno = $snapshot['turno']; // Restauramos el turno

    $this->mensaje = $snapshot['mensaje']; // Restauramos el mensaje

    $this->partidaTerminada = $snapshot['partidaTerminada']; // Restauramos el estado de la partida

    return true; // Retornamos true
  }


  /*
  Para comprobar si el enroque corto está disponible para el color dado
    -$color: Color del jugador ('blancas' o 'negras')
    -@return True si el enroque corto está disponible, False en caso contrario
  */
  public function puedeEnrocarCorto($color)
  {
    $rey = $this->jugadores[$color]->getRey(); // Obtenemos el rey del color dado

    // Si no hay rey, o ha movido, o está en jaque, no se puede enrocar, por lo que retornamos false
    if (!$rey || $rey->haMovido() || $this->estaEnJaque($color)) return false;

    // Obtenemos la torre correspondiente
    $torre = $this->jugadores[$color]->getPiezaEnPosicion($color === 'blancas' ? 'H1' : 'H8');

    // Si no hay torre, o no es torre, o ha movido, retornamos false
    if (!$torre || !($torre instanceof Torre) || $torre->haMovido()) return false;

    // Verificamos que las casillas entre rey y torre estén libres y no en jaque
    $casillas = $color === 'blancas' ? ['F1', 'G1'] : ['F8', 'G8'];

    // Para cada casilla entre rey y torre
    foreach ($casillas as $casilla) {

      // Si la casilla no está libre, retornamos false
      if ($this->obtenerPiezaEnPosicion($casilla) !== null) return false;

      // Simular movimiento del rey a esa casilla y verificar jaque:
      $posOriginal = $rey->getPosicion(); // Guardamos posición original

      $rey->setPosicion($casilla); // Movemos el rey a la casilla

      $enJaque = $this->estaEnJaque($color); // Verificamos si está en jaque

      $rey->setPosicion($posOriginal); // Restaurar

      if ($enJaque) return false; // Si queda en jaque, retornamos false
    }
    // Si llega aquí, el enroque corto es posible
    return true; // Retornamos true
  }


  /*
  Para comprobar si el enroque largo está disponible para el color dado
    -$color: Color del jugador ('blancas' o 'negras')
    -@return True si el enroque largo está disponible, False en caso contrario
  */
  public function puedeEnrocarLargo($color)
  {
    $rey = $this->jugadores[$color]->getRey(); // Obtenemos el rey del color dado

    // Si no hay rey, o ha movido, o está en jaque, no se puede enrocar, por lo que retornamos false
    if (!$rey || $rey->haMovido() || $this->estaEnJaque($color)) return false;

    // Obtenemos la torre correspondiente
    $torre = $this->jugadores[$color]->getPiezaEnPosicion($color === 'blancas' ? 'A1' : 'A8');

    // Si no hay torre, o no es torre, o ha movido, retornamos false
    if (!$torre || !($torre instanceof Torre) || $torre->haMovido()) return false;

    // 
    $casillas = $color === 'blancas' ? ['B1', 'C1', 'D1'] : ['B8', 'C8', 'D8']; // Casillas entre rey y torre

    // Para cada casilla entre rey y torre
    foreach ($casillas as $casilla) {

      // Si la casilla no está libre, retornamos false
      if ($this->obtenerPiezaEnPosicion($casilla) !== null) return false;

      // Para largo, comprobar jaque en C y D; B puede estar fuera del trayecto del rey:
      // Si la casilla es C1/C8 o D1/D8 
      if ($casilla === ($color === 'blancas' ? 'C1' : 'C8') || $casilla === ($color === 'blancas' ? 'D1' : 'D8')) {

        $posOriginal = $rey->getPosicion(); // Guardamos posición original

        $rey->setPosicion($casilla); // Movemos el rey a la casilla

        $enJaque = $this->estaEnJaque($color); // Verificamos si está en jaque

        $rey->setPosicion($posOriginal); // Restauramos la posición original

        if ($enJaque) return false; // Si queda en jaque, retornamos false
      }
    }
    return true; // Retornamos true (ya que el enroque largo es posible)
  }

  /**
   * Conversión local de notación (A1..H8) a coordenadas [fila, col]
   */
  private function notacionACoordsLocal($pos)
  {
    if (!is_string($pos) || strlen($pos) < 2) return null;
    $colLetra = strtoupper($pos[0]);
    $filaNum = (int)substr($pos, 1);
    if ($colLetra < 'A' || $colLetra > 'H' || $filaNum < 1 || $filaNum > 8) return null;
    $col = ord($colLetra) - ord('A');
    $fila = 8 - $filaNum; // 8->0, 1->7
    return [$fila, $col];
  }

  /**
   * Conversión local de coordenadas [fila, col] a notación A1..H8
   */
  private function coordsANotacionLocal($fila, $col)
  {
    if ($fila < 0 || $fila > 7 || $col < 0 || $col > 7) return null;
    $colLetra = chr(ord('A') + $col);
    $filaNum = 8 - $fila;
    return $colLetra . $filaNum;
  }

  /**
   * Verifica si la partida está en tablas
   * @return bool True si hay tablas
   */
  public function esTablas()
  {
    // Verificar stalemate: turno actual no puede mover pero no está en jaque
    if (!$this->estaEnJaque($this->turno) && !$this->tieneMovimientosLegales($this->turno)) return true;

    // Insuficiente material: solo reyes, o rey + caballo/alfil vs rey
    $piezasBlancas = array_filter($this->jugadores['blancas']->getPiezas(), fn($p) => !$p->estaCapturada());
    $piezasNegras = array_filter($this->jugadores['negras']->getPiezas(), fn($p) => !$p->estaCapturada());

    if ($this->materialInsuficiente($piezasBlancas, $piezasNegras)) return true;

    return false;
  }

  /**
   * Verifica si el jugador tiene movimientos legales
   * @param string $color 'blancas' o 'negras'
   * @return bool True si tiene al menos un movimiento
   */
  private function tieneMovimientosLegales($color)
  {
    $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    foreach ($this->jugadores[$color]->getPiezas() as $pieza) {
      if ($pieza->estaCapturada()) continue;
      $origen = $pieza->getPosicion();
      if (!$origen) continue;

      foreach ($letras as $col) {
        for ($fila = 1; $fila <= 8; $fila++) {
          $destino = $col . $fila;
          if ($destino === $origen) continue;

          $piezaDestino = $this->obtenerPiezaEnPosicion($destino);
          $esCaptura = ($piezaDestino !== null && $piezaDestino->getColor() !== $color);
          if ($piezaDestino !== null && $piezaDestino->getColor() === $color) continue;

          $casillasRecorridas = ($pieza instanceof Peon)
            ? $pieza->simulaMovimiento($destino, $esCaptura)
            : $pieza->simulaMovimiento($destino);

          if (empty($casillasRecorridas)) continue;

          $hayBloqueo = false;
          if (!($pieza instanceof Caballo)) {
            for ($i = 0; $i < count($casillasRecorridas) - 1; $i++) {
              if ($this->obtenerPiezaEnPosicion($casillasRecorridas[$i]) !== null) {
                $hayBloqueo = true;
                break;
              }
            }
          }
          if ($hayBloqueo) continue;

          $backup = serialize($this->jugadores);
          $tempJugadores = unserialize($backup);
          $piezaOrigenTemp = $tempJugadores[$color]->getPiezaEnPosicion($origen);
          $piezaDestinoTemp = $this->obtenerPiezaEnPosicion($destino, $tempJugadores);

          if ($piezaDestinoTemp && $esCaptura) {
            $piezaDestinoTemp->capturar();
          }

          if ($piezaOrigenTemp) {
            $piezaOrigenTemp->setPosicion($destino);
            $piezaOrigenTemp->setHaMovido(true);
            if ($piezaOrigenTemp instanceof Peon) {
              $piezaOrigenTemp->setEsPrimerMovimiento(false);
            }
          }

          if (!$this->estaEnJaque($color, $tempJugadores)) {
            return true;
          }
        }
      }
    }

    return false;
  }

  /**
   * Detecta material insuficiente para jaque mate
   * @param array $piezasBlancas
   * @param array $piezasNegras
   * @return bool
   */
  private function materialInsuficiente($piezasBlancas, $piezasNegras)
  {
    $sinRey = fn($piezas) => array_filter($piezas, fn($p) => !($p instanceof Rey));

    $restantesB = $sinRey($piezasBlancas);
    $restantesN = $sinRey($piezasNegras);

    // Solo reyes
    if (count($restantesB) === 0 && count($restantesN) === 0) return true;

    // Rey + (alfil o caballo) vs rey
    $esMinor = fn($restantes) => count($restantes) === 1 && ($restantes[0] instanceof Alfil || $restantes[0] instanceof Caballo);

    if ($esMinor($restantesB) && count($restantesN) === 0) return true;
    if ($esMinor($restantesN) && count($restantesB) === 0) return true;

    return false;
  }

  /**
   * Establece el mensaje de estado
   * @param string $nuevoMensaje El nuevo mensaje
   */
  public function setMensaje($nuevoMensaje)
  {
    $this->mensaje = $nuevoMensaje;
  }

  /**
   * Ejecuta el enroque tras confirmación del jugador
   * @param string $origen Posición de origen (rey)
   * @param string $destino Posición de destino (rey)
   * @param string $tipo Tipo de enroque: 'corto' o 'largo'
   * @return bool True si se ejecutó correctamente
   */
  public function ejecutarEnroque($origen, $destino, $tipo)
  {
    // Validar que es el turno correcto
    $piezaOrigen = $this->jugadores[$this->turno]->getPiezaEnPosicion($origen);
    if (!$piezaOrigen || !($piezaOrigen instanceof Rey)) {
      $this->mensaje = "Error: no hay un rey en la posición de origen";
      return false;
    }

    // Determinar posiciones según el tipo de enroque
    $color = $this->turno;
    $posReyDestino = ($color === 'blancas') ? (($tipo === 'corto') ? 'G1' : 'C1') : (($tipo === 'corto') ? 'G8' : 'C8');
    $posTorreOrigen = ($color === 'blancas') ? (($tipo === 'corto') ? 'H1' : 'A1') : (($tipo === 'corto') ? 'H8' : 'A8');
    $posTorreDestino = ($color === 'blancas') ? (($tipo === 'corto') ? 'F1' : 'D1') : (($tipo === 'corto') ? 'F8' : 'D8');

    $torre = $this->jugadores[$color]->getPiezaEnPosicion($posTorreOrigen);
    if (!$torre || !($torre instanceof Torre)) {
      $this->mensaje = "Error: no se encontró la torre para el enroque";
      return false;
    }

    // Ejecutar el movimiento
    $piezaOrigen->setPosicion($posReyDestino);
    $piezaOrigen->setHaMovido(true);
    $torre->setPosicion($posTorreDestino);
    $torre->setHaMovido(true);

    // Registrar en historial con notación estándar
    $notacion = ($tipo === 'corto') ? 'O-O' : 'O-O-O';

    // Cambiar turno
    $turnoAnterior = $this->turno;
    $this->turno = $this->oponente($this->turno);

    // Comprobar si el nuevo turno está en jaque/mate
    if ($this->estaEnJaque($this->turno)) {
      if ($this->esJaqueMate($this->turno)) {
        $this->partidaTerminada = true;
        $ganador = $this->oponente($this->turno);
        $this->mensaje = "¡Jaque mate! {$this->jugadores[$ganador]->getNombre()} ha ganado";
        $notacion .= '#';
      } else {
        $this->mensaje = "Jaque a {$this->jugadores[$this->turno]->getNombre()}";
        $notacion .= '+';
      }
    } else {
      $this->mensaje = "Turno de {$this->jugadores[$this->turno]->getNombre()}";
    }

    // Registrar en historial de movimientos
    $this->historialMovimientos[] = [
      'numero' => count($this->historialMovimientos) + 1,
      'color' => $turnoAnterior,
      'notacion' => $notacion,
      'captura' => false
    ];

    // Guardar estado para deshacer
    $estadoSerializado = serialize([
      'jugadores' => [
        'blancas' => clone $this->jugadores['blancas'],
        'negras' => clone $this->jugadores['negras']
      ],
      'turno' => $turnoAnterior,
      'mensaje' => "Enroque " . $tipo,
      'terminada' => false
    ]);
    $this->historial[] = $estadoSerializado;
    if (count($this->historial) > 10) {
      array_shift($this->historial);
    }

    return true;
  }

  /**
   * Obtiene el último movimiento realizado
   * @return array|null Array con información del último movimiento o null
   */
  public function getUltimoMovimiento()
  {
    return $this->ultimoMovimiento;
  }

  /**
   * Devuelve el oponente del color dado
   * @param string $color 'blancas' o 'negras'
   * @return string El color oponente
   */
  private function oponente($color)
  {
    return $color === 'blancas' ? 'negras' : 'blancas';
  }
}
