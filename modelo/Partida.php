<?php

require_once __DIR__ . '/Jugador.php';

/**
 * Clase Partida
 * Controla el juego completo de ajedrez
 */
class Partida
{
  private $jugadores;  // Array de 2 jugadores
  private $turno;      // Jugador al que le toca mover
  private $mensaje;    // Mensaje de estado de la partida
  private $partidaTerminada; // Indica si la partida ha terminado
  private $historial;  // Historial de snapshots para deshacer jugadas
  private $ultimoMovimiento; // Meta del último movimiento realizado

  /**
   * Constructor de Partida
   * Inicializa los dos jugadores y establece que comienzan las blancas
   * @param string $nombreBlancas Nombre del jugador con piezas blancas
   * @param string $nombreNegras Nombre del jugador con piezas negras
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
  }

  /**
   * Realiza una jugada en el tablero
   * @param string $origen Posición de origen (ej: "E2")
   * @param string $destino Posición de destino (ej: "E4")
   * @return bool True si la jugada fue exitosa
   */
  public function jugada($origen, $destino)
  {
    if ($this->partidaTerminada) {
      $this->mensaje = "La partida ha terminado";
      return false;
    }

    // Guardar snapshot del estado actual para poder deshacer
    $this->historial[] = serialize([
      'jugadores' => $this->jugadores,
      'turno' => $this->turno,
      'mensaje' => $this->mensaje,
      'partidaTerminada' => $this->partidaTerminada
    ]);
    // Limitar historial a 10 movimientos para no consumir memoria
    if (count($this->historial) > 10) {
      array_shift($this->historial);
    }

    // 1. Verificar que existe una pieza en el origen
    $piezaOrigen = $this->jugadores[$this->turno]->getPiezaEnPosicion($origen);

    if (!$piezaOrigen) {
      $this->mensaje = "No hay ninguna pieza en $origen o no es tu pieza";
      return false;
    }

    // 2. Verificar si hay una pieza en el destino (para saber si es captura)
    $piezaDestino = $this->obtenerPiezaEnPosicion($destino);
    $esCaptura = ($piezaDestino !== null);

    // 2.1 Detectar posibles movimientos especiales antes del cálculo estándar
    $esEnroque = false;
    $tipoEnroque = null; // 'corto' | 'largo'
    $esEnPassant = false;
    $posCapturaEnPassant = null;

    // Coordenadas para cálculos especiales
    $coordsOrigen = $this->notacionACoordsLocal($origen);
    $coordsDestino = $this->notacionACoordsLocal($destino);
    if (!$coordsOrigen || !$coordsDestino) {
      $this->mensaje = "Coordenadas inválidas";
      return false;
    }
    list($filaOrig, $colOrig) = $coordsOrigen;
    list($filaDest, $colDest) = $coordsDestino;

    // Enroque: el rey se desplaza 2 columnas en la misma fila
    if ($piezaOrigen instanceof Rey && !$piezaOrigen->haMovido()) {
      if ($filaOrig === $filaDest && abs($colDest - $colOrig) === 2) {
        // Determinar corto/largo por dirección de columna
        if ($colDest > $colOrig) {
          // Corto (E -> G)
          if ($this->puedeEnrocarCorto($this->turno)) {
            $esEnroque = true;
            $tipoEnroque = 'corto';
          } else {
            $this->mensaje = "Enroque corto no permitido";
            return false;
          }
        } else {
          // Largo (E -> C)
          if ($this->puedeEnrocarLargo($this->turno)) {
            $esEnroque = true;
            $tipoEnroque = 'largo';
          } else {
            $this->mensaje = "Enroque largo no permitido";
            return false;
          }
        }
      }
    }

    // Captura al paso: peón se mueve diagonal a casilla vacía en el turno inmediatamente posterior
    if (!$esEnroque && $piezaOrigen instanceof Peon && !$esCaptura) {
      $direccion = ($this->turno === 'blancas') ? -1 : 1;
      $difFilas = $filaDest - $filaOrig;
      $difCols = abs($colDest - $colOrig);
      if ($difFilas === $direccion && $difCols === 1) {
        // Casilla del peón que sería capturado al paso (misma fila de origen, columna destino)
        $posCapturaEnPassant = $this->coordsANotacionLocal($filaOrig, $colDest);
        $piezaPosibleCapturada = $this->obtenerPiezaEnPosicion($posCapturaEnPassant);
        // Validar último movimiento del contrario
        if ($this->ultimoMovimiento && $piezaPosibleCapturada instanceof Peon) {
          $um = $this->ultimoMovimiento;
          // Debe ser peón del oponente que avanzó 2 casillas y acabó en la posición a capturar
          $coordsUMOrig = $this->notacionACoordsLocal($um['origen']);
          $coordsUMDest = $this->notacionACoordsLocal($um['destino']);
          if ($coordsUMOrig && $coordsUMDest && $um['pieza'] === 'Peon' && $um['color'] !== $this->turno) {
            $salto = abs($coordsUMDest[0] - $coordsUMOrig[0]);
            if ($salto === 2 && $um['destino'] === $posCapturaEnPassant) {
              $esEnPassant = true;
              // Se trata como captura especial
              $esCaptura = true;
            }
          }
        }
      }
    }

    // 3. Verificar que la pieza puede moverse a ese destino
    // Para peones, debemos indicar si es captura
    if ($piezaOrigen instanceof Peon) {
      $casillasRecorridas = $piezaOrigen->simulaMovimiento($destino, $esCaptura);
    } else {
      $casillasRecorridas = $piezaOrigen->simulaMovimiento($destino);
    }

    if (!$esEnroque && !$esEnPassant && empty($casillasRecorridas)) {
      $this->mensaje = "Movimiento inválido para esta pieza";
      return false;
    }

    // 4. Verificar que no hay piezas en las posiciones intermedias (excepto caballo)
    $hayPiezasIntermedias = false;

    // Para todas las casillas excepto la última (destino)
    for ($i = 0; $i < count($casillasRecorridas) - 1; $i++) {
      $casilla = $casillasRecorridas[$i];

      if ($this->obtenerPiezaEnPosicion($casilla) !== null) {
        $hayPiezasIntermedias = true;
        break;
      }
    }
    // El caballo puede saltar, los demás no
    if ($hayPiezasIntermedias && !($piezaOrigen instanceof Caballo)) {
      $this->mensaje = "Hay piezas bloqueando el camino";
      return false;
    }

    // Evitar movimientos que dejen al propio rey en jaque
    // Simulamos el movimiento en una copia clonada de $this->jugadores
    $backup = serialize($this->jugadores);
    $tempJugadores = unserialize($backup);

    // Obtener las piezas dentro del estado temporal
    $piezaOrigenTemp = $tempJugadores[$this->turno]->getPiezaEnPosicion($origen);
    $piezaDestinoTemp = $this->obtenerPiezaEnPosicion($destino, $tempJugadores);

    if ($esEnPassant) {
      // Captura al paso: capturar la pieza en posCapturaEnPassant
      $piezaAlPasoTemp = $this->obtenerPiezaEnPosicion($posCapturaEnPassant, $tempJugadores);
      if ($piezaAlPasoTemp && $piezaAlPasoTemp instanceof Peon) {
        $piezaAlPasoTemp->capturar();
      }
    } elseif ($piezaDestinoTemp) {
      $piezaDestinoTemp->capturar();
    }

    if ($piezaOrigenTemp && !$esEnroque) {
      $piezaOrigenTemp->setPosicion($destino);
      $piezaOrigenTemp->setHaMovido();
      if ($piezaOrigenTemp instanceof Peon) {
        $piezaOrigenTemp->setEsPrimerMovimiento(false);
      }
    }

    // Simular enroque moviendo rey y torre en temp si aplica
    if ($esEnroque) {
      $color = $this->turno;
      $filaInicial = ($color === 'blancas') ? 1 : 8;
      $posReyOrigen = ($color === 'blancas') ? 'E1' : 'E8';
      $posReyDestino = ($color === 'blancas') ? (($tipoEnroque === 'corto') ? 'G1' : 'C1') : (($tipoEnroque === 'corto') ? 'G8' : 'C8');
      $posTorreOrigen = ($color === 'blancas') ? (($tipoEnroque === 'corto') ? 'H1' : 'A1') : (($tipoEnroque === 'corto') ? 'H8' : 'A8');
      $posTorreDestino = ($color === 'blancas') ? (($tipoEnroque === 'corto') ? 'F1' : 'D1') : (($tipoEnroque === 'corto') ? 'F8' : 'D8');

      $reyTemp = $tempJugadores[$color]->getRey();
      $torreTemp = $tempJugadores[$color]->getPiezaEnPosicion($posTorreOrigen);
      if (!$reyTemp || !$torreTemp) {
        $this->mensaje = 'Enroque no válido';
        return false;
      }
      $reyTemp->setPosicion($posReyDestino);
      $torreTemp->setPosicion($posTorreDestino);
    }

    $miColor = $this->turno;
    $quedaEnJaque = $this->estaEnJaque($miColor, $tempJugadores);

    if ($quedaEnJaque) {
      $this->mensaje = "Movimiento inválido: deja en jaque a tu rey";
      return false;
    }

    // 5. Verificar la casilla de destino
    if (!$esEnroque && !$esEnPassant && $piezaDestino !== null) {
      // Hay una pieza en el destino
      if ($piezaDestino->getColor() === $this->turno) {
        // Es una pieza propia, no se puede mover ahí
        $this->mensaje = "No puedes capturar tus propias piezas";
        return false;
      } else {
        // Es una pieza enemiga, se captura
        $piezaDestino->capturar();

        // Verificar si era el rey (fin de partida)
        if ($piezaDestino instanceof Rey) {
          $this->partidaTerminada = true;
          $nombreGanador = $this->jugadores[$this->turno]->getNombre();
          $this->mensaje = "¡Jaque mate! " . $nombreGanador . " ha ganado la partida";
          return true;
        }
      }
    }

    // 6. Realizar el movimiento (incluye especiales)
    if ($esEnroque) {
      $color = $this->turno;
      $posReyDestino = ($color === 'blancas') ? (($tipoEnroque === 'corto') ? 'G1' : 'C1') : (($tipoEnroque === 'corto') ? 'G8' : 'C8');
      $posTorreOrigen = ($color === 'blancas') ? (($tipoEnroque === 'corto') ? 'H1' : 'A1') : (($tipoEnroque === 'corto') ? 'H8' : 'A8');
      $posTorreDestino = ($color === 'blancas') ? (($tipoEnroque === 'corto') ? 'F1' : 'D1') : (($tipoEnroque === 'corto') ? 'F8' : 'D8');

      $torre = $this->jugadores[$color]->getPiezaEnPosicion($posTorreOrigen);
      if (!$torre || !($torre instanceof Torre)) {
        $this->mensaje = 'No se encontró la torre para enroque';
        return false;
      }
      // Mover rey y torre
      $piezaOrigen->setPosicion($posReyDestino);
      $piezaOrigen->setHaMovido();
      $torre->setPosicion($posTorreDestino);
      $torre->setHaMovido();
    } else {
      // Para peones, necesitamos indicar si es captura
      if ($piezaOrigen instanceof Peon) {
        // Captura al paso: capturar pieza intermedia
        if ($esEnPassant && $posCapturaEnPassant) {
          $piezaAlPaso = $this->obtenerPiezaEnPosicion($posCapturaEnPassant);
          if ($piezaAlPaso && $piezaAlPaso instanceof Peon) {
            $piezaAlPaso->capturar();
          }
        }
        $piezaOrigen->movimiento($destino, $esCaptura);
      } else {
        $piezaOrigen->movimiento($destino);
      }
    }

    // 6.5. Verificar promoción de peón (deferida al controlador mediante modal)
    // Si el peón puede promoverse, no realizar aquí la promoción automática.

    // Registrar último movimiento (para captura al paso)
    $this->ultimoMovimiento = [
      'pieza' => ($piezaOrigen instanceof Peon) ? 'Peon' : (($piezaOrigen instanceof Rey) ? 'Rey' : get_class($piezaOrigen)),
      'color' => $this->turno,
      'origen' => $origen,
      'destino' => $destino
    ];

    // 7. Cambiar el turno
    $this->turno = ($this->turno === 'blancas') ? 'negras' : 'blancas';
    // Comprobar si el jugador al que le toca está en jaque o jaque mate
    $jugadorSiguiente = $this->turno;
    if ($this->estaEnJaque($jugadorSiguiente)) {
      if ($this->esJaqueMate($jugadorSiguiente)) {
        $this->partidaTerminada = true;
        // El ganador es el jugador que movió (el anterior)
        $ganadorColor = ($jugadorSiguiente === 'blancas') ? 'negras' : 'blancas';
        $nombreGanador = $this->jugadores[$ganadorColor]->getNombre();
        $this->mensaje = "¡Jaque mate! " . $nombreGanador . " ha ganado la partida";
      } else {
        $this->mensaje = "Jaque a " . $this->jugadores[$jugadorSiguiente]->getNombre();
      }
    } else {
      $this->mensaje = "Turno de " . $this->jugadores[$this->turno]->getNombre() .
        " (" . $this->turno . ")";
    }

    return true;
  }

