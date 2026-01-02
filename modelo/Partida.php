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

    // 3. Verificar que la pieza puede moverse a ese destino
    // Para peones, debemos indicar si es captura
    if ($piezaOrigen instanceof Peon) {
      $casillasRecorridas = $piezaOrigen->simulaMovimiento($destino, $esCaptura);
    } else {
      $casillasRecorridas = $piezaOrigen->simulaMovimiento($destino);
    }

    if (empty($casillasRecorridas)) {
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

    if ($piezaDestinoTemp) {
      $piezaDestinoTemp->capturar();
    }

    if ($piezaOrigenTemp) {
      $piezaOrigenTemp->setPosicion($destino);
      $piezaOrigenTemp->setHaMovido();
      if ($piezaOrigenTemp instanceof Peon) {
        $piezaOrigenTemp->setEsPrimerMovimiento(false);
      }
    }

    $miColor = $this->turno;
    $quedaEnJaque = $this->estaEnJaque($miColor, $tempJugadores);

    if ($quedaEnJaque) {
      $this->mensaje = "Movimiento inválido: deja en jaque a tu rey";
      return false;
    }

    // 5. Verificar la casilla de destino
    if ($piezaDestino !== null) {
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

    // 6. Realizar el movimiento
    // Para peones, necesitamos indicar si es captura
    if ($piezaOrigen instanceof Peon) {
      $piezaOrigen->movimiento($destino, $esCaptura);
    } else {
      $piezaOrigen->movimiento($destino);
    }

    // 6.5. Verificar promoción de peón
    if ($piezaOrigen instanceof Peon && $piezaOrigen->puedePromoverse()) {
      $this->jugadores[$this->turno]->promoverPeon($piezaOrigen, 'Dama');
      $this->mensaje = "Peón promovido a dama";
    }

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

    // Verificar casillas libres y no en jaque
    $casillas = $color === 'blancas' ? ['F1', 'G1'] : ['F8', 'G8'];
    foreach ($casillas as $casilla) {
      if ($this->jugadores[$color]->getPiezaEnPosicion($casilla) || $this->jugadores[$this->oponente($color)]->getPiezaEnPosicion($casilla)) return false;
      // Simular movimiento del rey a esa casilla y verificar jaque
      $rey->setPosicion($casilla);
      if ($this->estaEnJaque($color)) {
        $rey->setPosicion($color === 'blancas' ? 'E1' : 'E8'); // Restaurar
        return false;
      }
      $rey->setPosicion($color === 'blancas' ? 'E1' : 'E8'); // Restaurar
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

    // Verificar casillas libres y no en jaque
    $casillas = $color === 'blancas' ? ['B1', 'C1', 'D1'] : ['B8', 'C8', 'D8'];
    foreach ($casillas as $casilla) {
      if ($this->jugadores[$color]->getPiezaEnPosicion($casilla) || $this->jugadores[$this->oponente($color)]->getPiezaEnPosicion($casilla)) return false;
      if ($casilla === ($color === 'blancas' ? 'D1' : 'D8')) continue; // D1/D8 no necesita check de jaque para rey
      // Simular movimiento del rey a esa casilla y verificar jaque
      $rey->setPosicion($casilla);
      if ($this->estaEnJaque($color)) {
        $rey->setPosicion($color === 'blancas' ? 'E1' : 'E8'); // Restaurar
        return false;
      }
      $rey->setPosicion($color === 'blancas' ? 'E1' : 'E8'); // Restaurar
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
    // Implementación simplificada: verificar si el último movimiento fue un peón avanzando 2 casillas
    // Asumir que se guarda el último movimiento
    // Por simplicidad, devolver null por ahora (no implementado completamente)
    return null;
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
   * Devuelve el oponente del color dado
   * @param string $color 'blancas' o 'negras'
   * @return string El color oponente
   */
  private function oponente($color)
  {
    return $color === 'blancas' ? 'negras' : 'blancas';
  }
}
