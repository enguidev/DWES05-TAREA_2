<?php

require_once __DIR__ . '/Pieza.php';

/**
 * Clase Rey
 * Se mueve una casilla en cualquier dirección (horizontal, vertical o diagonal)
 * No tiene valor asignado (su pérdida significa perder la partida)
 */
class Rey extends Pieza
{
  /**
   * Constructor de Rey
   * @param string $posicion Posición inicial
   * @param string $color Color de la pieza
   */
  public function __construct($posicion, $color)
  {
    parent::__construct($posicion, $color);
    $this->valor = 0; // El rey no tiene valor (su pérdida = derrota)
  }

  /**
   * Verifica si el movimiento es válido para un rey y lo realiza
   * @param string $nuevaPosicion Posición destino
   * @return bool True si el movimiento es válido
   */
  public function movimiento($nuevaPosicion)
  {
    if ($this->estCapturada()) return false;

    $coordsActuales = $this->notacionACoords($this->posicion);
    $coordsNuevas = $this->notacionACoords($nuevaPosicion);

    if (!$coordsActuales || !$coordsNuevas) return false;

    list($filaActual, $colActual) = $coordsActuales;
    list($filaNueva, $colNueva) = $coordsNuevas;

    $difFilas = abs($filaNueva - $filaActual);
    $difCols = abs($colNueva - $colActual);

    // El rey se mueve solo una casilla en cualquier dirección
    $esMovimientoValido = ($difFilas <= 1 && $difCols <= 1) && ($difFilas + $difCols > 0);

    if ($esMovimientoValido) {
      $this->posicion = $nuevaPosicion;
      return true;
    }

    return false;
  }

  /**
   * Simula el movimiento del rey
   * Como el rey solo se mueve una casilla, solo devuelve la posición final
   * @param string $nuevaPosicion Posición destino
   * @return array Array con solo la posición final
   */
  public function simulaMovimiento($nuevaPosicion)
  {
    if ($this->estCapturada()) return [];

    $coordsActuales = $this->notacionACoords($this->posicion);
    $coordsNuevas = $this->notacionACoords($nuevaPosicion);

    if (!$coordsActuales || !$coordsNuevas) return [];

    list($filaActual, $colActual) = $coordsActuales;
    list($filaNueva, $colNueva) = $coordsNuevas;

    $difFilas = abs($filaNueva - $filaActual);
    $difCols = abs($colNueva - $colActual);

    $esMovimientoValido = ($difFilas <= 1 && $difCols <= 1) && ($difFilas + $difCols > 0);

    if ($esMovimientoValido) {
      // El rey solo se mueve una casilla, devolvemos solo la posición final
      return [$nuevaPosicion];
    }

    return [];
  }
}