  /**
   * Obtiene una pieza en una posición específica (de cualquier jugador)
   * @param string $posicion Posición a buscar
   * @return Pieza|null La pieza encontrada o null
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
  public function estaEnJaque($color, $jugadores = null)
  {
    $jugadoresUso = $jugadores ?? $this->jugadores;

    $rey = $jugadoresUso[$color]->getRey();
    if ($rey === null) return false;

    $posRey = $rey->getPosicion();
    $oponente = ($color === 'blancas') ? 'negras' : 'blancas';

    foreach ($jugadoresUso[$oponente]->getPiezas() as $pieza) {
      if ($pieza->estCapturada()) continue;

      if ($pieza instanceof Peon) {
        $movs = $pieza->simulaMovimiento($posRey, true);
      } else {
        $movs = $pieza->simulaMovimiento($posRey);
      }

      if (empty($movs)) continue;

      // Comprobar bloqueo para piezas que no son caballo
      if (!($pieza instanceof Caballo)) {
        $bloqueado = false;
        for ($i = 0; $i < count($movs) - 1; $i++) {
          if ($this->obtenerPiezaEnPosicion($movs[$i], $jugadoresUso) !== null) {
            $bloqueado = true;
            break;
          }
        }
        if ($bloqueado) continue;
      }

      // Si llega aquí, la pieza amenaza la casilla del rey
      return true;
    }

    return false;
  }

  /**
   * Comprueba si el color dado está en jaque mate
   * @param string $color
   * @return bool
   */
  public function esJaqueMate($color)
  {
    if (!$this->estaEnJaque($color)) return false;

    // Probar todas las jugadas posibles del color y ver si alguna quita el jaque
    $misPiezas = $this->jugadores[$color]->getPiezas();
    $todas = $this->obtenerTodasCasillas();

    foreach ($misPiezas as $pieza) {
      if ($pieza->estCapturada()) continue;

      foreach ($todas as $destino) {
        // Determinar si el destino está ocupado y por quién
        $piezaDestinoObj = $this->obtenerPiezaEnPosicion($destino);
        if ($piezaDestinoObj && $piezaDestinoObj->getColor() === $color) {
          continue; // No podemos mover a una casilla ocupada por pieza propia
        }

        // Determinar si el movimiento es posible según la pieza
        if ($pieza instanceof Peon) {
          $esCaptura = ($piezaDestinoObj !== null) && ($piezaDestinoObj->getColor() !== $color);
          $movs = $pieza->simulaMovimiento($destino, $esCaptura);
        } else {
          $movs = $pieza->simulaMovimiento($destino);
        }

        if (empty($movs)) continue;

        // Comprobar bloqueo para piezas que no son caballo
        if (!($pieza instanceof Caballo)) {
          $bloqueado = false;
          for ($i = 0; $i < count($movs) - 1; $i++) {
            if ($this->obtenerPiezaEnPosicion($movs[$i]) !== null) {
              $bloqueado = true;
              break;
            }
          }
          if ($bloqueado) continue;
        }

        // Simular el movimiento en una copia clonada del estado de jugadores
        $jugadoresUso = $this->jugadores;
        $backup = serialize($jugadoresUso);
        $tempJugadores = unserialize($backup);

        // Encontrar la pieza correspondiente en el estado temporal (buscando por posición original)
        $origenPos = $pieza->getPosicion();
        $piezaTemp = $tempJugadores[$color]->getPiezaEnPosicion($origenPos);
        $piezaDestinoTemp = $this->obtenerPiezaEnPosicion($destino, $tempJugadores);

        if ($piezaDestinoTemp) {
          $piezaDestinoTemp->capturar();
        }

        if ($piezaTemp) {
          $piezaTemp->setPosicion($destino);
          if ($piezaTemp instanceof Peon) {
            $piezaTemp->setEsPrimerMovimiento(false);
          }
        }

        $quedaEnJaque = $this->estaEnJaque($color, $tempJugadores);

        if (!$quedaEnJaque) {
          return false; // Existe una jugada que quita el jaque
        }
      }
    }

    return true; // Ninguna jugada quita el jaque
  }

  /**
   * Devuelve todas las casillas A1..H8
   * @return array
   */
  private function obtenerTodasCasillas()
  {
    $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    $out = [];
    for ($fila = 1; $fila <= 8; $fila++) {
      foreach ($cols as $c) {
        $out[] = $c . $fila;
      }
    }
    return $out;
  }

  /**
   * Devuelve el marcador de la partida (puntos por piezas capturadas del oponente)
   * @return array Array con los puntos [puntosBlancas, puntosNegras]
   */
  public function marcador()
  {
    $puntosBlancas = 0;
    $puntosNegras = 0;

    // Sumar puntos de piezas capturadas del oponente
    foreach ($this->jugadores['negras']->getPiezas() as $pieza) {
      if ($pieza->estCapturada()) {
        $puntosBlancas += $pieza->getValor();
      }
    }

    foreach ($this->jugadores['blancas']->getPiezas() as $pieza) {
      if ($pieza->estCapturada()) {
        $puntosNegras += $pieza->getValor();
      }
    }

    return [$puntosBlancas, $puntosNegras];
  }

  /**
   * Muestra el tablero en modo texto
   * @return string Representación del tablero en texto
   */
  public function muestraTablero()
  {
    $tablero = "\n  A B C D E F G H\n";

    for ($fila = 8; $fila >= 1; $fila--) {
      $tablero .= $fila . " ";

      for ($col = 0; $col < 8; $col++) {
        $columna = chr(ord('A') + $col);
        $posicion = $columna . $fila;

        $pieza = $this->obtenerPiezaEnPosicion($posicion);

        if ($pieza === null) {
          $tablero .= "x ";
        } else {
          $letra = $this->obtenerLetraPieza($pieza);
          // Mayúsculas para blancas, minúsculas para negras
          $letra = ($pieza->getColor() === 'blancas') ? strtoupper($letra) : strtolower($letra);
          $tablero .= $letra . " ";
        }
      }

      $tablero .= $fila . "\n";
    }

    $tablero .= "  A B C D E F G H\n";

    return $tablero;
  }

  /**
   * Obtiene la letra que representa una pieza
   * @param Pieza $pieza La pieza
   * @return string Letra representativa (T=Torre, C=Caballo, A=Alfil, D=Dama, R=Rey, P=Peón)
   */
  private function obtenerLetraPieza($pieza)
  {
    if ($pieza instanceof Torre) return 'T';
    if ($pieza instanceof Caballo) return 'C';
    if ($pieza instanceof Alfil) return 'A';
    if ($pieza instanceof Dama) return 'D';
    if ($pieza instanceof Rey) return 'R';
    if ($pieza instanceof Peon) return 'P';
    return '?';
  }

  /**
   * Obtiene el mensaje actual de la partida
   * @return string Mensaje de estado
   */
  public function getMensaje()
  {
    return $this->mensaje;
  }

  /**
   * Obtiene el turno actual
   * @return string Color del jugador actual
   */
  public function getTurno()
  {
    return $this->turno;
  }

  /**
   * Verifica si la partida ha terminado
   * @return bool True si la partida terminó
   */
  public function estaTerminada()
  {
    return $this->partidaTerminada;
  }

  /**
   * Obtiene todos los jugadores
   * @return array Array asociativo con los jugadores
   */
  public function getJugadores()
  {
    return $this->jugadores;
  }

  /**
   * Verifica si hay historial de jugadas disponible para deshacer
   * @return bool True si hay jugadas en el historial, false en caso contrario
   */
  public function tieneHistorial()
  {
    return !empty($this->historial);
  }

  /**
   * Deshace la última jugada realizada
   * @return bool True si se pudo deshacer, false si no hay jugadas para deshacer
   */
  public function deshacerJugada()
  {
    if (empty($this->historial)) {
      $this->mensaje = "No hay jugadas para deshacer";
      return false;
    }

    // Restaurar el último snapshot
    $snapshot = unserialize(array_pop($this->historial));
    $this->jugadores = $snapshot['jugadores'];
    $this->turno = $snapshot['turno'];
    $this->mensaje = $snapshot['mensaje'];
    $this->partidaTerminada = $snapshot['partidaTerminada'];

    return true;
  }

  /**
   * Verifica si el enroque corto está disponible para el color dado
   * @param string $color 'blancas' o 'negras'
   * @return bool True si está disponible
   */
  public function puedeEnrocarCorto($color)
  {
    $rey = $this->jugadores[$color]->getRey();
    if (!$rey || $rey->haMovido() || $this->estaEnJaque($color)) return false;

    $torre = $this->jugadores[$color]->getPiezaEnPosicion($color === 'blancas' ? 'H1' : 'H8');
    if (!$torre || !($torre instanceof Torre) || $torre->haMovido()) return false;

    $casillas = $color === 'blancas' ? ['F1', 'G1'] : ['F8', 'G8'];
    foreach ($casillas as $casilla) {
      if ($this->obtenerPiezaEnPosicion($casilla) !== null) return false;
      // Simular movimiento del rey a esa casilla y verificar jaque
      $posOriginal = $rey->getPosicion();
      $rey->setPosicion($casilla);
      $enJaque = $this->estaEnJaque($color);
      $rey->setPosicion($posOriginal); // Restaurar
      if ($enJaque) return false;
    }
    return true;
  }

  /**
   * Verifica si el enroque largo está disponible para el color dado
   * @param string $color 'blancas' o 'negras'
   * @return bool True si está disponible
   */
  public function puedeEnrocarLargo($color)
  {
    $rey = $this->jugadores[$color]->getRey();
    if (!$rey || $rey->haMovido() || $this->estaEnJaque($color)) return false;

    $torre = $this->jugadores[$color]->getPiezaEnPosicion($color === 'blancas' ? 'A1' : 'A8');
    if (!$torre || !($torre instanceof Torre) || $torre->haMovido()) return false;

    $casillas = $color === 'blancas' ? ['B1', 'C1', 'D1'] : ['B8', 'C8', 'D8'];
    foreach ($casillas as $casilla) {
      if ($this->obtenerPiezaEnPosicion($casilla) !== null) return false;
      // Para largo, comprobar jaque en C y D; B puede estar fuera del trayecto del rey
      if ($casilla === ($color === 'blancas' ? 'C1' : 'C8') || $casilla === ($color === 'blancas' ? 'D1' : 'D8')) {
        $posOriginal = $rey->getPosicion();
        $rey->setPosicion($casilla);
        $enJaque = $this->estaEnJaque($color);
        $rey->setPosicion($posOriginal);
        if ($enJaque) return false;
      }
    }
    return true;
  }

  /**
   * Verifica si hay captura al paso disponible
   * @param string $color 'blancas' o 'negras'
   * @return array|null Posición de captura o null
   */
  public function capturaAlPasoDisponible($color)
  {
    // Ahora gestionado dentro de jugada() mediante $this->ultimoMovimiento
    return null;
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
    $piezasBlancas = array_filter($this->jugadores['blancas']->getPiezas(), fn($p) => !$p->estCapturada());
    $piezasNegras = array_filter($this->jugadores['negras']->getPiezas(), fn($p) => !$p->estCapturada());

    $tieneBlancasPiezas = count(array_filter($piezasBlancas, fn($p) => !($p instanceof Rey))) > 0;
    $tieneNegrasPiezas = count(array_filter($piezasNegras, fn($p) => !($p instanceof Rey))) > 0;

    if (!$tieneBlancasPiezas && !$tieneNegrasPiezas) return true; // Solo reyes

    // Más complejos, pero por ahora solo stalemate
    return false;
  }

  /**
   * Verifica si el jugador tiene movimientos legales
   * @param string $color 'blancas' o 'negras'
   * @return bool True si tiene al menos un movimiento
   */
  private function tieneMovimientosLegales($color)
  {
    // Simplificado: verificar si alguna pieza puede mover sin dejar rey en jaque
    foreach ($this->jugadores[$color]->getPiezas() as $pieza) {
      if ($pieza->estCapturada()) continue;
      // Simular movimientos posibles
      // Por simplicidad, asumir que si no está en jaque y hay piezas, hay movimientos
      return true;
    }
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
   * Termina la partida
   */
  public function terminar()
  {
    $this->partidaTerminada = true;
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
